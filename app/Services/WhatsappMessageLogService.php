<?php

namespace App\Services;

use App\Models\WhatsappConfig;
use App\Models\WhatsappMessageLog;
use Illuminate\Support\Arr;

class WhatsappMessageLogService
{
    public static function recordAcceptedOutbound(WhatsappConfig $config, array $payload, array $response): ?WhatsappMessageLog
    {
        $messageId = data_get($response, 'messages.0.id');
        $now = now();

        // #region debug-point C:record-accepted-outbound
        self::dbg('record-accepted-outbound', 'C', [
            'branch_id' => $config->branch_id,
            'config_id' => $config->id,
            'phone_number_id' => $config->phone_number_id,
            'message_id' => $messageId,
            'to' => data_get($payload, 'to'),
            'template' => data_get($payload, 'template.name'),
            'message_status' => data_get($response, 'messages.0.message_status'),
        ], '[DEBUG] record accepted outbound');
        // #endregion

        $attributes = [
            'branch_id' => $config->branch_id,
            'whatsapp_config_id' => $config->id,
            'phone_number_id' => $config->phone_number_id,
            'display_phone_number' => $config->phone_number_oficial,
            'contact_phone' => (string) data_get($payload, 'to'),
            'direction' => 'outbound',
            'event_type' => 'send_api_accepted',
            'message_type' => (string) data_get($payload, 'type', 'template'),
            'status' => 'accepted',
            'template_name' => data_get($payload, 'template.name'),
            'template_language' => data_get($payload, 'template.language.code'),
            'message_body' => self::extractOutboundSummary($payload),
            'send_payload' => $payload,
            'send_response' => $response,
            'accepted_at' => $now,
            'last_status_at' => $now,
        ];

        if ($messageId) {
            $log = WhatsappMessageLog::firstOrNew(['message_id' => $messageId]);
            $log->fill($attributes);
            $log->save();

            return $log;
        }

        return WhatsappMessageLog::create($attributes);
    }

    public static function recordStatusUpdate(?WhatsappConfig $config, array $status, array $webhookPayload): WhatsappMessageLog
    {
        $messageId = (string) data_get($status, 'id', '');
        $now = now();
        $statusValue = (string) data_get($status, 'status', 'unknown');
        $error = Arr::first((array) data_get($status, 'errors', []));

        // #region debug-point B:record-status-update
        self::dbg('record-status-update', 'B', [
            'message_id' => $messageId,
            'status' => $statusValue,
            'recipient_id' => data_get($status, 'recipient_id'),
            'phone_number_id' => data_get($webhookPayload, 'metadata.phone_number_id'),
            'config_found' => $config !== null,
            'errors' => data_get($status, 'errors', []),
        ], '[DEBUG] record status update');
        // #endregion

        $log = $messageId !== ''
            ? WhatsappMessageLog::firstOrNew(['message_id' => $messageId])
            : new WhatsappMessageLog();

        $log->fill(array_filter([
            'branch_id' => $config?->branch_id,
            'whatsapp_config_id' => $config?->id,
            'phone_number_id' => data_get($webhookPayload, 'metadata.phone_number_id') ?: $config?->phone_number_id,
            'display_phone_number' => data_get($webhookPayload, 'metadata.display_phone_number') ?: $config?->phone_number_oficial,
            'contact_phone' => data_get($status, 'recipient_id'),
            'direction' => 'outbound',
            'event_type' => 'status_update',
            'status' => $statusValue,
            'error_code' => $error ? (string) data_get($error, 'code') : null,
            'error_message' => $error
                ? ((string) (data_get($error, 'title') ?: 'Error de WhatsApp') . ': ' . (string) data_get($error, 'message', ''))
                : null,
            'webhook_payload' => $webhookPayload,
            'webhook_received_at' => $now,
            'last_status_at' => $now,
        ], static fn ($value) => $value !== null));

        self::applyStatusTimestamp($log, $statusValue, $now);
        $log->save();

        return $log;
    }

