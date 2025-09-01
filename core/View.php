<?php

class View
{
    private static $instance = null;
    private $basePath;
    private $data = [];

    private function __construct()
    {
        $this->basePath = __DIR__ . '/../views/';
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function render($template, $data = [])
    {
        $this->data = array_merge($this->data, $data);
        
        $templatePath = $this->basePath . str_replace('.', '/', $template) . '.php';
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template nicht gefunden: {$templatePath}");
        }

        extract($this->data);
        
        ob_start();
        include $templatePath;
        $content = ob_get_clean();
        
        return $content;
    }

    public function display($template, $data = [])
    {
        echo $this->render($template, $data);
    }

    public function layout($layout, $view, $data = [])
    {
        $content = $this->render($view, $data);
        $this->set('content', $content);
        $this->display($layout, $data);
    }

    public function exists($template)
    {
        $templatePath = $this->basePath . str_replace('.', '/', $template) . '.php';
        return file_exists($templatePath);
    }

    public function addGlobal($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}