<?php
namespace App\Models;

use App\Core\Model;

class Competency extends Model {
    protected $table = 'competencies';

    // Obtener todas las competencias de un perfil
    public function getByProfile($profileId) {
        $sql = "SELECT * FROM competencies WHERE profile_id = :pid ORDER BY id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pid' => $profileId]);
        return $stmt->fetchAll();
    }

    public function getByScope($scope) {
        $sql = "SELECT * FROM competencies WHERE scope = :scope ORDER BY custom_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':scope' => $scope]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO competencies 
                (profile_id, custom_id, description, type, scope, level_1, level_2, level_3) 
                VALUES 
                (:profile_id, :custom_id, :description, :type, :scope, :level_1, :level_2, :level_3)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':profile_id' => $data['profile_id'], // Puede ser NULL ahora
            ':custom_id'  => $data['custom_id'],
            ':description'=> $data['description'],
            ':type'       => $data['type'],
            ':scope'      => $data['scope'] ?? 'specific',
            ':level_1'    => $data['level_1'] ?? null,
            ':level_2'    => $data['level_2'] ?? null,
            ':level_3'    => $data['level_3'] ?? null,
        ]);
        
        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE competencies SET 
                description = :description,
                level_1 = :level_1,
                level_2 = :level_2,
                level_3 = :level_3
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':description'=> $data['description'],
            ':level_1'    => $data['level_1'] ?? null,
            ':level_2'    => $data['level_2'] ?? null,
            ':level_3'    => $data['level_3'] ?? null,
        ]);
    }
}