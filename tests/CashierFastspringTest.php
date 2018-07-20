<?php

namespace Bgultekin\CashierFastspring\Tests;

use Bgultekin\CashierFastspring\Billable;
use Bgultekin\CashierFastspring\Exceptions\NotImplementedException;
use Bgultekin\CashierFastspring\SubscriptionBuilder;
use Bgultekin\CashierFastspring\Tests\Traits\Database;
use Bgultekin\CashierFastspring\Tests\Traits\Guzzle;
use Bgultekin\CashierFastspring\Tests\Traits\Model;
use Exception;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Orchestra\Testbench\TestCase;

/**
 * This class tests general process of cashier over Billable trait.
 */
class CashierFastspringTest extends TestCase
{
    use Database;
    use Model;
    use Guzzle;

    public static function setUpBeforeClass()
    {
        if (file_exists(__DIR__.'/../.env')) {
            $dotenv = new \Dotenv\Dotenv(__DIR__.'/../');
            $dotenv->load();
        }
    }

    public function setUp()
    {
        parent::setUp();

        Eloquent::unguard();

        // create tables
        $this->createUsersTable();
        $this->createSubscriptionsTable();
        $this->createSubscriptionPeriodsTable();
        $this->createInvoicesTable();
    }

    /**
     * Tests.
     */
    public function testSubscriptionBuilderCanBeConstructed()
    {
        $this->assertInstanceOf(SubscriptionBuilder::class, new SubscriptionBuilder('owner', 'name', 'plan'));
    }

    public function testCreateSession()
    {
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode(['id' => 'session_id'])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $session = $user->newSubscription('main', 'starter-plan')->create();

        $this->assertObjectHasAttribute('id', $session);
    }