    public static function recordIncomingMessage(?WhatsappConfig $config, array $message, array $contact, array $webhookPayload): WhatsappMessageLog
    {
        $messageId = (string) data_get($message, 'id', '');
        $now = now();

        // #region debug-point A:record-incoming-message
        self::dbg('record-incoming-message', 'A', [
            'message_id' => $messageId,
            'from' => data_get($message, 'from'),
            'type' => data_get($message, 'type'),
            'phone_number_id' => data_get($webhookPayload, 'metadata.phone_number_id'),
            'config_found' => $config !== null,
        ], '[DEBUG] record incoming message');
        // #endregion

        $log = $messageId !== ''
            ? WhatsappMessageLog::firstOrNew(['message_id' => $messageId])
            : new WhatsappMessageLog();

        $log->fill(array_filter([
            'branch_id' => $config?->branch_id,
            'whatsapp_config_id' => $config?->id,
            'phone_number_id' => data_get($webhookPayload, 'metadata.phone_number_id') ?: $config?->phone_number_id,
            'display_phone_number' => data_get($webhookPayload, 'metadata.display_phone_number') ?: $config?->phone_number_oficial,
            'contact_phone' => data_get($message, 'from'),
            'direction' => 'inbound',
            'event_type' => 'incoming_message',
            'message_type' => data_get($message, 'type'),
            'status' => 'received',
            'message_body' => self::extractInboundSummary($message, $contact),
            'webhook_payload' => $webhookPayload,
            'webhook_received_at' => $now,
            'last_status_at' => $now,
        ], static fn ($value) => $value !== null));

        $log->save();

        return $log;
    }

    protected static function applyStatusTimestamp(WhatsappMessageLog $log, string $status, $now): void
    {
        if ($status === 'sent' && !$log->sent_at) {
            $log->sent_at = $now;
        }

        if ($status === 'delivered' && !$log->delivered_at) {
            $log->delivered_at = $now;
        }

        if ($status === 'read' && !$log->read_at) {
            $log->read_at = $now;
        }

        if ($status === 'failed') {
            $log->failed_at = $now;
        }
    }

    protected static function extractOutboundSummary(array $payload): ?string
    {
        $type = data_get($payload, 'type');

        if ($type === 'template') {
            $template = data_get($payload, 'template.name');
            $language = data_get($payload, 'template.language.code');

            return trim("Template {$template} ({$language})");
        }

        if ($type === 'text') {
            return data_get($payload, 'text.body');
        }

        return $type ? "Mensaje {$type}" : null;
    }

    protected static function extractInboundSummary(array $message, array $contact): ?string
    {
        $type = (string) data_get($message, 'type', '');

        return match ($type) {
            'text' => data_get($message, 'text.body'),
            'button' => data_get($message, 'button.text'),
            'interactive' => data_get($message, 'interactive.button_reply.title')
                ?: data_get($message, 'interactive.list_reply.title')
                ?: 'Respuesta interactiva',
            'image' => data_get($message, 'image.caption') ?: 'Imagen recibida',
            'document' => data_get($message, 'document.filename') ?: 'Documento recibido',
            'audio' => 'Audio recibido',
            'video' => data_get($message, 'video.caption') ?: 'Video recibido',
            'sticker' => 'Sticker recibido',
            'location' => 'Ubicacion recibida',
            'contacts' => 'Contacto recibido',
            default => data_get($contact, 'profile.name')
                ? 'Mensaje de ' . data_get($contact, 'profile.name')
                : ($type !== '' ? "Mensaje {$type}" : 'Mensaje recibido'),
        };
    }

    // #region debug-point dbg-helper
    protected static function dbg(string $pointId, string $hypothesisId, array $data, string $msg): void
    {
        try {
            $envPath = base_path('.dbg/whatsapp-accepted-only.env');
            $debugUrl = null;
            $sessionId = 'whatsapp-accepted-only';
            if (is_file($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($lines as $line) {
                    if (str_starts_with($line, 'DEBUG_SERVER_URL=')) {
                        $debugUrl = trim(substr($line, strlen('DEBUG_SERVER_URL=')));
                    }
                    if (str_starts_with($line, 'DEBUG_SESSION_ID=')) {
                        $sessionId = trim(substr($line, strlen('DEBUG_SESSION_ID=')));
                    }
                }
            }
            if (!$debugUrl) {
                return;
            }

            \Illuminate\Support\Facades\Http::timeout(1)->asJson()->post($debugUrl, [
                'sessionId' => $sessionId,
                'runId' => 'pre-fix',
                'hypothesisId' => $hypothesisId,
                'pointId' => $pointId,
                'location' => 'WhatsappMessageLogService',
                'msg' => $msg,
                'ts' => round(microtime(true) * 1000),
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
        }
    }
    // #endregion
}
