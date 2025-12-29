<?php
namespace App\Models;

use App\Core\Model;

class Instrument extends Model {
    protected $table = 'instruments';

    public function create($data) {
        $sql = "INSERT INTO instruments (profile_id, title, description, type, status) 
                VALUES (:profile_id, :title, :description, :type, :status)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':profile_id' => $data['profile_id'],
            ':title'      => $data['title'],
            ':description'=> $data['description'] ?? '',
            ':type'       => $data['type'] ?? 'rubric',
            ':status'     => 'draft'
        ]);
        
        return $this->pdo->lastInsertId();
    }
}