<?php

namespace Bgultekin\CashierFastspring\Tests;

use LogicException;
use Carbon\Carbon;
use Orchestra\Testbench\TestCase;
use Bgultekin\CashierFastspring\Tests\Traits\Database;
use Bgultekin\CashierFastspring\Tests\Traits\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Bgultekin\CashierFastspring\Subscription;
use Bgultekin\CashierFastspring\SubscriptionPeriod;
use GuzzleHttp\Psr7\Response;

class SubscriptionPeriodTest extends TestCase
{
    use Database;
    use Model;

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

    public function testSubscriptionPeriodCanBeConstructed()
    {
        $this->assertInstanceOf(SubscriptionPeriod::class, new SubscriptionPeriod());
    }

    public function testSubscriptionPeriodCanBeInserted()
    {
        $email = 'bilal@gultekin.me';

        $user = $this->createUser(['email' => $email, 'fastspring_id' => 'fastspring_id']);
        $subscription = $this->createSubscription($user, ['state' => 'active']);
        $period = $this->createSubscriptionPeriod($subscription);

        $this->assertEquals($period->subscription->id, $subscription->id);
    }
}
