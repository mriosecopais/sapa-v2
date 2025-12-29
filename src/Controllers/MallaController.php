<?php
namespace App\Controllers;

use App\Models\Activity;

class MallaController {
    private $activityModel;

    public function __construct() {
        $this->activityModel = new Activity();
    }

    // GET /api/profiles/{id}/malla
    public function getGraph($profileId) {
        // 1. Obtener todas las actividades del perfil
        $activities = $this->activityModel->getByProfile($profileId);
        
        // 2. Agruparlas por semestre
        $malla = [];
        
        foreach ($activities as $act) {
            $semestre = $act['semester'];
            
            // Si el semestre no existe en el array, crearlo
            if (!isset($malla[$semestre])) {
                $malla[$semestre] = [];
            }
            
            // Agregar la actividad a ese semestre
            $malla[$semestre][] = [
                'id' => $act['id'],
                'name' => $act['name'],
                'credits' => $act['credits'],
                'type' => $act['type'],
                'code' => $act['custom_id']
            ];
        }

        // 3. Devolver JSON estructurado
        // Formato: { "1": [mate, historia], "2": [fisica, quimica] }
        echo json_encode([
            'profile_id' => $profileId,
            'semesters' => $malla
        ]);
    }
}