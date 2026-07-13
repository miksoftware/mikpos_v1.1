<?php
$json = json_decode(file_get_contents('swagger_parsed.json'), true);
echo json_encode($json['/instance/qr']['get']['parameters'] ?? [], JSON_PRETTY_PRINT);
