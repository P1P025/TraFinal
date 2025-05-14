<?php
header('Content-Type: application/json');

try {
    // leer el JSON que envía fetch
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = intval($data['id'] ?? 0);

    if (!$id) {
        throw new Exception("ID de producto no válido.");
    }

    // Conexión a la base de datos
    $m = new mysqli('localhost', 'root', '', 'unimarket');
    if ($m->connect_errno) {
        throw new Exception("Error de conexión: " . $m->connect_error);
    }
    $m->set_charset('utf8mb4');

    // Opcional: obtén el nombre de la imagen para borrarla del disco
    $stmt = $m->prepare("SELECT imagen FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($imagenNombre);
    if (!$stmt->fetch()) {
        throw new Exception("Producto no encontrado.");
    }
    $stmt->close();

    // Borrar registro
    $stmt = $m->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Error al eliminar el producto: " . $stmt->error);
    }
    $stmt->close();

    // Borrar archivo de imagen
    if ($imagenNombre) {
        $ruta = __DIR__ . '/public/img/' . $imagenNombre;
        if (file_exists($ruta)) {
            @unlink($ruta);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Producto eliminado con éxito.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
?>
