<?php
// Limpieza de buffer
while (ob_get_level()) ob_end_clean();

// Headers estrictos
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Validación de entrada
if (empty($_GET['cedula']) || !ctype_digit($_GET['cedula'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Cédula inválida']));
}

// Conexión segura
require 'db.php';

try {
    $stmt = $conexion->prepare("
        SELECT 
            id,
            nombre, 
            imagen, 
            precio, 
            stock AS cantidad, 
            descripcion, 
            fecha_inicio, 
            fecha_fin,
            disponible
        FROM productos 
        WHERE cedula = ?
        ORDER BY fecha_creacion DESC
    ");
    if (!$stmt) {
        throw new RuntimeException("Error en preparación de consulta");
    }

    $stmt->bind_param("s", $_GET['cedula']);
    if (!$stmt->execute()) {
        throw new RuntimeException("Error en ejecución de consulta");
    }

    $result = $stmt->get_result();
    $productos = [];

    while ($row = $result->fetch_assoc()) {
        $row = array_map('htmlspecialchars', $row);
        $productos[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'count' => count($productos),
            'productos' => $productos
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener productos',
        'debug' => (ini_get('display_errors')) ? $e->getMessage() : null
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conexion->close();
}
?>