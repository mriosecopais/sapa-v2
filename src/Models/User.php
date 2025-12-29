<?php
namespace App\Models;

use App\Core\Model;

class User extends Model {
    protected $table = 'users';

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function create($data) {
        // Encriptar contraseña antes de guardar
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :pass, :role)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':pass' => $hash,
            ':role' => $data['role'] ?? 'student'
        ]);
        return $this->pdo->lastInsertId();
    }
}