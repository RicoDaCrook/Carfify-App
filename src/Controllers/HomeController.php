<?php
namespace Controllers;

use Config\AppConfig;

class HomeController
{
    public function index(): void
    {
        $config = AppConfig::getInstance();
        include __DIR__ . '/../Views/home.php';
    }
}