<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $usage = config('terms.usage', []);

        DB::table('terms')->updateOrInsert(
            ['key' => 'usage'],
            [
                'version' => $usage['version'] ?? '1.1',
                'effective_at' => $usage['effective_at'] ?? '2026-05-24',
                'body' => $usage['body'] ?? 'Termo de uso nao definido.',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // Mantem o termo atual para evitar restaurar uma versao antiga ja aceita em producao.
    }
};
