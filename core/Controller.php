<?php

class Controller
{
    protected $view;
    protected $db;
    protected $session;
    protected $config;

    public function __construct()
    {
        $this->view = View::getInstance();
        $this->db = Database::getInstance();
        $this->session = SessionManager::getInstance();
        $this->config = Config::getInstance();

        $this->view->set('base_url', $this->config->get('app.base_url'));
        $this->view->set('session', $this->session);
    }

    protected function redirect($url, $code = 302)
    {
        header("Location: {$url}", true, $code);
        exit;
    }

    protected function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $fieldRules = is_array($rule) ? $rule : explode('|', $rule);

            foreach ($fieldRules as $fieldRule) {
                $parts = explode(':', $fieldRule);
                $ruleName = $parts[0];
                $ruleValue = $parts[1] ?? null;

                $error = $this->applyRule($field, $value, $ruleName, $ruleValue);
                if ($error) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }

    private function applyRule($field, $value, $rule, $ruleValue)
    {
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    return "{$field} ist erforderlich";
                }
                break;
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$field} muss eine gültige E-Mail-Adresse sein";
                }
                break;
            case 'min':
                if (strlen($value) < (int)$ruleValue) {
                    return "{$field} muss mindestens {$ruleValue} Zeichen lang sein";
                }
                break;
            case 'max':
                if (strlen($value) > (int)$ruleValue) {
                    return "{$field} darf maximal {$ruleValue} Zeichen lang sein";
                }
                break;
            case 'numeric':
                if (!is_numeric($value)) {
                    return "{$field} muss eine Zahl sein";
                }
                break;
        }

        return null;
    }

    protected function withErrors($errors)
    {
        foreach ($errors as $field => $error) {
            $this->session->flash('errors.' . $field, $error);
        }
        return $this;
    }

    protected function withInput($input = null)
    {
        $data = $input ?? $_POST;
        $this->session->flash('old', $data);
        return $this;
    }

    protected function old($key, $default = '')
    {
        $old = $this->session->flash('old') ?? [];
        return $old[$key] ?? $default;
    }

    protected function error($key)
    {
        $errors = $this->session->flash('errors') ?? [];
        return $errors[$key] ?? null;
    }
}