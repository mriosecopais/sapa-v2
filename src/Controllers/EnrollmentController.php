<?php
namespace App\Controllers;

use App\Core\Model;

class EnrollmentController {
    private $pdo;

    public function __construct() {
        $db = \App\Config\Database::getInstance();
        $this->pdo = $db->getConnection();
    }

    // GET /api/enrollments/student/{id}
    // Ver qué materias está cursando un alumno
    public function byStudent($studentId) {
        $sql = "SELECT e.*, a.name as activity_name, a.custom_id, p.name as profile_name 
                FROM enrollments e
                JOIN activities a ON e.activity_id = a.id
                JOIN profiles p ON a.profile_id = p.id
                WHERE e.student_id = :sid ORDER BY e.year DESC, e.semester DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':sid' => $studentId]);
        echo json_encode($stmt->fetchAll());
    }

    // POST /api/enrollments
    // Inscribir un alumno en una asignatura
    public function store() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if(empty($data['student_id']) || empty($data['activity_id'])) {
            http_response_code(400); echo json_encode(['error' => 'Faltan datos']); return;
        }

        try {
            $sql = "INSERT INTO enrollments (student_id, activity_id, year, semester) VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['student_id'], 
                $data['activity_id'], 
                $data['year'] ?? date('Y'), 
                $data['semester'] ?? 1
            ]);
            
            http_response_code(201);
            echo json_encode(['message' => 'Asignatura inscrita correctamente']);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                http_response_code(409);
                echo json_encode(['error' => 'El alumno ya tiene inscrita esta asignatura este semestre.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    }

    // DELETE /api/enrollments/{id}
    public function destroy($id) {
        $stmt = $this->pdo->prepare("DELETE FROM enrollments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['message' => 'Inscripción eliminada']);
    }
}