<?php
$url = 'http://76.13.26.221:8080';
$globalKey = '429683C4C977415CAAFCCE10F7D57E11';

$instanceName = 'test_' . uniqid();

// Create instance
$opts = [
    "http" => [
        "method" => "POST",
        "header" => "apikey: " . $globalKey . "\r\nContent-Type: application/json\r\n",
        "content" => json_encode([
            "name" => $instanceName,
            "token" => "token123",
            "qrcode" => true
        ])
    ]
];
$context = stream_context_create($opts);
$res = @file_get_contents($url . '/instance/create', false, $context);
echo "Create Response:\n" . $res . "\n\n";

if ($res) {
    // Try to get QR
    $optsToken = [
        "http" => [
            "method" => "GET",
            "header" => "apikey: token123\r\n"
        ]
    ];
    $contextToken = stream_context_create($optsToken);
    
    echo "Testing GET /instance/qr with instance token...\n";
    $resQR = @file_get_contents($url . '/instance/qr', false, $contextToken);
    echo "QR Response:\n" . substr($resQR, 0, 200) . "\n\n";

    echo "Testing GET /instance/qr with global token and instance in URL...\n";
    $optsGlobal = [
        "http" => [
            "method" => "GET",
            "header" => "apikey: " . $globalKey . "\r\n"
        ]
    ];
    $contextGlobal = stream_context_create($optsGlobal);
    $resQR2 = @file_get_contents($url . '/instance/qr/' . $instanceName, false, $contextGlobal);
    echo "QR Response2:\n" . substr($resQR2, 0, 200) . "\n\n";

    // Delete instance
    $optsDelete = [
        "http" => [
            "method" => "DELETE",
            "header" => "apikey: " . $globalKey . "\r\n"
        ]
    ];
    $contextDelete = stream_context_create($optsDelete);
    @file_get_contents($url . '/instance/delete/' . $instanceName, false, $contextDelete);
}
