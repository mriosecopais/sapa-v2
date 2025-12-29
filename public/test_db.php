<?php
// Habilitar errores al máximo para ver qué pasa
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Conexión</h1>";

// 1. Probar Credenciales (Las mismas que pusimos en Database.php)
$host = 'db';
$db   = 'sapa_v2_db';
$user = 'sapa_user';
$pass = 'Mr34s2c4@'; // Tu contraseña real

echo "<p>Intentando conectar a: <strong>$host</strong> con usuario <strong>$user</strong>...</p>";

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    echo "<h3 style='color:green'>✅ ¡Conexión Exitosa con MySQL!</h3>";
    
    // 2. Probar si hay perfiles
    $stmt = $pdo->query("SELECT * FROM profiles");
    $perfiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Número de perfiles encontrados: <strong>" . count($perfiles) . "</strong></p>";
    
    if (count($perfiles) > 0) {
        echo "<pre>";
        print_r($perfiles[0]); // Muestra el primero para ver si los datos están bien
        echo "</pre>";
    } else {
        echo "<h3 style='color:orange'>⚠️ La tabla 'profiles' está vacía.</h3>";
    }

} catch (PDOException $e) {
    echo "<h3 style='color:red'>❌ Error Fatal de Conexión:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>