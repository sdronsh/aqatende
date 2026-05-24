<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappCampaign extends Model
{
    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'type',
        'status',
        'message',
        'inactive_days',
        'recipients_count',
        'sent_count',
        'failed_count',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'inactive_days' => 'int',
        'recipients_count' => 'int',
        'sent_count' => 'int',
        'failed_count' => 'int',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsappCampaignRecipient::class);
    }
}
