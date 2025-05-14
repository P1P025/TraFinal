<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

include("db.php");

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Datos no válidos"]);
    exit;
}

$cedula = $input["cedula"] ?? "";
$nueva = $input["nuevaContraseña"] ?? "";

if (!$cedula || !$nueva) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Cédula y nueva contraseña son obligatorios"]);
    exit;
}

// Hashear la nueva contraseña
$nuevaHash = password_hash($nueva, PASSWORD_DEFAULT);

// Actualizar la contraseña en la base de datos
$stmt = $conn->prepare("UPDATE usuario SET contraseña = ? WHERE cedula = ?");
$stmt->bind_param("ss", $nuevaHash, $cedula);

if ($stmt->execute()) {
    echo json_encode(["mensaje" => "Contraseña actualizada correctamente"]);
} else {
    http_response_code(500);
    echo json_encode(["mensaje" => "Error al actualizar la contraseña"]);
}

$stmt->close();
$conn->close();
?>
