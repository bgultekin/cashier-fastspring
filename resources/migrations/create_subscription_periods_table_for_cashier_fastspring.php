<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionPeriodsTableForCashierFastspring extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_periods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('subscription_id');

            $table->string('type');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            $table->unique(['subscription_id', 'type', 'start_date', 'end_date'], 'subscription_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_periods');
    }
}
