<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require 'db.php';  // Esto solo abre $conn, no imprime nada si está OK

// Leer y decodificar JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['status'=>'error','mensaje'=>'❌ JSON no válido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// **Verifica tus columnas**
// Abre phpMyAdmin y comprueba exactamente cómo se llaman las columnas de tu tabla `usuarios`.
// Por ejemplo, tal vez sean `nombre` (singular) o `apellido` sin “s”.
// Ajusta estas variables según tu esquema real:
$cedula    = $conn->real_escape_string($data['cedula']);
$nombre    = $conn->real_escape_string($data['nombres']);    // Si tu columna es `nombre`
$apellidos  = $conn->real_escape_string($data['apellidos']);  // Si tu columna es `apellido`
$carrera   = $conn->real_escape_string($data['carrera']);
$correo    = $conn->real_escape_string($data['correo']);
$pass_hash = password_hash($data['contraseña'], PASSWORD_DEFAULT);
$rol       = $conn->real_escape_string($data['rol']);

// Usa **backticks** con el nombre real de tus columnas:
$sql = "INSERT INTO `usuarios` 
        (`cedula`,`nombre`,`apellidos`,`carrera`,`correo`,`contraseña`,`rol`)
        VALUES 
        ('$cedula','$nombre','$apellidos','$carrera','$correo','$pass_hash','$rol')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['status'=>'success','mensaje'=>'✅ Usuario registrado exitosamente'], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['status'=>'error','mensaje'=>'❌ Error al registrar usuario: '.$conn->error], JSON_UNESCAPED_UNICODE);
}

$conn->close();
