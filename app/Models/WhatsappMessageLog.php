<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessageLog extends Model
{
    protected $fillable = [
        'branch_id',
        'whatsapp_config_id',
        'message_id',
        'phone_number_id',
        'display_phone_number',
        'contact_phone',
        'direction',
        'event_type',
        'message_type',
        'status',
        'template_name',
        'template_language',
        'message_body',
        'error_code',
        'error_message',
        'send_payload',
        'send_response',
        'webhook_payload',
        'accepted_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'last_status_at',
        'webhook_received_at',
    ];

    protected function casts(): array
    {
        return [
            'send_payload' => 'array',
            'send_response' => 'array',
            'webhook_payload' => 'array',
            'accepted_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'last_status_at' => 'datetime',
            'webhook_received_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function whatsappConfig(): BelongsTo
    {
        return $this->belongsTo(WhatsappConfig::class);
    }
}
