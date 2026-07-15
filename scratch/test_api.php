<?php
$ch = curl_init('http://76.13.26.221:8080/instance/create');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: cefd35f14d76651d19f18f55a352f027',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'test_php_2',
    'instanceName' => 'test_php_2',
    'token' => 'testtoken2',
    'qrcode' => true,
    'integration' => 'WHATSAPP-BAILEYS'
]));
$res = curl_exec($ch);
echo "STATUS: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\nBODY: " . $res . "\n";
curl_close($ch);
