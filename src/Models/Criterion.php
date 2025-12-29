<?php
namespace App\Models;

use App\Core\Model;

class Criterion extends Model {
    protected $table = 'criteria';

    // Obtener criterios de un instrumento
    public function getByInstrument($instrumentId) {
        $sql = "SELECT * FROM criteria WHERE instrument_id = :iid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':iid' => $instrumentId]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO criteria (instrument_id, description, max_score, weight) 
                VALUES (:instrument_id, :description, :max_score, :weight)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':instrument_id' => $data['instrument_id'],
            ':description'   => $data['description'],
            ':max_score'     => $data['max_score'] ?? 7,
            ':weight'        => $data['weight'] ?? 1.0
        ]);
        
        return $this->pdo->lastInsertId();
    }
}