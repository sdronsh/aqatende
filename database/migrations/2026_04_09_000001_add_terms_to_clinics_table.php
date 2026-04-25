<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->string('terms_version', 20)->nullable()->after('active');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_version');
            $table->string('terms_accepted_ip', 45)->nullable()->after('terms_accepted_at');
            $table->foreignId('terms_accepted_user_id')->nullable()->after('terms_accepted_ip')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('terms_accepted_user_id');
            $table->dropColumn(['terms_accepted_ip', 'terms_accepted_at', 'terms_version']);
        });
    }
};
