<?php
$url = 'http://76.13.26.221:8080/swagger/doc.json';
$json = file_get_contents($url);
$data = json_decode($json, true);

if (isset($data['paths'])) {
    foreach ($data['paths'] as $path => $methods) {
        if (stripos($path, 'instance') !== false || stripos($path, 'session') !== false || stripos($path, 'connect') !== false || stripos($path, 'qr') !== false) {
            echo "Path: $path\n";
            foreach ($methods as $method => $details) {
                echo "  Method: $method\n";
                echo "  Summary: " . ($details['summary'] ?? '') . "\n";
            }
        }
    }
}
