<?php
namespace App\Controllers;

use App\Models\Grade;

class GradeController {
    private $model;

    public function __construct() {
        $this->model = new Grade();
    }

    // GET /api/students/{id}/grades
    public function index($studentId) {
        $data = $this->model->getByStudent($studentId);
        echo json_encode($data);
    }

    // POST /api/grades
    public function store() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // 1. Validar datos obligatorios
        if (empty($data['student_id']) || empty($data['activity_id']) || empty($data['grade'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan datos: student_id, activity_id, grade']);
            return;
        }

        // 2. Validar rango de nota (Chile: 1.0 a 7.0)
        if ($data['grade'] < 1.0 || $data['grade'] > 7.0) {
            http_response_code(400);
            echo json_encode(['error' => 'La nota debe estar entre 1.0 y 7.0']);
            return;
        }

        try {
            $id = $this->model->create($data);
            
            // Lógica de aprobación automática (definida en la BD como columna generada, pero útil saberlo aquí)
            $status = ($data['grade'] >= 4.0) ? 'Aprobado' : 'Reprobado';

            http_response_code(201);
            echo json_encode([
                'message' => 'Nota registrada correctamente', 
                'id' => $id,
                'status' => $status
            ]);
        } catch (\PDOException $e) {
            // Capturamos error de integridad (Si el alumno o asignatura no existen)
            if ($e->getCode() == '23000') {
                http_response_code(404);
                echo json_encode(['error' => 'El Estudiante o la Asignatura no existen.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error BD: ' . $e->getMessage()]);
            }
        }
    }
}