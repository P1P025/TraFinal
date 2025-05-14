<?php
header('Content-Type: application/json');

try {
    // Conexión a la base de datos
    $m = new mysqli('localhost', 'root', '', 'unimarket');
    $m->set_charset('utf8mb4');

    // Recibir datos del formulario
    $id = intval($_POST['id'] ?? 0);
    $nombre = $_POST['nombre'] ?? '';
    $caracteristicas = $_POST['caracteristicas'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $stock = intval($_POST['stock'] ?? 0);
    $precio = floatval($_POST['precio'] ?? 0);
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    
    // Validación de datos
    if (!$id || !$nombre || !$caracteristicas || !$descripcion || !$stock || !$precio || !$fecha_inicio || !$fecha_fin) {
        throw new Exception("Todos los campos son requeridos.");
    }

    // Manejo de imagen (si es que la suben)
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = $_FILES['imagen'];
        $imagenNombre = uniqid() . '-' . $imagen['name'];
        $imagenRuta = 'public/img/' . $imagenNombre;

        // Subir la imagen
        if (!move_uploaded_file($imagen['tmp_name'], $imagenRuta)) {
            throw new Exception("Error al subir la imagen.");
        }
    } else {
        // Si no se sube imagen, mantener la imagen anterior
        $imagenNombre = $_POST['imagen_actual'] ?? '';  // Suponiendo que la imagen actual se pasa al editar
    }

    // Actualizar producto en la base de datos
    $stmt = $m->prepare("
        UPDATE productos SET
            nombre = ?, 
            caracteristicas = ?, 
            descripcion = ?, 
            stock = ?, 
            precio = ?, 
            fecha_inicio = ?, 
            fecha_fin = ?, 
            imagen = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssidsisi",
        $nombre, $caracteristicas, $descripcion,
        $stock, $precio, $fecha_inicio, $fecha_fin, 
        $imagenNombre, $id
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar el producto: " . $stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'Producto actualizado con éxito.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
