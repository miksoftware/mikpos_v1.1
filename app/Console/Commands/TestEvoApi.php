<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\EvoWhatsappConfig;

class TestEvoApi extends Command
{
    protected $signature = 'test:evo';

    public function handle()
    {
        $serverUrl = config('services.evo_whatsapp.server_url');
        $globalKey = config('services.evo_whatsapp.global_api_key');
        
        $this->info("Server: $serverUrl");
        
        $config = EvoWhatsappConfig::first();
        if (!$config) {
            $this->error("No config found");
            return;
        }
        
        $this->info("Instance: " . $config->instance_name);
        $this->info("Token: " . $config->instance_token);
        
        $this->info("Testing GET /instance/qr with instance token...");
        $response = Http::withHeaders([
            'apikey' => $config->instance_token,
        ])->get($serverUrl . '/instance/qr');
        
        $this->info("Status: " . $response->status());
        $this->info("Body: " . substr($response->body(), 0, 100));

        $this->info("Testing POST /instance/connect with global key...");
        $response2 = Http::withHeaders([
            'apikey' => $globalKey,
        ])->post($serverUrl . '/instance/connect', ['instance' => ['immediate' => true]]);
        
        $this->info("Status2: " . $response2->status());
        $this->info("Body2: " . substr($response2->body(), 0, 100));
        
        $this->info("Testing GET /instance/connect/{instance} with global key...");
        $response3 = Http::withHeaders([
            'apikey' => $globalKey,
        ])->get($serverUrl . '/instance/connect/' . $config->instance_name);
        
        $this->info("Status3: " . $response3->status());
        $this->info("Body3: " . substr($response3->body(), 0, 100));
    }
}
