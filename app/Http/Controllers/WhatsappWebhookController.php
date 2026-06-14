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

        if ($mode !== 'subscribe' || $token !== (string) config('services.whatsapp.webhook_verify_token')) {
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

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request)
    {
        if (!$this->hasValidSignature($request)) {
            Log::warning('Whatsapp webhook signature rejected', [
                'ip' => $request->ip(),
            ]);

            abort(403, 'Firma invalida.');
        }

        $payload = $request->all();

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

                foreach ((array) data_get($value, 'statuses', []) as $status) {
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
}
