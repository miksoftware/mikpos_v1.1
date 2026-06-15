<?php

namespace App\Console\Commands;

use App\Models\WhatsappConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WhatsappDiagnostic extends Command
{
    protected $signature = 'whatsapp:diagnostic
        {--branch= : Branch ID to diagnose (default: first active config)}
        {--send-test= : Phone number to send a hello_world test message to}
        {--send-mikpos= : Phone number to send the mikpos template test to}';

    protected $description = 'Run a full diagnostic of the WhatsApp Business API configuration';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║       DIAGNÓSTICO COMPLETO DE WHATSAPP BUSINESS API         ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');

        // 1. Load config
        $branchId = $this->option('branch');
        $config = $branchId
            ? WhatsappConfig::where('branch_id', $branchId)->where('is_active', true)->first()
            : WhatsappConfig::where('is_active', true)->first();

        if (!$config) {
            $this->error('❌ No se encontró una configuración activa de WhatsApp.');
            return 1;
        }

        $token = trim($config->token_permanente);
        $version = trim($config->api_version);
        $phoneNumberId = trim($config->phone_number_id);
        $wabaId = trim($config->waba_id);
        $templateName = trim($config->template_name ?: 'mikpos');
        $templateLanguage = trim($config->template_language ?: 'es_CO');

        $this->info("📋 Configuración cargada:");
        $this->table(['Campo', 'Valor'], [
            ['Branch ID', $config->branch_id],
            ['Phone Number ID', $phoneNumberId],
            ['WABA ID', $wabaId],
            ['API Version', $version],
            ['Número oficial', $config->phone_number_oficial],
            ['Template', "{$templateName} ({$templateLanguage})"],
            ['Token (primeros 12)', substr($token, 0, 12) . '...'],
        ]);

        $issues = [];
        $warnings = [];

        // ─── CHECK 1: Phone Number Status ─────────────────────────────────────
        $this->newLine();
        $this->info('━━━ 1. ESTADO DEL NÚMERO DE TELÉFONO ━━━');

        $phoneResponse = Http::withToken($token)
            ->acceptJson()
            ->get("https://graph.facebook.com/{$version}/{$phoneNumberId}", [
                'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,name_status,status,platform_type,throughput,is_official_business_account,account_mode,certificate,health_status',
            ]);

        if ($phoneResponse->successful()) {
            $phone = $phoneResponse->json();
            $this->table(['Campo', 'Valor'], [
                ['ID', data_get($phone, 'id', 'N/A')],
                ['Número', data_get($phone, 'display_phone_number', 'N/A')],
                ['Nombre verificado', data_get($phone, 'verified_name', 'N/A')],
                ['Estado', data_get($phone, 'status', 'N/A')],
                ['Calidad', data_get($phone, 'quality_rating', 'N/A')],
                ['Verificación código', data_get($phone, 'code_verification_status', 'N/A')],
                ['Estado del nombre', data_get($phone, 'name_status', 'N/A')],
                ['Plataforma', data_get($phone, 'platform_type', 'N/A')],
                ['Cuenta oficial', data_get($phone, 'is_official_business_account') ? 'Sí' : 'No'],
                ['Modo de cuenta', data_get($phone, 'account_mode', 'N/A')],
                ['Health Status', json_encode(data_get($phone, 'health_status', 'N/A'))],
            ]);

            $status = data_get($phone, 'status');
            $quality = data_get($phone, 'quality_rating');

            if ($status !== 'CONNECTED') {
                $issues[] = "⚠ El número NO está en estado CONNECTED (actual: {$status}). El número debe estar registrado y conectado.";
                $this->error("  ❌ Estado: {$status} — Debería ser CONNECTED");
            } else {
                $this->info("  ✅ Estado: CONNECTED");
            }

            if ($quality === 'RED' || $quality === 'FLAGGED') {
                $issues[] = "⚠ La calidad del número es {$quality}. Meta puede restringir el envío.";
                $this->error("  ❌ Calidad: {$quality}");
            } else {
                $this->info("  ✅ Calidad: {$quality}");
            }
        } else {
            $error = $phoneResponse->json();
            $issues[] = "❌ No se pudo consultar el número: " . data_get($error, 'error.message', 'Error desconocido');
            $this->error("  ❌ Error: " . json_encode($error));
        }

        // ─── CHECK 2: WABA Status ────────────────────────────────────────────
        $this->newLine();
        $this->info('━━━ 2. ESTADO DE LA CUENTA WHATSAPP BUSINESS (WABA) ━━━');

        $wabaResponse = Http::withToken($token)
            ->acceptJson()
            ->get("https://graph.facebook.com/{$version}/{$wabaId}", [
                'fields' => 'id,name,currency,timezone_id,message_template_namespace,account_review_status,business_verification_status,on_behalf_of_business_info',
            ]);

        if ($wabaResponse->successful()) {
            $waba = $wabaResponse->json();
            $this->table(['Campo', 'Valor'], [
                ['ID', data_get($waba, 'id', 'N/A')],
                ['Nombre', data_get($waba, 'name', 'N/A')],
                ['Moneda', data_get($waba, 'currency', 'N/A')],
                ['Revisión de cuenta', data_get($waba, 'account_review_status', 'N/A')],
                ['Verificación del negocio', data_get($waba, 'business_verification_status', 'N/A')],
                ['Namespace templates', data_get($waba, 'message_template_namespace', 'N/A')],
                ['Negocio', json_encode(data_get($waba, 'on_behalf_of_business_info', 'N/A'))],
            ]);

            $reviewStatus = data_get($waba, 'account_review_status');
            $bizVerification = data_get($waba, 'business_verification_status');

            if ($reviewStatus && $reviewStatus !== 'APPROVED') {
                $issues[] = "❌ La cuenta WABA NO está aprobada (estado: {$reviewStatus}). Meta no entregará mensajes.";
                $this->error("  ❌ Revisión de cuenta: {$reviewStatus}");
            } else {
                $this->info("  ✅ Revisión de cuenta: " . ($reviewStatus ?: 'OK'));
            }

            if ($bizVerification && $bizVerification !== 'verified') {
                $warnings[] = "⚠ La verificación del negocio no está completa (estado: {$bizVerification}). Esto puede limitar el envío.";
                $this->warn("  ⚠ Verificación del negocio: {$bizVerification}");
            } else {
                $this->info("  ✅ Verificación del negocio: " . ($bizVerification ?: 'N/A'));
            }
        } else {
            $error = $wabaResponse->json();
            $issues[] = "❌ No se pudo consultar la WABA: " . data_get($error, 'error.message', 'Error desconocido');
            $this->error("  ❌ Error: " . json_encode($error));
        }

        // ─── CHECK 3: App Subscriptions ───────────────────────────────────────
        $this->newLine();
        $this->info('━━━ 3. SUSCRIPCIONES DE LA APP (WABA) ━━━');

        $subsResponse = Http::withToken($token)
            ->acceptJson()
            ->get("https://graph.facebook.com/{$version}/{$wabaId}/subscribed_apps");

        if ($subsResponse->successful()) {
            $subs = data_get($subsResponse->json(), 'data', []);
            if (empty($subs)) {
                $issues[] = "❌ No hay apps suscritas a la WABA. Los webhooks NO se recibirán.";
                $this->error("  ❌ No hay apps suscritas a esta WABA");
            } else {
                foreach ($subs as $sub) {
                    $this->info("  ✅ App suscrita: " . data_get($sub, 'whatsapp_business_api_data.name', 'Sin nombre'));
                    $link = data_get($sub, 'whatsapp_business_api_data.link', '');
                    if ($link) {
                        $this->line("     Link: {$link}");
                    }
                }
            }
        } else {
            $this->warn("  ⚠ No se pudieron consultar las suscripciones: " . json_encode($subsResponse->json()));
        }

        // ─── CHECK 4: Template Status ─────────────────────────────────────────
        $this->newLine();
        $this->info("━━━ 4. ESTADO DE LA PLANTILLA '{$templateName}' ━━━");

        $templatesResponse = Http::withToken($token)
            ->acceptJson()
            ->get("https://graph.facebook.com/{$version}/{$wabaId}/message_templates", [
                'name' => $templateName,
                'fields' => 'name,status,language,category,quality_score,rejected_reason,components',
            ]);

        if ($templatesResponse->successful()) {
            $templates = data_get($templatesResponse->json(), 'data', []);
            if (empty($templates)) {
                $issues[] = "❌ La plantilla '{$templateName}' NO existe en la WABA.";
                $this->error("  ❌ Plantilla '{$templateName}' no encontrada");
            } else {
                foreach ($templates as $tpl) {
                    $tplStatus = data_get($tpl, 'status', 'UNKNOWN');
                    $tplLang = data_get($tpl, 'language', 'N/A');
                    $tplCategory = data_get($tpl, 'category', 'N/A');
                    $qualityScore = data_get($tpl, 'quality_score.score', 'N/A');
                    $rejectedReason = data_get($tpl, 'rejected_reason', '');

                    $this->table(['Campo', 'Valor'], [
                        ['Nombre', data_get($tpl, 'name')],
                        ['Estado', $tplStatus],
                        ['Idioma', $tplLang],
                        ['Categoría', $tplCategory],
                        ['Calidad', $qualityScore],
                        ['Razón rechazo', $rejectedReason ?: 'N/A'],
                    ]);

                    if ($tplStatus !== 'APPROVED') {
                        $issues[] = "❌ La plantilla '{$templateName}' ({$tplLang}) tiene estado '{$tplStatus}', no APPROVED.";
                        $this->error("  ❌ Estado: {$tplStatus}");
                    } else {
                        $this->info("  ✅ Plantilla aprobada ({$tplLang})");
                    }

                    if ($tplLang !== $templateLanguage) {
                        $warnings[] = "⚠ El idioma de la plantilla ({$tplLang}) no coincide con el configurado ({$templateLanguage}).";
                        $this->warn("  ⚠ Idioma en config: {$templateLanguage}, en Meta: {$tplLang}");
                    }
                }
            }
        } else {
            $this->warn("  ⚠ No se pudieron consultar las plantillas: " . json_encode($templatesResponse->json()));
        }

        // ─── CHECK 5: Messaging Limits ────────────────────────────────────────
        $this->newLine();
        $this->info('━━━ 5. LÍMITES DE MENSAJERÍA ━━━');

        $limitsResponse = Http::withToken($token)
            ->acceptJson()
            ->get("https://graph.facebook.com/{$version}/{$wabaId}", [
                'fields' => 'messaging_limit_tier',
            ]);

        if ($limitsResponse->successful()) {
            $tier = data_get($limitsResponse->json(), 'messaging_limit_tier', 'N/A');
            $this->info("  Tier de mensajería: {$tier}");

            if ($tier === 'TIER_NOT_SET' || $tier === 'N/A') {
                $warnings[] = "⚠ No hay tier de mensajería configurado. Esto puede significar que la cuenta no está lista para enviar.";
                $this->warn("  ⚠ Sin tier configurado");
            }
        } else {
            $this->warn("  ⚠ No se pudo consultar: " . json_encode($limitsResponse->json()));
        }

        // ─── CHECK 6: App Info (mode) ─────────────────────────────────────────
        $this->newLine();
        $this->info('━━━ 6. INFORMACIÓN DE LA APP ━━━');

        // Try to get app info from the token
        $appResponse = Http::withToken($token)
            ->acceptJson()
            ->get("https://graph.facebook.com/{$version}/app", [
                'fields' => 'id,name,link',
            ]);

        if ($appResponse->successful()) {
            $app = $appResponse->json();
            $this->table(['Campo', 'Valor'], [
                ['App ID', data_get($app, 'id', 'N/A')],
                ['Nombre', data_get($app, 'name', 'N/A')],
                ['Link', data_get($app, 'link', 'N/A')],
            ]);
        } else {
            $this->warn("  ⚠ No se pudo consultar la app: " . json_encode($appResponse->json()));
        }

        // ─── CHECK 7: Token Debug ─────────────────────────────────────────────
        $this->newLine();
        $this->info('━━━ 7. VALIDACIÓN DEL TOKEN ━━━');

        $tokenDebugResponse = Http::withToken($token)
            ->acceptJson()
            ->get("https://graph.facebook.com/debug_token", [
                'input_token' => $token,
            ]);

        if ($tokenDebugResponse->successful()) {
            $tokenData = data_get($tokenDebugResponse->json(), 'data', []);
            $this->table(['Campo', 'Valor'], [
                ['App ID', data_get($tokenData, 'app_id', 'N/A')],
                ['Tipo', data_get($tokenData, 'type', 'N/A')],
                ['Válido', data_get($tokenData, 'is_valid') ? 'Sí' : 'No'],
                ['Expira', data_get($tokenData, 'expires_at', 0) == 0 ? 'Nunca' : date('Y-m-d H:i:s', data_get($tokenData, 'expires_at'))],
                ['Scopes', implode(', ', (array) data_get($tokenData, 'scopes', []))],
                ['Granular Scopes', json_encode(data_get($tokenData, 'granular_scopes', []))],
            ]);

            if (!data_get($tokenData, 'is_valid')) {
                $issues[] = "❌ El token NO es válido.";
                $this->error("  ❌ Token inválido");
            } else {
                $this->info("  ✅ Token válido");
            }

            $scopes = (array) data_get($tokenData, 'scopes', []);
            if (!in_array('whatsapp_business_messaging', $scopes) && !in_array('whatsapp_business_management', $scopes)) {
                $warnings[] = "⚠ El token podría no tener los permisos necesarios de WhatsApp Business.";
                $this->warn("  ⚠ Permisos de WhatsApp no detectados en scopes principales");
            }
        } else {
            $this->warn("  ⚠ No se pudo depurar el token: " . json_encode($tokenDebugResponse->json()));
        }

        // ─── CHECK 8: Send hello_world test ───────────────────────────────────
        $testPhone = $this->option('send-test');
        if ($testPhone) {
            $this->newLine();
            $this->info('━━━ 8. ENVÍO DE PRUEBA (hello_world) ━━━');

            $sanitizedPhone = preg_replace('/\D+/', '', $testPhone);
            $this->info("  Enviando hello_world a: {$sanitizedPhone}");

            $testPayload = [
                'messaging_product' => 'whatsapp',
                'to' => $sanitizedPhone,
                'type' => 'template',
                'template' => [
                    'name' => 'hello_world',
                    'language' => ['code' => 'en_US'],
                ],
            ];

            $testResponse = Http::withToken($token)
                ->acceptJson()
                ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", $testPayload);

            $testData = $testResponse->json();
            $this->info("  Status HTTP: " . $testResponse->status());
            $this->info("  Respuesta: " . json_encode($testData, JSON_PRETTY_PRINT));

            if ($testResponse->successful()) {
                $msgStatus = data_get($testData, 'messages.0.message_status', 'N/A');
                $msgId = data_get($testData, 'messages.0.id', 'N/A');
                $this->info("  ✅ Message ID: {$msgId}");
                $this->info("  ✅ Message Status: {$msgStatus}");
                $this->newLine();
                $this->warn("  👉 Revisa tu teléfono ({$sanitizedPhone}). Si NO llega el hello_world,");
                $this->warn("     el problema es de la cuenta de Meta, no del código.");
            } else {
                $errorMsg = data_get($testData, 'error.message', 'Error desconocido');
                $errorCode = data_get($testData, 'error.code', 'N/A');
                $errorSubcode = data_get($testData, 'error.error_subcode', 'N/A');
                $this->error("  ❌ Error ({$errorCode}/{$errorSubcode}): {$errorMsg}");
            }
        }

        // ─── CHECK 9: Send mikpos test ────────────────────────────────────────
        $mikposPhone = $this->option('send-mikpos');
        if ($mikposPhone) {
            $this->newLine();
            $this->info("━━━ 9. ENVÍO DE PRUEBA ({$templateName}) ━━━");

            $sanitizedPhone = preg_replace('/\D+/', '', $mikposPhone);
            $this->info("  Enviando {$templateName} ({$templateLanguage}) a: {$sanitizedPhone}");

            $mikposPayload = [
                'messaging_product' => 'whatsapp',
                'to' => $sanitizedPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => $templateLanguage],
                ],
            ];

            $mikposResponse = Http::withToken($token)
                ->acceptJson()
                ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", $mikposPayload);

            $mikposData = $mikposResponse->json();
            $this->info("  Status HTTP: " . $mikposResponse->status());
            $this->info("  Respuesta: " . json_encode($mikposData, JSON_PRETTY_PRINT));

            if ($mikposResponse->successful()) {
                $msgStatus = data_get($mikposData, 'messages.0.message_status', 'N/A');
                $this->info("  ✅ Message Status: {$msgStatus}");
            } else {
                $errorMsg = data_get($mikposData, 'error.message', 'Error desconocido');
                $errorCode = data_get($mikposData, 'error.code', 'N/A');
                $this->error("  ❌ Error ({$errorCode}): {$errorMsg}");
            }
        }

        // ─── RESUMEN FINAL ────────────────────────────────────────────────────
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    RESUMEN DEL DIAGNÓSTICO                  ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');

        if (empty($issues) && empty($warnings)) {
            $this->info('  ✅ No se encontraron problemas. La configuración parece correcta.');
            $this->newLine();
            $this->info('  Si los mensajes siguen sin llegar, las causas restantes son:');
            $this->line('  1. La app no está en modo "Live" (verificar en App Dashboard → Configuración básica)');
            $this->line('  2. El número destino tiene WhatsApp pero bloqueó la línea de negocio');
            $this->line('  3. Meta tiene una cola interna retrasada (esperar 5-10 minutos)');
            $this->line('  4. La plantilla requiere parámetros (components) que no se están enviando');
        }

        if (!empty($issues)) {
            $this->newLine();
            $this->error('  PROBLEMAS ENCONTRADOS:');
            foreach ($issues as $i => $issue) {
                $this->error("  " . ($i + 1) . ". {$issue}");
            }
        }

        if (!empty($warnings)) {
            $this->newLine();
            $this->warn('  ADVERTENCIAS:');
            foreach ($warnings as $i => $warning) {
                $this->warn("  " . ($i + 1) . ". {$warning}");
            }
        }

        if (!$testPhone && !$mikposPhone) {
            $this->newLine();
            $this->info('  💡 Para enviar un mensaje de prueba directamente:');
            $this->line("     php artisan whatsapp:diagnostic --send-test=573086537828");
            $this->line("     php artisan whatsapp:diagnostic --send-mikpos=573086537828");
        }

        $this->newLine();

        return empty($issues) ? 0 : 1;
    }
}