    public function testCreateSessionWithCoupon()
    {
        $transactions = [];
        $history = Middleware::history($transactions);
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode(['id' => 'session_id'])),
        ], $history);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $session = $user->newSubscription('main', 'starter-plan')->withCoupon('free-php-coupon')->quantity(1)->create();

        $body = (string) $transactions[0]['request']->getBody();
        $requestParameters = json_decode($body, true);

        $this->assertEquals(1, $requestParameters['items'][0]['quantity']);
        $this->assertEquals('main', $requestParameters['tags']['name']);
        $this->assertEquals('free-php-coupon', $requestParameters['coupon']);
        $this->assertObjectHasAttribute('id', $session);
    }

    public function testCreateAsFastspringCustomer()
    {
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode(['account' => 'fastspring_id'])),
        ]);

        $user = $this->createUser();

        $account = $user->createAsFastspringCustomer();

        $this->assertObjectHasAttribute('account', $account);
        $this->assertEquals($user->fastspring_id, 'fastspring_id');
    }

    public function testCreateSessionWithoutFastspringId()
    {
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode(['account' => 'fastspring_id'])),
            new Response(200, [], json_encode(['hello' => 'world'])),
        ]);

        $user = $this->createUser();

        $session = $user->newSubscription('main', 'starter-plan')->create();

        $this->assertObjectHasAttribute('hello', $session);
        $this->assertEquals($user->fastspring_id, 'fastspring_id');
    }

    public function testCreateSessionWithLostFastspringId()
    {
        $this->setMockResponsesAndHistory([
            new Response(407, [], json_encode([
                'error' => [
                    'email' => 'Email is already in use.',
                ],
            ])),
            new Response(200, [], json_encode(['accounts' => [
                    ['id' => 'fastspring_id'],
                ],
            ])),
            new Response(200, [], json_encode(['hello' => 'world'])),
        ]);

        $user = $this->createUser(['fastspring_id' => null]);

        $session = $user->newSubscription('main', 'starter-plan')->create();

        $this->assertObjectHasAttribute('hello', $session);
        $this->assertEquals($user->fastspring_id, 'fastspring_id');
    }

    public function testUpdateAsFastspringCustomer()
    {
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([['account' => 'fastspring_id']])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $account = $user->updateAsFastspringCustomer();

        $this->assertInternalType('array', $account);
        $this->assertObjectHasAttribute('account', $account[0]);
    }

    public function testUpdateAsFastspringCustomerWithoutFastspringId()
    {
        $this->expectException(Exception::class);

        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([['account' => 'fastspring_id']])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => null,
        ]);

        $account = $user->updateAsFastspringCustomer();
    }

    public function testAsFastspringCustomer()
    {
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([['account' => 'fastspring_id']])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $account = $user->asFastspringCustomer();

        $this->assertInternalType('array', $account);
        $this->assertObjectHasAttribute('account', $account[0]);
    }

    public function testGetAccountManagementURI()
    {
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode(['accounts' => [['url' => 'url']]])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $url = $user->accountManagementURI();

        $this->assertEquals($url, 'url');
    }

    public function testAsFastspringCustomerWithoutFastspringId()
    {
        $this->expectException(Exception::class);

        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([['account' => 'fastspring_id']])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => null,
        ]);

        $account = $user->asFastspringCustomer();
    }

    // improve
    public function testSubscription()
    {
        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);
        $this->createSubscription($user);

        $isSubscribedNotExist = $user->subscribed('notexist');
        $isSubscribed = $user->subscribed('main');
        $isSubscribedToPlan = $user->subscribedToPlan(['starter-plan'], 'main');
        $isSubscribedToPlanWithoutSubscription = $user->subscribedToPlan(['starter-plan']);
        $isSubscribedToPlanWithoutPlans = $user->subscribedToPlan([], 'main');
        $isSubscribedWithPlanParameter = $user->subscribed('main', 'starter-plan');
        $isSubscribedWithFakePlanParameter = $user->subscribed('main', 'non-plan');
        $subscription = $user->subscription('main');
        $subscriptions = $user->subscriptions();
        $onTrial = $user->onTrial('main', 'starter-plan');
        $onTrialWithoutPlan = $user->onTrial('main');
        $onPlan = $user->onPlan('starter-plan');

        $this->assertFalse($isSubscribedNotExist);
        $this->assertFalse($isSubscribedWithFakePlanParameter);
        $this->assertTrue($isSubscribed);
        $this->assertTrue($isSubscribedToPlan);
        $this->assertTrue($isSubscribedWithPlanParameter);
        $this->assertInternalType('object', $subscription);
        $this->assertEquals($subscription->plan, 'starter-plan');
        $this->assertEquals($subscriptions->count(), 1);
        $this->assertFalse($onTrial);
        $this->assertFalse($onTrialWithoutPlan);
        $this->assertFalse($isSubscribedToPlanWithoutSubscription);
        $this->assertTrue($onPlan);
        $this->assertFalse($isSubscribedToPlanWithoutPlans);
    }

    public function testHasFastspringId()
    {
        $user = $this->createUser();

        $user->hasFastspringId();

        $this->assertFalse($user->hasFastspringId());

        $user->fastspring_id = 'fastspring_id';
        $user->save();

        $this->assertTrue($user->hasFastspringId());
    }

    public function testGetFirstAndLastName()
    {
        $user = $this->createUser([
            'email' => 'first.middle@last.com',
            'name'  => 'First Middle Last',
        ]);

        $user2 = $this->createUser([
            'email' => 'first@last.com',
            'name'  => 'First Last',
        ]);

        $user3 = $this->createUser([
            'email' => 'first@last.com',
            'name'  => 'First',
        ]);

        $user4 = $this->createUser([
            'email' => 'first.space.middle.space@last.com',
            'name'  => 'First  Middle  Last',
        ]);

        $this->assertEquals($user->extractFirstName(), 'First Middle');
        $this->assertEquals($user2->extractFirstName(), 'First');
        $this->assertEquals($user3->extractFirstName(), 'First');
        $this->assertEquals($user4->extractFirstName(), 'First Middle');

        $this->assertEquals($user->extractLastName(), 'Last');
        $this->assertEquals($user2->extractLastName(), 'Last');
        $this->assertEquals($user3->extractLastName(), 'Unknown');
        $this->assertEquals($user4->extractLastName(), 'Last');
    }

    public function testCharge()
    {
        $this->expectException(NotImplementedException::class);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $user->charge(1);
    }

    public function testRefund()
    {
        $this->expectException(NotImplementedException::class);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $user->refund(1);
    }
}
