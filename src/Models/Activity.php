<?php
namespace App\Models;

use App\Core\Model;

class Activity extends Model {
    protected $table = 'activities';

    // Obtener actividades de un perfil ordenadas por semestre
    public function getByProfile($profileId) {
        $sql = "SELECT * FROM activities WHERE profile_id = :pid ORDER BY semester ASC, id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pid' => $profileId]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO activities 
                (profile_id, custom_id, name, semester, credits, type) 
                VALUES 
                (:profile_id, :custom_id, :name, :semester, :credits, :type)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':profile_id' => $data['profile_id'],
            ':custom_id'  => $data['custom_id'] ?? null, // Opcional (ej: MAT-101)
            ':name'       => $data['name'],
            ':semester'   => $data['semester'],
            ':credits'    => $data['credits'] ?? 0,
            ':type'       => $data['type'] ?? 'obligatoria'
        ]);
        
        return $this->pdo->lastInsertId();
    }
}