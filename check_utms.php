<?php

$db = require __DIR__ . "/includes/database.php";

$rows = $db->query("
    SELECT
        id,
        name,
        ip,
        port,
        enabled,
        created_at,
        updated_at
    FROM utms
    ORDER BY id
")->fetchAll();

foreach ($rows as $row) {
    echo json_encode(
        $row,
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    ), PHP_EOL;
}
