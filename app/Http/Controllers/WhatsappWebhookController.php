<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConfig;
use App\Services\WhatsappMessageLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        // #region debug-point A:webhook-verify-entry
        $this->dbg('webhook-verify-entry', 'A', [
            'mode' => $mode,
            'token_prefix' => substr($token, 0, 8),
            'has_challenge' => $challenge !== '',
            'ip' => $request->ip(),
        ], '[DEBUG] webhook verify request');
        // #endregion

        if ($mode !== 'subscribe' || $token !== (string) config('services.whatsapp.webhook_verify_token')) {
            // #region debug-point A:webhook-verify-rejected
            $this->dbg('webhook-verify-rejected', 'A', [
                'mode' => $mode,
                'token_prefix' => substr($token, 0, 8),
                'ip' => $request->ip(),
            ], '[DEBUG] webhook verify rejected');
            // #endregion
            Log::warning('Whatsapp webhook verification failed', [
                'mode' => $mode,
                'token_prefix' => substr($token, 0, 8),
                'ip' => $request->ip(),
            ]);

            abort(403, 'Token de verificacion invalido.');
        }

        Log::info('Whatsapp webhook verified', [
            'ip' => $request->ip(),
        ]);

        // #region debug-point A:webhook-verify-accepted
        $this->dbg('webhook-verify-accepted', 'A', [
            'ip' => $request->ip(),
        ], '[DEBUG] webhook verify accepted');
        // #endregion

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request)
    {
        // #region debug-point A:webhook-receive-entry
        $payload = $request->all();
        $entries = (array) data_get($payload, 'entry', []);
        $changes = [];
        foreach ($entries as $entry) {
            $changes = array_merge($changes, (array) data_get($entry, 'changes', []));
        }
        $this->dbg('webhook-receive-entry', 'A', [
            'ip' => $request->ip(),
            'entries_count' => count($entries),
            'changes_count' => count($changes),
            'has_signature' => $request->header('X-Hub-Signature-256') !== null,
        ], '[DEBUG] webhook receive entry');
        // #endregion

        if (!$this->hasValidSignature($request)) {
            // #region debug-point D:webhook-signature-rejected
            $this->dbg('webhook-signature-rejected', 'D', [
                'ip' => $request->ip(),
                'signature_prefix' => substr((string) $request->header('X-Hub-Signature-256', ''), 0, 24),
            ], '[DEBUG] webhook signature rejected');
            // #endregion
            Log::warning('Whatsapp webhook signature rejected', [
                'ip' => $request->ip(),
            ]);

            abort(403, 'Firma invalida.');
        }

        Log::info('Whatsapp webhook received', [
            'payload' => $payload,
        ]);

        foreach ((array) data_get($payload, 'entry', []) as $entry) {
            foreach ((array) data_get($entry, 'changes', []) as $change) {
                $value = (array) data_get($change, 'value', []);
                $phoneNumberId = (string) data_get($value, 'metadata.phone_number_id', '');
                $config = $phoneNumberId !== ''
                    ? WhatsappConfig::where('phone_number_id', $phoneNumberId)->first()
                    : null;

                // #region debug-point A:webhook-change-received
                $this->dbg('webhook-change-received', 'A', [
                    'phone_number_id' => $phoneNumberId,
                    'has_config' => $config !== null,
                    'statuses_count' => count((array) data_get($value, 'statuses', [])),
                    'messages_count' => count((array) data_get($value, 'messages', [])),
                ], '[DEBUG] webhook change received');
                // #endregion

                foreach ((array) data_get($value, 'statuses', []) as $status) {
                    // #region debug-point B:webhook-status-item
                    $this->dbg('webhook-status-item', 'B', [
                        'message_id' => data_get($status, 'id'),
                        'status' => data_get($status, 'status'),
                        'recipient_id' => data_get($status, 'recipient_id'),
                        'errors' => data_get($status, 'errors', []),
                    ], '[DEBUG] webhook status item');
                    // #endregion
                    WhatsappMessageLogService::recordStatusUpdate($config, (array) $status, $value);
                }

                $contactsByWaId = [];
                foreach ((array) data_get($value, 'contacts', []) as $contact) {
                    $waId = (string) data_get($contact, 'wa_id', '');
                    if ($waId !== '') {
                        $contactsByWaId[$waId] = (array) $contact;
                    }
                }

                foreach ((array) data_get($value, 'messages', []) as $message) {
                    $messageData = (array) $message;
                    $from = (string) data_get($messageData, 'from', '');
                    $contact = $contactsByWaId[$from] ?? [];

                    // #region debug-point A:webhook-message-item
                    $this->dbg('webhook-message-item', 'A', [
                        'message_id' => data_get($messageData, 'id'),
                        'from' => $from,
                        'type' => data_get($messageData, 'type'),
                    ], '[DEBUG] webhook inbound message item');
                    // #endregion

                    WhatsappMessageLogService::recordIncomingMessage($config, $messageData, $contact, $value);
                }
            }
        }

        return response()->json(['received' => true]);
    }

    protected function hasValidSignature(Request $request): bool
    {
        $secret = (string) config('services.whatsapp.webhook_app_secret');
        if ($secret === '') {
            return true;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        if (!str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    // #region debug-point dbg-helper
    protected function dbg(string $pointId, string $hypothesisId, array $data, string $msg): void
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
                'location' => 'WhatsappWebhookController',
                'msg' => $msg,
                'ts' => round(microtime(true) * 1000),
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
        }
    }
    // #endregion
}
