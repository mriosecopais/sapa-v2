<?php
namespace App\Services;

use App\Config\Database;
use PDO;

class CodeGeneratorService {

    public static function generate($profileId, $type) {
        $pdo = Database::getInstance()->getConnection();
        
        // 1. Determinar prefijo según el tipo (Coincide con tu ENUM de base de datos)
        $prefix = match(strtolower($type)) {
            'disciplinar' => 'COMP',
            'sello' => 'SELLO',
            'licenciatura' => 'LIC',
            default => 'GEN'
        };

        // 2. Buscar el último código generado para este perfil y tipo
        // Buscamos el ID más alto para asegurarnos de seguir la secuencia
        $sql = "SELECT custom_id FROM competencies 
                WHERE profile_id = :pid AND type = :type 
                ORDER BY id DESC LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':pid' => $profileId, ':type' => $type]);
        $lastCode = $stmt->fetchColumn();

        // 3. Calcular el siguiente número
        if ($lastCode) {
            // Extraemos el número final (Ej: de 'COMP-04' extraemos '04')
            if (preg_match('/-(\d+)$/', $lastCode, $matches)) {
                $nextNum = intval($matches[1]) + 1;
            } else {
                $nextNum = 1;
            }
        } else {
            $nextNum = 1;
        }

        // 4. Formatear (Ej: COMP-05)
        // %02d asegura que siempre tenga dos dígitos (01, 02, ... 10)
        return sprintf('%s-%02d', $prefix, $nextNum);
    }
}