<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('district')->default('Bojonggede');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('status', 20)->default('unavailable');
            $table->timestamps();

            $table->unique(['name', 'district']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
