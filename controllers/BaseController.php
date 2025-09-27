<?php

abstract class BaseController
{
    protected function render(string $template, array $data = []): void
    {
        $templatePath = __DIR__ . '/../templates/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new RuntimeException("Template {$template} nicht gefunden");
        }

        extract($data, EXTR_SKIP);
        require $templatePath;
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    protected function requireJsonPayload(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?? '', true);

        if (!is_array($data)) {
            $this->json([
                'error' => 'Ungültiges JSON übermittelt.'
            ], 400);
        }

        return $data;
    }
}
