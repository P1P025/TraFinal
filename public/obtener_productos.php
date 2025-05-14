<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// Leer filtro 
$filtro = $_GET['filtro'] ?? 'todos';
$filtro = in_array($filtro, ['todos','disponibles','noDisponibles']) ? $filtro : 'todos';

$where = '';
if ($filtro === 'disponibles') {
    $where = 'WHERE disponible = 1';
} elseif ($filtro === 'noDisponibles') {
    $where = 'WHERE disponible = 0';
}

// Consulta
$sql = "SELECT * FROM productos $where ORDER BY fecha_inicio DESC";
$resultado = $conexion->query($sql);

$productos = [];
if ($resultado && $resultado->num_rows > 0) {
    while ($prod = $resultado->fetch_assoc()) {
        $productos[] = $prod;
    }
}

echo json_encode([
    'success'   => true,
    'productos' => $productos
]);

$conexion->close();
?>
