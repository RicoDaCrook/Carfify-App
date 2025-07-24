<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

echo json_encode([
    "status" => "ok", 
    "message" => "PHP läuft auf Vercel!",
    "timestamp" => date("Y-m-d H:i:s"),
    "php_version" => PHP_VERSION
]);
