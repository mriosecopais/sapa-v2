<?php
namespace App\Controllers;

use App\Config\Database;

class ReportController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // GET /api/reports/gap-analysis/{profileId}
    public function gapAnalysis($profileId) {
        // 1. Obtener competencias
        $sqlComp = "SELECT id, custom_id, description, scope FROM competencies 
                    WHERE profile_id = :pid OR scope = 'seal'";
        $stmt = $this->pdo->prepare($sqlComp);
        $stmt->execute([':pid' => $profileId]);
        $competencies = $stmt->fetchAll();

        $report = [];

        foreach ($competencies as $comp) {
            // 2. Calcular Promedios (Código anterior)
            // Real (Docente)
            $sqlReal = "SELECT AVG(ed.score) as average FROM evaluation_details ed
                JOIN criteria c ON ed.criterion_id = c.id
                JOIN evaluations e ON ed.evaluation_id = e.id
                JOIN instruments i ON e.instrument_id = i.id
                WHERE c.competency_id = :cid AND i.type = 'rubric'";
            $stmtReal = $this->pdo->prepare($sqlReal);
            $stmtReal->execute([':cid' => $comp['id']]);
            $realScore = $stmtReal->fetch()['average'] ?? 0;

            // Percepción (Estudiante)
            $sqlPerc = "SELECT AVG(ed.score) as average FROM evaluation_details ed
                JOIN criteria c ON ed.criterion_id = c.id
                JOIN evaluations e ON ed.evaluation_id = e.id
                JOIN instruments i ON e.instrument_id = i.id
                WHERE c.competency_id = :cid AND i.type = 'survey'";
            $stmtPerc = $this->pdo->prepare($sqlPerc);
            $stmtPerc->execute([':cid' => $comp['id']]);
            $percScore = $stmtPerc->fetch()['average'] ?? 0;

            // 3. NUEVO: Buscar EVIDENCIA DIGITAL (El archivo más reciente)
            // Buscamos si existe algún archivo ligado a una evaluación que midió esta competencia
            $sqlEvid = "SELECT f.filepath FROM evidence_files f
                JOIN evaluations e ON f.evaluation_id = e.id
                JOIN evaluation_details ed ON ed.evaluation_id = e.id
                JOIN criteria c ON ed.criterion_id = c.id
                WHERE c.competency_id = :cid
                ORDER BY f.id DESC LIMIT 1";
            
            $stmtEvid = $this->pdo->prepare($sqlEvid);
            $stmtEvid->execute([':cid' => $comp['id']]);
            $evidencePath = $stmtEvid->fetch()['filepath'] ?? null;

            if ($realScore > 0 || $percScore > 0) {
                $report[] = [
                    'competency' => $comp['description'],
                    'code' => $comp['custom_id'],
                    'real_score' => round($realScore, 2),
                    'perc_score' => round($percScore, 2),
                    'gap' => round($realScore - $percScore, 2),
                    'evidence_url' => $evidencePath // <--- Aquí va el link al PDF
                ];
            }
        }

        echo json_encode($report);
    }
}