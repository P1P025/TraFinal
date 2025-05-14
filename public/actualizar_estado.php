<?php
// Permitir solicitudes desde otros orígenes (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido"]);
    exit();
}

// Incluir conexión a la base de datos
require_once 'db.php'; // Asegúrate de que esta ruta sea correcta

// Leer y decodificar datos JSON del cuerpo de la solicitud
$datos = json_decode(file_get_contents("php://input"), true);

// Validar datos
if (!isset($datos['cedula']) || !isset($datos['estado'])) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Datos incompletos."]);
    exit();
}

$cedula = $conexion->real_escape_string($datos['cedula']);
$estado = $conexion->real_escape_string($datos['estado']); // "activo" o "inactivo"

// Actualizar estado
$sql = "UPDATE usuario SET estado = '$estado' WHERE cedula = '$cedula'";
if ($conexion->query($sql) === TRUE) {
    echo json_encode(["mensaje" => "Estado actualizado correctamente a '$estado'."]);
} else {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error al actualizar el estado: " . $conexion->error]);
}

$conexion->close();
?>
