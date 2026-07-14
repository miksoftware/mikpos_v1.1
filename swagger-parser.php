<?php
$json = json_decode(file_get_contents('swagger.json'), true);

echo "=== SECURITY DEFINITIONS ===\n";
echo json_encode($json['securityDefinitions'] ?? $json['components']['securitySchemes'] ?? 'NOT FOUND', JSON_PRETTY_PRINT) . "\n\n";

echo "=== GLOBAL SECURITY ===\n";
echo json_encode($json['security'] ?? 'NOT FOUND', JSON_PRETTY_PRINT) . "\n\n";

echo "=== INFO ===\n";
echo json_encode($json['info'] ?? 'NOT FOUND', JSON_PRETTY_PRINT) . "\n\n";

echo "=== BASE PATH ===\n";
echo json_encode($json['basePath'] ?? 'NOT FOUND', JSON_PRETTY_PRINT) . "\n\n";

echo "=== HOST ===\n";
echo json_encode($json['host'] ?? 'NOT FOUND', JSON_PRETTY_PRINT) . "\n\n";

// Check CreateStruct response schema
echo "=== CREATE INSTANCE RESPONSE (from definitions) ===\n";
$createDef = $json['definitions']['github_com_EvolutionAPI_evolution-go_pkg_instance_service.CreateStruct'] ?? 'NOT FOUND';
echo json_encode($createDef, JSON_PRETTY_PRINT) . "\n\n";

// Check ConnectStruct
echo "=== CONNECT STRUCT ===\n";
$connectDef = $json['definitions']['github_com_EvolutionAPI_evolution-go_pkg_instance_service.ConnectStruct'] ?? 'NOT FOUND';
echo json_encode($connectDef, JSON_PRETTY_PRINT) . "\n\n";

// Check all top-level keys
echo "=== TOP LEVEL KEYS ===\n";
echo implode(", ", array_keys($json)) . "\n";
