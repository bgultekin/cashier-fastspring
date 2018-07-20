<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTableForCashierFastspring extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
