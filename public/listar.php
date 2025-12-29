<?php
// 1. Definimos la raíz del proyecto (subimos un nivel desde donde está este archivo)
$projectRoot = realpath(__DIR__ . '/../');

// 2. Iniciamos el iterador en la raíz del proyecto
// SKIP_DOTS evita que salgan los puntos "." y ".."
$dir = new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS);

// SELF_FIRST asegura que se listen los directorios antes que los archivos que contienen
$iter = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::SELF_FIRST);

$files = [];

foreach ($iter as $file) {
    // Obtenemos la ruta real del archivo/directorio
    $absolutePath = $file->getRealPath();

    // 3. Limpiamos la ruta para mostrarla relativa a la raíz del proyecto
    // (Quitamos toda la parte de /var/www/html... para que se vea limpio)
    $relativePath = str_replace($projectRoot, '', $absolutePath);
    $relativePath = str_replace('\\', '/', $relativePath); // Corrección para Windows/Linux

    // OPCIONAL: Si quieres volver a filtrar SOLO archivos PHP, descomenta esto:
    // if ($file->isFile() && $file->getExtension() !== 'php') continue;

    // Ignoramos la carpeta .git para no ensuciar la lista (opcional)
    if (strpos($relativePath, '/.git') === 0) continue;

    $files[] = $relativePath;
}

sort($files);

echo "<h3>📂 Estructura del Proyecto:</h3>";
echo "<div style='background:#f4f4f4; padding:20px; border:1px solid #ddd; border-radius:5px;'>";
echo "<pre>";
foreach ($files as $file) {
    echo $file . "\n";
}
echo "</pre>";
echo "</div>";
?>