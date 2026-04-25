<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model
{
    private static ?array $cachedUsage = null;
    private static ?string $cachedUsageVersion = null;

    protected $fillable = [
        'key',
        'version',
        'effective_at',
        'body',
        'updated_by_user_id',
    ];

    protected $casts = [
        'effective_at' => 'date',
    ];

    public static function currentUsage(): array
    {
        if (self::$cachedUsage !== null) {
            return self::$cachedUsage;
        }

        $term = static::where('key', 'usage')->first();
        if (! $term) {
            self::$cachedUsage = config('terms.usage', []);
            self::$cachedUsageVersion = self::$cachedUsage['version'] ?? null;

            return self::$cachedUsage;
        }

        self::$cachedUsage = [
            'version' => $term->version,
            'effective_at' => optional($term->effective_at)->format('Y-m-d'),
            'body' => $term->body,
            'id' => $term->id,
        ];
        self::$cachedUsageVersion = self::$cachedUsage['version'] ?? null;

        return self::$cachedUsage;
    }

    public static function currentUsageVersion(): ?string
    {
        if (self::$cachedUsageVersion !== null) {
            return self::$cachedUsageVersion;
        }

        self::$cachedUsageVersion = static::where('key', 'usage')->value('version')
            ?? config('terms.usage.version');

        return self::$cachedUsageVersion;
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
