<?php
// Zeige ALLE Fehler an
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
echo json_encode([
    "status" => "debug", 
    "message" => "API Check", 
    "server" => $_SERVER['REQUEST_URI'],
    "method" => $_SERVER['REQUEST_METHOD']
]);
