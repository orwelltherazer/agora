<?php

require __DIR__ . '/vendor/autoload.php';

$dbConfig = require __DIR__ . '/config/database.php';
$db = new Agora\Services\Database($dbConfig);

$settings = $db->fetchAll('SELECT * FROM settings');
print_r($settings);
