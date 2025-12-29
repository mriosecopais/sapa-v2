<?php
namespace App\Models;

use App\Core\Model;

class Grade extends Model {
    protected $table = 'grades';

    // Obtener historial de notas de un alumno
    public function getByStudent($studentId) {
        // Hacemos un JOIN para traer también el nombre de la asignatura
        $sql = "SELECT g.*, a.name as activity_name, a.custom_id as activity_code 
                FROM grades g
                JOIN activities a ON g.activity_id = a.id
                WHERE g.student_id = :sid 
                ORDER BY g.period DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':sid' => $studentId]);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO grades (student_id, activity_id, period, grade) 
                VALUES (:student_id, :activity_id, :period, :grade)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':student_id'  => $data['student_id'],
            ':activity_id' => $data['activity_id'],
            ':period'      => $data['period'] ?? date('Y-1'), // Ej: 2025-1
            ':grade'       => $data['grade']
        ]);
        
        return $this->pdo->lastInsertId();
    }
}