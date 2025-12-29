<?php
namespace App\Models;

use App\Core\Model;

class Student extends Model {
    protected $table = 'students';

    // Buscar si ya existe un estudiante por su RUT
    public function findByRut($rut) {
        $sql = "SELECT * FROM students WHERE rut = :rut LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':rut' => $rut]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO students (rut, email, name, entry_year, active) 
                VALUES (:rut, :email, :name, :entry_year, :active)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':rut'        => $data['rut'],
            ':email'      => $data['email'] ?? null,
            ':name'       => $data['name'],
            ':entry_year' => $data['entry_year'] ?? date('Y'),
            ':active'     => $data['active'] ?? 1
        ]);
        
        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE students SET 
                rut = :rut, 
                email = :email, 
                name = :name, 
                entry_year = :entry_year,
                active = :active
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id'         => $id,
            ':rut'        => $data['rut'],
            ':email'      => $data['email'] ?? null,
            ':name'       => $data['name'],
            ':entry_year' => $data['entry_year'] ?? date('Y'),
            ':active'     => $data['active'] ?? 1
        ]);
    }
}