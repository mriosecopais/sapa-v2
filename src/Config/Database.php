<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // [MODIFICACIÓN TEMPORAL] Forzamos los valores del .env para descartar errores de lectura
        // Usamos las credenciales que mostraste en tu mensaje anterior
        $host = 'db';              // Nombre del servicio en docker-compose
        $port = '3306';            // Puerto interno de mysql
        $db   = 'sapa_v2_db';
        $user = 'sapa_user';
        $pass = 'Mr34s2c4@';       // Tu contraseña real del .env

        try {
            // Construimos el DSN
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            
            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Tip de depuración: temporalmente mostramos el error real para que lo veas si falla
            die("Error FATAL de conexión a BD: " . $e->getMessage());
        }
    }

    // Patrón Singleton: Garantiza una única instancia
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}