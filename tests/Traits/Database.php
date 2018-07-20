<?php

namespace Bgultekin\CashierFastspring\Tests\Traits;

use Illuminate\Support\Facades\Schema;

trait Database
{
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function tearDown()
    {
        Schema::drop('users');
        Schema::drop('subscriptions');
        Schema::drop('invoices');
    }

    public function createUsersTable()
    {
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->string('name');
            $table->string('fastspring_id')->nullable();
            $table->timestamps();
        });
    }

    public function createSubscriptionsTable()
    {
        Schema::create('subscriptions', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name');
            $table->string('fastspring_id')->nullable();
            $table->string('plan');
            $table->string('state');
            $table->integer('quantity');
            $table->string('currency');
            $table->string('interval_unit');
            $table->integer('interval_length');
            $table->string('swap_to')->nullable();
            $table->datetime('swap_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function createSubscriptionPeriodsTable()
    {
        Schema::create('subscription_periods', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('subscription_id');
            $table->string('type');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
        });
    }

    public function createInvoicesTable()
    {
        Schema::create('invoices', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('fastspring_id')->nullable();
            $table->string('type')->nullable(); // subscription, order
            $table->string('subscription_display')->nullable();
            $table->string('subscription_product')->nullable();
            $table->integer('subscription_sequence')->nullable();
            $table->string('invoice_url');
            $table->decimal('total', 8, 2);
            $table->decimal('tax', 8, 2);
            $table->decimal('subtotal', 8, 2);
            $table->decimal('discount', 8, 2);
            $table->string('currency');
            $table->string('payment_type');
            $table->boolean('completed');
            $table->datetime('subscription_period_start_date')->nullable();
            $table->datetime('subscription_period_end_date')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
