<?php
namespace App\Controllers;

use App\Core\Model;

class MatrixController {
    private $pdo;

    public function __construct() {
        // Usamos una conexión directa ligera, ya que Model.php es genérico
        $db = \App\Config\Database::getInstance();
        $this->pdo = $db->getConnection();
    }

    // GET /api/matrix/{profileId}
    // Obtiene la matriz completa para pintar la tabla visual
    public function show($profileId) {
        // 1. Obtener todas las Asignaturas del perfil
        $stmtAct = $this->pdo->prepare("SELECT id, name, custom_id FROM activities WHERE profile_id = :pid ORDER BY semester, name");
        $stmtAct->execute([':pid' => $profileId]);
        $activities = $stmtAct->fetchAll();

        // 2. Obtener Competencias (Específicas del perfil + Sello + Licenciatura)
        // OJO: Traemos las del perfil O las que sean globales (scope != specific)
        $stmtComp = $this->pdo->prepare("
            SELECT id, description, custom_id, scope, type 
            FROM competencies 
            WHERE profile_id = :pid OR scope IN ('seal', 'licensure') 
            ORDER BY scope DESC, custom_id ASC
        ");
        $stmtComp->execute([':pid' => $profileId]);
        $competencies = $stmtComp->fetchAll();

        // 3. Obtener las relaciones existentes (Cruces marcados)
        $stmtRel = $this->pdo->prepare("
            SELECT ac.* FROM activity_competency ac
            JOIN activities a ON ac.activity_id = a.id
            WHERE a.profile_id = :pid
        ");
        $stmtRel->execute([':pid' => $profileId]);
        $relations = $stmtRel->fetchAll();

        // Formatear relaciones para fácil acceso en JS { "actID_compID": "medium", ... }
        $map = [];
        foreach($relations as $r) {
            $map[$r['activity_id'] . '_' . $r['competency_id']] = $r['contribution_level'];
        }

        echo json_encode([
            'activities' => $activities,
            'competencies' => $competencies,
            'relations' => $map
        ]);
    }

    // POST /api/matrix/toggle
    // Guarda o borra una relación (Celda de la matriz)
    public function toggle() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $actId = $data['activity_id'];
        $compId = $data['competency_id'];
        $level = $data['level'] ?? 'medium'; // low, medium, high

        // Verificar si ya existe
        $check = $this->pdo->prepare("SELECT 1 FROM activity_competency WHERE activity_id=? AND competency_id=?");
        $check->execute([$actId, $compId]);
        
        if ($check->fetch()) {
            // Si existe, actualizamos o borramos. 
            // Para simplificar: si el nivel enviado es 'none', borramos.
            if ($level === 'none') {
                $del = $this->pdo->prepare("DELETE FROM activity_competency WHERE activity_id=? AND competency_id=?");
                $del->execute([$actId, $compId]);
                echo json_encode(['status' => 'deleted']);
            } else {
                $upd = $this->pdo->prepare("UPDATE activity_competency SET contribution_level=? WHERE activity_id=? AND competency_id=?");
                $upd->execute([$level, $actId, $compId]);
                echo json_encode(['status' => 'updated']);
            }
        } else {
            // Si no existe, insertamos
            if ($level !== 'none') {
                $ins = $this->pdo->prepare("INSERT INTO activity_competency (activity_id, competency_id, contribution_level) VALUES (?, ?, ?)");
                $ins->execute([$actId, $compId, $level]);
                echo json_encode(['status' => 'created']);
            }
        }
    }
}