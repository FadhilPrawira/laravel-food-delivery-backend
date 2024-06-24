<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Create foreign key to users.id with column orders.user_id. This reference to customer
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // Create foreign key to users.id with column orders.restaurant_id. This reference to restaurant
            $table->foreignId('restaurant_id')->constrained('users')->onDelete('cascade');
            // Create foreign key to users.id with column orders.driver_id. This reference to driver. When driver in the table users is deleted, set this column to null
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('total_price');
            $table->integer('shipping_cost');
            $table->integer('total_bill');
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pending');
            $table->string('shipping_address')->nullable();
            $table->string('shipping_latlong');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
