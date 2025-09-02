<?php
namespace Core;

use Config\AppConfig;

class App
{
    private $config;
    private $router;

    public function __construct(AppConfig $config)
    {
        $this->config = $config;
        $this->router = new Router();
        $this->setupErrorHandling();
    }

    private function setupErrorHandling(): void
    {
        if ($this->config->get('debug', false)) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }

    public function run(): void
    {
        $this->router->dispatch();
    }
}