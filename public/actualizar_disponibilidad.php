<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener los datos 
$datos = json_decode(file_get_contents("php://input"), true);

// Verifica que los datos sean válidos
if (!isset($datos['id']) || !isset($datos['disponible'])) {
    echo json_encode(["success" => false, "error" => "Datos incompletos"]);
    exit;
}

require_once 'db.php';

// Valida conexión
if ($conexion->connect_error) {
    echo json_encode(["success" => false, "error" => "Error de conexión a la base de datos: " . $conexion->connect_error]);
    exit;
}

$id = intval($datos['id']);  
$disponible = $datos['disponible'] ? 1 : 0;  

// Prepara la consulta para actualizar la disponibilidad
$sql = "UPDATE productos SET disponible = ? WHERE id = ?";
$stmt = $conexion->prepare($sql);

// Verifica si la preparación fue exitosa
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "Error en la preparación de la consulta"]);
    exit;
}

$stmt->bind_param("ii", $disponible, $id); 

// Ejecutar la consulta
if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "mensaje" => "La disponibilidad del producto se ha actualizado correctamente."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Error en la BD al actualizar la disponibilidad."
    ]);
}

// Cerrar 
$stmt->close();
$conexion->close();

exit;
?>
