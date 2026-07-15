<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Promotion;
use App\Models\EvoWhatsappConfig;
use App\Services\WhatsappMessageLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsappPromotionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 60, 120];

    protected Promotion $promotion;
    protected Customer $customer;
    protected ?int $promotionLogId;

    /**
     * Create a new job instance.
     */
    public function __construct(Promotion $promotion, Customer $customer, ?int $promotionLogId = null)
    {
        $this->promotion = $promotion;
        $this->customer = $customer;
        $this->promotionLogId = $promotionLogId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logRecord = $this->promotionLogId ? \App\Models\PromotionLog::find($this->promotionLogId) : null;

        $config = EvoWhatsappConfig::where('branch_id', $this->promotion->branch_id)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            echo "[SendWhatsappPromotionJob] Aborting: WhatsApp is not configured for branch ID " . $this->promotion->branch_id . "\n";
            Log::error("SendWhatsappPromotionJob failed: WhatsApp is not configured for branch ID " . $this->promotion->branch_id);
            if ($logRecord) $logRecord->update(['status' => 'failed', 'error_message' => 'WhatsApp is not configured']);
            return;
        }

        if (!$config->instance_name || !in_array($config->status, ['connected', 'open', 'connecting'])) {
            echo "[SendWhatsappPromotionJob] Aborting: Instance not connected. Current status: " . $config->status . "\n";
            Log::error("SendWhatsappPromotionJob failed: WhatsApp instance is not connected for branch ID " . $this->promotion->branch_id . ". Current status: " . $config->status);
            if ($logRecord) $logRecord->update(['status' => 'failed', 'error_message' => 'Instance not connected. Current status: ' . $config->status]);
            return;
        }

        // Format phone number
        $phone = preg_replace('/[^0-9]/', '', $this->customer->phone);
        if (strlen($phone) === 10) {
            $phone = '57' . $phone; // Default to Colombia if no country code
        }
        
        $messageText = $this->promotion->message;
        if ($this->promotion->button_url) {
            $messageText .= "\n\n" . $this->promotion->button_url;
        }

        $serverUrl = rtrim(config('services.evo_whatsapp.server_url'), '/');
        $globalApiKey = config('services.evo_whatsapp.global_api_key');
        $apiUrl = $serverUrl . '/message/sendText/' . $config->instance_name;

        echo "[SendWhatsappPromotionJob] Sending to $phone via $apiUrl...\n";

        $response = Http::withHeaders([
            'apikey' => $globalApiKey,
            'Content-Type' => 'application/json',
        ])->post($apiUrl, [
            'number' => $phone,
            'text' => $messageText
        ]);

        if ($response->successful()) {
            echo "[SendWhatsappPromotionJob] SUCCESS! Response: " . $response->body() . "\n";
            Log::info("Evolution API sent promotion {$this->promotion->id} to {$phone}", [
                'api_response' => $response->json(),
                'customer_id' => $this->customer->id
            ]);
            if ($logRecord) $logRecord->update(['status' => 'sent', 'sent_at' => now()]);
        } else {
            echo "[SendWhatsappPromotionJob] ERROR! Status: " . $response->status() . " Body: " . $response->body() . "\n";
            Log::error("Evolution API failed to send promotion {$this->promotion->id} to {$phone}: " . $response->body());
            if ($logRecord) $logRecord->update(['status' => 'failed', 'error_message' => $response->body()]);
            
            // Retry if it's a server error
            if ($response->serverError() || $response->status() == 429 || $response->status() == 400 || $response->status() == 401) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 120);
            }
        }
    }
}
