<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$datos = json_decode(file_get_contents("php://input"), true);

// Valida que existan productos
if (!isset($datos['productos']) || !is_array($datos['productos'])) {
    echo json_encode(["success" => false, "error" => "No se recibieron productos válidos"]);
    exit;
}

require_once 'db.php'; 

$conexion->begin_transaction();

try {
    foreach ($datos['productos'] as $producto) {
        $nombre = $producto['nombre'];
        $cantidad = isset($producto['cantidad']) ? intval($producto['cantidad']) : 1;

        // Busca el producto por nombre y cantidad 
        $stmt = $conexion->prepare("SELECT id, stock FROM productos WHERE nombre = ?");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            throw new Exception("Producto '$nombre' no encontrado.");
        }

        $fila = $resultado->fetch_assoc();
        $id = $fila['id'];
        $stock_actual = $fila['stock'];

        if ($stock_actual < $cantidad) {
            throw new Exception("Stock insuficiente para '$nombre'.");
        }

        $nuevo_stock = $stock_actual - $cantidad;
        $update = $conexion->prepare("UPDATE productos SET stock = ? WHERE id = ?");
        $update->bind_param("ii", $nuevo_stock, $id);
        $update->execute();
    }

    $conexion->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
