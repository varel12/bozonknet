<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('whatsapp', 25);
            $table->string('email', 150);
            $table->string('customer_type', 20);
            $table->foreignId('village_id')->constrained()->restrictOnDelete();
            $table->string('street_address');
            $table->text('full_address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('coverage_status', 20);
            $table->string('plan_code', 20);
            $table->string('plan_name', 50);
            $table->unsignedSmallInteger('speed_mbps');
            $table->unsignedInteger('monthly_price');
            $table->unsignedInteger('installation_fee')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('consented_at');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['village_id', 'plan_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_subscriptions');
    }
};
