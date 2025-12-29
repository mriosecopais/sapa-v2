<?php
namespace App\Models;

use App\Core\Model;

class Profile extends Model {
    protected $table = 'profiles';

    public function create($data) {
        $sql = "INSERT INTO profiles (code, name, career, faculty, description, has_licentiate) 
                VALUES (:code, :name, :career, :faculty, :description, :has_licentiate)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':code' => $data['code'],
            ':name' => $data['name'],
            ':career' => $data['career'],
            ':faculty' => $data['faculty'],
            ':description' => $data['description'] ?? null,
            ':has_licentiate' => $data['has_licentiate'] ?? 0
        ]);
        
        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE profiles SET 
                code = :code, 
                name = :name, 
                career = :career, 
                faculty = :faculty, 
                description = :description, 
                has_licentiate = :has_licentiate 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':code' => $data['code'],
            ':name' => $data['name'],
            ':career' => $data['career'],
            ':faculty' => $data['faculty'],
            ':description' => $data['description'] ?? null,
            ':has_licentiate' => $data['has_licentiate'] ?? 0
        ]);
    }
}