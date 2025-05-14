<?php
header('Content-Type: application/json');

// Ajusta estos valores a tu configuración
$uploadDir  = __DIR__ . '/public/img/';
$maxSize    = 2 * 1024 * 1024; // 2 MB máximo

try {
    // 1) Conexión a la base de datos
    $mysqli = new mysqli('localhost', 'root', '', 'unimarket');
    if ($mysqli->connect_errno) {
        throw new Exception("Error de conexión: " . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    // 2) Validación de campos
    $nombre          = trim($_POST['nombre'] ?? '');
    $caracteristicas = trim($_POST['caracteristicas'] ?? '');
    $stock           = intval($_POST['stock'] ?? 0);
    $descripcion     = trim($_POST['descripcion'] ?? '');
    $precio          = floatval($_POST['precio'] ?? 0);
    $fecha_inicio    = $_POST['fecha_inicio'] ?? '';
    $fecha_fin       = $_POST['fecha_fin']   ?? '';

    $errors = [];
    if (!$nombre)          $errors[] = "El nombre es obligatorio";
    if (!$caracteristicas) $errors[] = "Las características son obligatorias";
    if ($stock < 0)        $errors[] = "El stock debe ser un número positivo";
    if ($precio <= 0)      $errors[] = "El precio debe ser mayor que cero";
    if (!$fecha_inicio)    $errors[] = "La fecha de inicio es obligatoria";
    if (!$fecha_fin)       $errors[] = "La fecha de fin es obligatoria";

    // 3) Manejo de la imagen
    $imagenNombre = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error al subir la imagen";
        } elseif ($_FILES['imagen']['size'] > $maxSize) {
            $errors[] = "La imagen supera el tamaño máximo de 2 MB";
        } else {
            // Crear un nombre único
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $imagenNombre = uniqid('prod_') . '.' . $ext;

            // Asegurarse de que la carpeta existe
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                $errors[] = "No se pudo crear la carpeta de imágenes";
            } else {
                $destino = $uploadDir . $imagenNombre;
                if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                    $errors[] = "No se pudo mover la imagen a su destino";
                }
            }
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // 4) Insertar en la base de datos
    $stmt = $mysqli->prepare("
        INSERT INTO productos 
          (nombre, caracteristicas, stock, descripcion, precio, fecha_inicio, fecha_fin, imagen)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssisdsss",
        $nombre,
        $caracteristicas,
        $stock,
        $descripcion,
        $precio,
        $fecha_inicio,
        $fecha_fin,
        $imagenNombre
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar producto: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Producto agregado correctamente',
        'producto_id' => $stmt->insert_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'errors'  => [ $e->getMessage() ]
    ]);
} finally {
    if (isset($stmt))   $stmt->close();
    if (isset($mysqli)) $mysqli->close();
}
