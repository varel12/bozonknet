<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address')->nullable();
            $table->string('location_description')->nullable();
            $table->timestamps();
        });

        Schema::create('odcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_id')->constrained('olts')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 20)->default('Unmapped');
            $table->timestamps();
        });

        Schema::create('internet_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('speed_mbps');
            $table->unsignedInteger('price');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('registration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone_number', 25);
            $table->string('village_name')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('package_id')->nullable()->constrained('internet_packages')->nullOnDelete();
            $table->string('status', 20)->default('Pending');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'village_name']);
        });

        Schema::table('odps', function (Blueprint $table) {
            if (! Schema::hasColumn('odps', 'odc_id')) {
                $table->foreignId('odc_id')->nullable()->after('id')->constrained('odcs')->nullOnDelete();
            }

            if (! Schema::hasColumn('odps', 'village_name')) {
                $table->string('village_name')->nullable()->after('address');
            }

            if (! Schema::hasColumn('odps', 'available_ports')) {
                $table->unsignedSmallInteger('available_ports')->nullable()->after('used_ports');
            }
        });

        DB::statement('ALTER TABLE odps MODIFY latitude DECIMAL(10,7) NULL');
        DB::statement('ALTER TABLE odps MODIFY longitude DECIMAL(10,7) NULL');
        DB::statement('UPDATE odps SET available_ports = GREATEST(total_ports - used_ports, 0) WHERE available_ports IS NULL');
        DB::statement("UPDATE odps SET status = CASE WHEN available_ports = 0 THEN 'Full' ELSE 'Available' END WHERE status IN ('active', 'planned')");

        Schema::table('customer_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_subscriptions', 'odp_id')) {
                $table->foreignId('odp_id')->nullable()->after('id')->constrained('odps')->nullOnDelete();
            }

            if (! Schema::hasColumn('customer_subscriptions', 'package_id')) {
                $table->foreignId('package_id')->nullable()->after('village_id')->constrained('internet_packages')->nullOnDelete();
            }

            if (! Schema::hasColumn('customer_subscriptions', 'village_name')) {
                $table->string('village_name')->nullable()->after('full_address');
            }
        });

        Schema::table('network_markers', function (Blueprint $table) {
            if (! Schema::hasColumn('network_markers', 'odp_id')) {
                $table->foreignId('odp_id')->nullable()->after('id')->constrained('odps')->nullOnDelete();
            }

            if (! Schema::hasColumn('network_markers', 'odc_id')) {
                $table->foreignId('odc_id')->nullable()->after('odp_id')->constrained('odcs')->nullOnDelete();
            }

            if (! Schema::hasColumn('network_markers', 'customer_subscription_id')) {
                $table->foreignId('customer_subscription_id')->nullable()->after('odc_id')->constrained('customer_subscriptions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('network_markers', function (Blueprint $table) {
            foreach (['customer_subscription_id', 'odc_id', 'odp_id'] as $column) {
                if (Schema::hasColumn('network_markers', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });

        Schema::table('customer_subscriptions', function (Blueprint $table) {
            foreach (['village_name', 'package_id', 'odp_id'] as $column) {
                if (Schema::hasColumn('customer_subscriptions', $column)) {
                    $column === 'village_name' ? $table->dropColumn($column) : $table->dropConstrainedForeignId($column);
                }
            }
        });

        Schema::table('odps', function (Blueprint $table) {
            if (Schema::hasColumn('odps', 'available_ports')) {
                $table->dropColumn('available_ports');
            }
            if (Schema::hasColumn('odps', 'village_name')) {
                $table->dropColumn('village_name');
            }
            if (Schema::hasColumn('odps', 'odc_id')) {
                $table->dropConstrainedForeignId('odc_id');
            }
        });

        Schema::dropIfExists('registration_logs');
        Schema::dropIfExists('internet_packages');
        Schema::dropIfExists('odcs');
        Schema::dropIfExists('olts');
    }
};
