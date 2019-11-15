<?php

namespace Bgultekin\CashierFastspring\Tests;

use Bgultekin\CashierFastspring\SubscriptionPeriod;
use Bgultekin\CashierFastspring\Tests\Traits\Database;
use Bgultekin\CashierFastspring\Tests\Traits\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Orchestra\Testbench\TestCase;

class SubscriptionPeriodTest extends TestCase
{
    use Database;
    use Model;

    public static function setUpBeforeClass()
    {
        if (file_exists(__DIR__.'/.env')) {
            $dotenv = \Dotenv\Dotenv::create(__DIR__);
            $dotenv->load();
        }
    }

    public function setUp(): void
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
