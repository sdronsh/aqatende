<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('version', 20);
            $table->date('effective_at')->nullable();
            $table->longText('body');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $usage = config('terms.usage', []);
        $version = $usage['version'] ?? '1.0';
        $effectiveAt = $usage['effective_at'] ?? null;
        $body = $usage['body'] ?? 'Termo de uso nao definido.';

        DB::table('terms')->insert([
            'key' => 'usage',
            'version' => $version,
            'effective_at' => $effectiveAt,
            'body' => $body,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
