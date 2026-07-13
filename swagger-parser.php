<?php
$json = json_decode(file_get_contents('swagger_parsed.json'), true);
echo json_encode($json['/instance/qr'], JSON_PRETTY_PRINT);
echo "\n====\n";
echo json_encode($json['/instance/connect'], JSON_PRETTY_PRINT);
