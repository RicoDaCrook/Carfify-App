<?php
// Delegate all requests to the main application entry point
chdir(dirname(__DIR__));
require_once __DIR__ . '/../index.php';
