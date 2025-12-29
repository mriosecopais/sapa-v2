<?php
namespace App\Controllers;

use App\Config\Database;

class EvaluationController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // POST /api/evaluations
    // Guarda una evaluación completa
    public function store() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validación
        if (empty($data['instrument_id']) || empty($data['student_id']) || empty($data['answers'])) {
            http_response_code(400); echo json_encode(['error' => 'Faltan datos']); return;
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Calcular Nota Final (Promedio Simple por ahora)
            // En un sistema real usaríamos los pesos (weights)
            $totalScore = 0;
            $count = 0;
            foreach ($data['answers'] as $score) {
                $totalScore += $score;
                $count++;
            }
            $finalScore = $count > 0 ? ($totalScore / $count) : 0;
            // Ajuste simple a escala 1-7 si los puntajes son raw (opcional, aquí asumimos que ya viene listo)

            // 2. Insertar Encabezado de Evaluación
            $sql = "INSERT INTO evaluations (instrument_id, student_id, evaluator_id, final_score, completed_at) 
                    VALUES (:iid, :sid, :uid, :final, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':iid' => $data['instrument_id'],
                ':sid' => $data['student_id'],
                ':uid' => $_SESSION['user_id'] ?? 1, // Si no hay sesión, asume admin (1)
                ':final' => $finalScore
            ]);
            $evalId = $this->pdo->lastInsertId();

            // 3. Insertar Detalles (Criterio por Criterio)
            $sqlDet = "INSERT INTO evaluation_details (evaluation_id, criterion_id, score) VALUES (?, ?, ?)";
            $stmtDet = $this->pdo->prepare($sqlDet);

            foreach ($data['answers'] as $critId => $score) {
                $stmtDet->execute([$evalId, $critId, $score]);
            }

            $this->pdo->commit();
            echo json_encode(['message' => 'Evaluación guardada', 'final_score' => $finalScore, 'id' => $evalId]);

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
        }
    }
}