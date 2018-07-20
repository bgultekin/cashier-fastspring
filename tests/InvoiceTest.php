<?php

namespace Bgultekin\CashierFastspring\Tests;

use Orchestra\Testbench\TestCase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit_Framework_TestCase;
use Bgultekin\CashierFastspring\Tests\Fixtures\User;
use Bgultekin\CashierFastspring\Tests\Traits\Database;
use Bgultekin\CashierFastspring\Tests\Traits\Model;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Bgultekin\CashierFastspring\Tests\Fixtures\CashierTestControllerStub;
use Bgultekin\CashierFastspring\Fastspring\Fastspring;
use Bgultekin\CashierFastspring\Subscription;
use Bgultekin\CashierFastspring\Invoice;

class InvoiceTest extends TestCase
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

    public function testOrder()
    {
        $email = 'bilal@gultekin.me';

        $user = User::create([
            'email' => $email,
            'name' => 'Bilal Gultekin',
            'fastspring_id' => 'fastspring_id'
        ]);
        
        $invoice = $user->invoices()->create([
            'fastspring_id' => 'fastspring_id',
            'type' => 'subscription',
            'subscription_display' => 'subscription_display',
            'subscription_product' => 'subscription_product',
            'subscription_sequence' => 'subscription_sequence',
            'invoice_url' => 'invoice_url',
            'total' => 0,
            'tax' => 0,
            'subtotal' => 0,
            'discount' => 0,
            'currency' => 'USD',
            'payment_type' => 'test',
            'completed' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'subscription_period_start_date' => date('Y-m-d H:i:s'),
            'subscription_period_end_date' => date('Y-m-d H:i:s')
        ]);
        
        $this->assertInstanceOf('Carbon\Carbon', $invoice->created_at);
        $this->assertInstanceOf('Carbon\Carbon', $invoice->updated_at);
        $this->assertInstanceOf('Carbon\Carbon', $invoice->subscription_period_start_date);
        $this->assertInstanceOf('Carbon\Carbon', $invoice->subscription_period_end_date);
        $this->assertEquals($invoice->user->email, $email);
    }

    /**
     * Schema Helpers.
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }
}
