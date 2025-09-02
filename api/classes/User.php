<?php
class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return [
                'success' => true,
                'user' => $user
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Ungültige Anmeldedaten'
        ];
    }
    
    public function register($data) {
        // Validierung
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Ungültige Email-Adresse'];
        }
        
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'error' => 'Passwort muss mindestens 6 Zeichen lang sein'];
        }
        
        // Prüfen ob Email existiert
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Email bereits registriert'];
        }
        
        // Benutzer erstellen
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, password, phone, address, role, created_at) 
            VALUES (?, ?, ?, ?, ?, 'customer', NOW())
        ");
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->execute([
            $data['name'],
            $data['email'],
            $hashedPassword,
            $data['phone'] ?? null,
            $data['address'] ?? null
        ]);
        
        $userId = $this->pdo->lastInsertId();
        
        return [
            'success' => true,
            'user' => [
                'id' => $userId,
                'email' => $data['email'],
                'name' => $data['name'],
                'role' => 'customer'
            ]
        ];
    }
}