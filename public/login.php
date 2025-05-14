<?php
// login.php

ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/json; charset=utf-8");
require "db.php";  // define $conn

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['mensaje'=>'❌ Datos no válidos']);
    exit;
}

$correo   = trim($input["correo"]     ?? "");
$claveRaw = trim($input["contraseña"] ?? "");
if (!$correo || !$claveRaw) {
    http_response_code(400);
    echo json_encode(['mensaje'=>'❌ Correo y contraseña son obligatorios']);
    exit;
}

// Ajusta los campos al esquema real:
$stmt = $conn->prepare("
    SELECT cedula, nombre, apellidos, correo, contraseña, rol
    FROM `usuarios`
    WHERE correo = ?
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($claveRaw, $user["contraseña"])) {
        echo json_encode([
            'status'  => 'success',
            'mensaje' => '✅ Inicio de sesión exitoso',
            'token'   => '',
            'usuario' => [
                'cedula'  => $user['cedula'],
                'nombres' => $user['nombre'],   // mapeo desde `nombre`
                'correo'  => $user['correo']
            ],
            'rol'     => $user['rol']
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(401);
        echo json_encode(['mensaje'=>'❌ Contraseña incorrecta']);
    }
} else {
    http_response_code(404);
    echo json_encode(['mensaje'=>'❌ Usuario no encontrado']);
}

$stmt->close();
$conn->close();
