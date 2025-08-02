<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Carfify\Router;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize router
$router = new Router();
$router->handleRequest();
