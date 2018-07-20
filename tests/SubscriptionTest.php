<?php

namespace Bgultekin\CashierFastspring\Tests;

use Bgultekin\CashierFastspring\Subscription;
use Bgultekin\CashierFastspring\Tests\Traits\Database;
use Bgultekin\CashierFastspring\Tests\Traits\Guzzle;
use Bgultekin\CashierFastspring\Tests\Traits\Model;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Orchestra\Testbench\TestCase;

class SubscriptionTest extends TestCase
{
    use Database;
    use Model;
    use Guzzle;

    public static function setUpBeforeClass()
    {
        if (file_exists(__DIR__.'/.env')) {
            $dotenv = new \Dotenv\Dotenv(__DIR__);
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
    public function testSubscriptionCanBeConstructed()
    {
        $this->assertInstanceOf(Subscription::class, new Subscription());
    }

    public function testOwner()
    {
        $email = 'bilal@gultekin.me';

        $user = $this->createUser(['email' => $email, 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, ['state' => 'active']);

        $this->assertEquals($subscription->user->email, $email);
    }

    public function testPeriods()
    {
        $user = $this->createUser(['email' => 'bilal@gultekin.me', 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, ['state' => 'active']);

        // create two periods
        $period1 = $this->createSubscriptionPeriod($subscription);
        $period2 = $this->createSubscriptionPeriod($subscription);

        $this->assertEquals($subscription->periods->count(), 2);
        $this->assertEquals($subscription->periods[0]->id, $period1->id);
        $this->assertEquals($subscription->periods[1]->id, $period2->id);
    }

    public function testActivePeriodOrCreateWhileThereIsOne()
    {
        $user = $this->createUser(['email' => 'bilal@gultekin.me', 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, ['state' => 'active']);

        // create two periods
        $period1 = $this->createSubscriptionPeriod($subscription, [
            'start_date' => Carbon::now()->subDays(45)->format('Y-m-d'),
            'end_date'   => Carbon::now()->subDays(15)->format('Y-m-d'),
            'type'       => 'fastspring',
        ]);

        $period2 = $this->createSubscriptionPeriod($subscription, [
            'start_date' => Carbon::now()->subDays(14)->format('Y-m-d'),
            'end_date'   => Carbon::now()->addDays(16)->format('Y-m-d'),
            'type'       => 'fastspring',
        ]);

        $activePeriod = $subscription->activePeriodOrCreate();

        $this->assertNotNull($activePeriod);
        $this->assertEquals($activePeriod->id, $period2->id);
        $this->assertEquals($subscription->activePeriod->id, $activePeriod->id);
    }

    public function testActivePeriodOrCreateWhileThereIsNoneForFastspringSubscription()
    {
        $user = $this->createUser(['email' => 'bilal@gultekin.me', 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, ['state' => 'active']);

        // set response for entry api
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([
                [
                    'beginPeriodDate' => Carbon::now()->subDays(14)->format('Y-m-d'),
                    'endPeriodDate'   => Carbon::now()->addDays(16)->format('Y-m-d'),
                ],
            ])),
        ]);

        // create two periods
        $period1 = $this->createSubscriptionPeriod($subscription, [
            'start_date' => Carbon::now()->subDays(45)->format('Y-m-d'),
            'end_date'   => Carbon::now()->subDays(15)->format('Y-m-d'),
        ]);

        $period2 = $subscription->activePeriodOrCreate();

        $this->assertNotNull($period2);
        $this->assertEquals($subscription->periods->count(), 2);
        // activePeriodOrCreate
        $this->assertEquals($subscription->activePeriod->id, $period2->id);
    }

    public function testActivePeriodOrCreateWhileThereIsEarlyOneForLocalMonthlySubscription()
    {
        $user = $this->createUser(['email' => 'bilal@gultekin.me', 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, [
            'state'         => 'active',
            'interval_unit' => 'month',
            'fastspring_id' => null,
        ]);

        // create a period with a start_date 65 days ago
        $lastCreatedPeriod = $this->createSubscriptionPeriod($subscription, [
            'start_date' => Carbon::now()->subDays(65)->format('Y-m-d'),
            'end_date'   => Carbon::now()->subDays(35)->format('Y-m-d'),
        ]);

        $lastPeriod = $subscription->activePeriodOrCreate();

        $this->assertNotNull($lastPeriod);
        // now there must be 4 total periods
        $this->assertEquals($subscription->periods->count(), 3);
        // activePeriodOrCreate
        $this->assertEquals($subscription->activePeriod->id, $lastPeriod->id);
    }

    public function testActivePeriodOrCreateWhileThereIsEarlyOneForLocalWeeklySubscription()
    {
        $user = $this->createUser(['email' => 'bilal@gultekin.me', 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, [
            'state'         => 'active',
            'interval_unit' => 'week',
            'fastspring_id' => null,
        ]);

        // create a period with a start_date 65 days ago
        $lastCreatedPeriod = $this->createSubscriptionPeriod($subscription, [
            'start_date' => Carbon::now()->subDays(65)->format('Y-m-d'),
            'end_date'   => Carbon::now()->subDays(35)->format('Y-m-d'),
        ]);

        $lastPeriod = $subscription->activePeriodOrCreate();

        $this->assertNotNull($lastPeriod);
        // now there must be 10 total periods
        $this->assertEquals($subscription->periods->count(), 10);
        // activePeriodOrCreate
        $this->assertEquals($subscription->activePeriod->id, $lastPeriod->id);
    }

    public function testActivePeriodOrCreateWhileThereIsEarlyOneForLocalYearlySubscription()
    {
        $user = $this->createUser(['email' => 'bilal@gultekin.me', 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, [
            'state'         => 'active',
            'interval_unit' => 'year',
            'fastspring_id' => null,
        ]);

        // create a period
        $lastCreatedPeriod = $this->createSubscriptionPeriod($subscription, [
            'start_date' => Carbon::now()->subDays(465)->format('Y-m-d'),
            'end_date'   => Carbon::now()->subDays(100)->format('Y-m-d'),
        ]);

        $lastPeriod = $subscription->activePeriodOrCreate();

        $this->assertNotNull($lastPeriod);
        // now there must be 2 total periods
        $this->assertEquals($subscription->periods->count(), 2);
        // activePeriodOrCreate
        $this->assertEquals($subscription->activePeriod->id, $lastPeriod->id);
    }

    public function testActivePeriodOrCreateWhileThereIsNoneForLocalSubscription()
    {
        $user = $this->createUser(['email' => 'bilal@gultekin.me', 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, [
            'state'         => 'active',
            'fastspring_id' => null,
        ]);

        $activePeriod = $subscription->activePeriodOrCreate();

        $this->assertNotNull($activePeriod);
        // now there must be 4 total periods
        $this->assertEquals($subscription->periods->count(), 1);
        // activePeriodOrCreate
        $this->assertEquals($subscription->activePeriod->id, $activePeriod->id);
        // active period start_date
        $this->assertEquals($subscription->activePeriod->start_date, Carbon::today());
    }

    public function testActivePeriodOrCreateWithNonExistIntervalUnit()
    {
        $this->expectException(\Exception::class);

        $user = $this->createUser(['email' => 'bilal@gultekin.me', 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, [
            'state'         => 'active',
            'fastspring_id' => null,
            'interval_unit' => 'martian-second',
        ]);

        $activePeriod = $subscription->activePeriodOrCreate();
    }

    public function testActiveSubscription()
    {
        $user = $this->createUser(['fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, ['state' => 'active']);

        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->active());

        $this->assertFalse($subscription->deactivated());
        $this->assertFalse($subscription->overdue());
        $this->assertFalse($subscription->trial());
        $this->assertFalse($subscription->canceled());
        $this->assertFalse($subscription->cancelled());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->onGracePeriod());
    }

    public function testCanceledSubscription()
    {
        $user = $this->createUser(['fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, ['state' => 'canceled']);

        $this->assertTrue($subscription->canceled());
        $this->assertTrue($subscription->cancelled());
        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->onGracePeriod());

        $this->assertFalse($subscription->deactivated());
        $this->assertFalse($subscription->overdue());
        $this->assertFalse($subscription->trial());
        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
    }

    public function testOnTrialSubscription()
    {
        $user = $this->createUser(['fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, ['state' => 'trial']);

        $this->assertTrue($subscription->trial());
        $this->assertTrue($subscription->valid());
        $this->assertTrue($subscription->onTrial());
        $this->assertTrue($subscription->onTrial('default', 'starter-plan'));

        $this->assertFalse($subscription->deactivated());
        $this->assertFalse($subscription->overdue());
        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onGracePeriod());
    }

    public function testOverdueSubscription()
    {
        $user = $this->createUser(['fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, ['state' => 'overdue']);

        $this->assertTrue($subscription->overdue());
        $this->assertTrue($subscription->valid());

        $this->assertFalse($subscription->deactivated());
        $this->assertFalse($subscription->trial());
        $this->assertFalse($subscription->active());
        $this->assertFalse($subscription->onTrial());
        $this->assertFalse($subscription->onGracePeriod());
    }

    public function testSwap()
    {
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([
                'subscriptions' => [
                    [
                        'subscription' => 'fastspring_id',
                        'result'       => 'success',
                    ],
                ],
            ])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $subscription = $this->createSubscription($user, ['fastspring_id' => 'fastspring_id']);

        $subscription->swap('new_plan', true);

        $this->assertEquals($subscription->plan, 'new_plan');
    }

    public function testSwapNoProrate()
    {
        $endDate = Carbon::now()->addDays(16)->format('Y-m-d');

        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([
                'subscriptions' => [
                    [
                        'subscription' => 'fastspring_id',
                        'result'       => 'success',
                    ],
                ],
            ])),
            new Response(200, [], json_encode([
                [
                    'beginPeriodDate' => Carbon::now()->subDays(14)->format('Y-m-d'),
                    'endPeriodDate'   => $endDate,
                ],
            ])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $subscription = $this->createSubscription($user, ['fastspring_id' => 'fastspring_id']);

        $subscription->swap('new_plan', false);
        $activePeriod = $subscription->activePeriodOrCreate();

        $this->assertEquals($subscription->swap_to, 'new_plan');
        $this->assertEquals($subscription->swap_at->format('Y-m-d'), $endDate);
    }

    public function testCancel()
    {
        $endDate = Carbon::now()->addDays(16)->format('Y-m-d');

        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([
                'subscriptions' => [
                    [
                        'subscription' => 'fastspring_id',
                        'result'       => 'success',
                    ],
                ],
            ])),
            new Response(200, [], json_encode([
                [
                    'beginPeriodDate' => Carbon::now()->subDays(14)->format('Y-m-d'),
                    'endPeriodDate'   => $endDate,
                ],
            ])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $subscription = $this->createSubscription($user, ['fastspring_id' => 'fastspring_id']);

        $subscription->cancel();
        $this->assertEquals($subscription->state, 'canceled');
        $this->assertEquals($subscription->swap_at->format('Y-m-d'), $endDate);
    }

    public function testCancelNow()
    {
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([
                'subscriptions' => [
                    [
                        'subscription' => 'fastspring_id',
                        'result'       => 'success',
                    ],
                ],
            ])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $subscription = $this->createSubscription($user, ['fastspring_id' => 'fastspring_id']);

        $subscription->cancelNow();
        $this->assertEquals($subscription->state, 'deactivated');
    }

    public function testResume()
    {
        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([
                'subscriptions' => [
                    [
                        'subscription' => 'fastspring_id',
                        'result'       => 'success',
                    ],
                ],
            ])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $subscription = $this->createSubscription($user, ['fastspring_id' => 'fastspring_id', 'state' => 'canceled']);

        $subscription->resume();
        $this->assertEquals($subscription->state, 'active');
        $this->assertNull($subscription->swap_at);
        $this->assertNull($subscription->swap_to);
    }

    public function testTryToResumeNoncanceled()
    {
        $this->expectException(\LogicException::class);

        $this->setMockResponsesAndHistory([
            new Response(200, [], json_encode([
                'subscriptions' => [
                    ['subscription' => 'fastspring_id'],
                ],
            ])),
        ]);

        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $subscription = $this->createSubscription($user, ['fastspring_id' => 'fastspring_id']);

        $response = $subscription->resume();
        $this->assertInternalType('object', $response);
        $this->assertObjectHasAttribute('subscription', $response->subscriptions[0]);
    }

    public function testType()
    {
        $user = $this->createUser([
            'fastspring_id' => 'fastspring_id',
        ]);

        $fastspringSubscription = $this->createSubscription($user, ['fastspring_id' => 'fastspring_id']);
        $localSubscription = $this->createSubscription($user, ['fastspring_id' => null]);

        // test fastspring
        $this->assertEquals($fastspringSubscription->type(), 'fastspring');
        $this->assertTrue($fastspringSubscription->isFastspring());
        $this->assertFalse($fastspringSubscription->isLocal());

        // test local
        $this->assertEquals($localSubscription->type(), 'local');
        $this->assertFalse($localSubscription->isFastspring());
        $this->assertTrue($localSubscription->isLocal());
    }
}
