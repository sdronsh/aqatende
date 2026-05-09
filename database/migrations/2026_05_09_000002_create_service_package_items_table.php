<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'is_package')) {
            Schema::table('services', function (Blueprint $table) {
                $table->boolean('is_package')->default(false)->after('shared_service');
            });
        }

        if (! Schema::hasTable('service_package_items')) {
            Schema::create('service_package_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_service_id')->constrained('services')->cascadeOnDelete();
                $table->foreignId('component_service_id')->constrained('services')->restrictOnDelete();
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['package_service_id', 'component_service_id'], 'svc_pkg_items_unique');
            });
        } else {
            try {
                Schema::table('service_package_items', function (Blueprint $table) {
                    $table->unique(['package_service_id', 'component_service_id'], 'svc_pkg_items_unique');
                });
            } catch (Throwable) {
                // Existing partial local migrations may already have this index.
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_package_items');

        if (Schema::hasColumn('services', 'is_package')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('is_package');
            });
        }
    }
};
