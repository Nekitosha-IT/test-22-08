<?php

declare(strict_types=1);

$db = require __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/utm_repository.php';

$name = 'Сникерс';
$ip = '10.0.0.100';
$port = 8086;
$enabled = true;

$stmt = $db->prepare("
    SELECT id
    FROM utms
    WHERE ip = :ip
      AND port = :port
    LIMIT 1
");

$stmt->execute([
    ':ip' => $ip,
    ':port' => $port
]);

$existing = $stmt->fetchColumn();

if ($existing !== false) {
    $id = (int)$existing;

    setUtmEnabled($db, $id, $enabled);

    echo "УТМ уже существует, повторно не добавляем.", PHP_EOL;
    echo "ID: ", $id, PHP_EOL;
} else {
    $id = addUtm(
        $db,
        $name,
        $ip,
        $port,
        null
    );

    echo "УТМ успешно добавлен в SQLite.", PHP_EOL;
    echo "ID: ", $id, PHP_EOL;
}

echo PHP_EOL;
echo "=== ТЕКУЩИЕ УТМ ===", PHP_EOL;

$utms = getAllUtms($db);

foreach ($utms as $utm) {
    echo
        "ID=", $utm['id'],
        " | ", $utm['name'],
        " | ", $utm['ip'],
        ":", $utm['port'],
        " | ENABLED=", $utm['enabled'],
        PHP_EOL;
}