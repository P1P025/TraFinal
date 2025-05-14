<?php
// db.php

$host     = "host.docker.internal";
$user     = "root";
$password = "";
$db       = "unimarket";
$port     = 3307;

$conn = new mysqli($host, $user, $password, $db, $port);
if ($conn->connect_error) {
    // Si no puede conectar, respondemos y detenemos
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'error',
        'mensaje' => '❌ Error de conexión: ' . $conn->connect_error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
// Si llegó aquí, la conexión está OK y NO imprimimos nada más.
