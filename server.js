const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken'); // Mantén solo esta importación
const app = express();
const PORT = 3000;

// Middleware
app.use(cors());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());
app.use(express.static(path.join(__dirname, 'public'))); // Carpeta para tus interfaces

// Base de datos
const db = new sqlite3.Database('./usuarios.db', (err) => {
  if (err) return console.error(err.message);
  console.log('✅ Conectado a la base de datos SQLite');
});

// Crear tabla si no existe
db.run(`
  CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cedula TEXT UNIQUE,
    nombres TEXT,
    apellidos TEXT,
    carrera TEXT,
    correo TEXT UNIQUE,
    contraseña TEXT PASSWORD_BCRYPT,
    rol TEXT
  )
`);

// Crear tabla productos si no existe
db.run(`
  CREATE TABLE IF NOT EXISTS productos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    vendedor_id INTEGER,
    nombre TEXT,
    descripcion TEXT,
    caracteristicas TEXT,
    stock INTEGER,
    precio REAL,
    fecha_disponible TEXT,
    hora_disponible TEXT,
    estado TEXT DEFAULT 'Disponible',
    FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
  )
`);

// Ruta para registrar usuario
app.post('/register', (req, res) => {
  const { cedula, nombres, apellidos, carrera, correo, contraseña, rol } = req.body;

  // Generar el hash de la contraseña
  bcrypt.hash(contraseña, 10, (err, hashedPassword) => {  // Usé un valor de 'saltRounds' que es 10 (normalmente recomendado)
    if (err) {
      return res.status(500).json({ mensaje: 'Error al generar el hash de la contraseña' });
    }

    // Insertar el nuevo usuario con la contraseña hasheada
    db.run(`
      INSERT INTO usuarios (cedula, nombres, apellidos, carrera, correo, contraseña, rol)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `, [cedula, nombres, apellidos, carrera, correo, hashedPassword, rol], function (err) {
      if (err) {
        return res.status(400).json({ mensaje: 'Error al registrar', error: err.message });
      }
      res.json({ mensaje: 'Usuario registrado exitosamente' });
    });
  });
});

// Ruta de login (actualizada para usar JWT)
app.post('/login', (req, res) => {
  const { correo, contraseña } = req.body;

  db.get(`
    SELECT * FROM usuarios WHERE correo = ?
  `, [correo], (err, row) => {
    if (err) {
      return res.status(500).json({ mensaje: 'Error en el servidor' });
    }
    if (!row) {
      return res.status(401).json({ mensaje: 'Credenciales inválidas' });
    }

    bcrypt.compare(contraseña, row.contraseña, (err, result) => {
      if (err || !result) {
        return res.status(401).json({ mensaje: 'Credenciales inválidas' });
      }

      // Crear el token JWT
      const token = jwt.sign({ id: row.id, rol: row.rol }, 'tu_clave_secreta', { expiresIn: '1h' });

      res.json({
        mensaje: 'Login exitoso',
        token,
        rol: row.rol,  // Asegúrate de incluir el rol aquí
        usuario: {
          nombres: row.nombres,
          correo: row.correo,
          cedula: row.cedula,
        }
        
      });
      
    });
  });
});

// Ruta para cambiar el estado de un usuario (solo admin o usuario mismo puede cambiar su estado)
app.put('/usuarios/:id/estado', verifyToken, (req, res) => {
  const { id } = req.params; // ID del usuario
  const { estado } = req.body; // Estado "activo" o "inactivo"
  const usuarioId = req.user.id; // ID del usuario autenticado

  // Verificar si el usuario tiene permisos para cambiar el estado (admin o el mismo usuario)
  if (req.user.rol !== 'admin' && usuarioId !== parseInt(id)) {
    return res.status(403).json({ mensaje: 'No tienes permisos para cambiar el estado de este usuario' });
  }

  // Validar que el estado sea "activo" o "inactivo"
  if (estado !== 'activo' && estado !== 'inactivo') {
    return res.status(400).json({ mensaje: 'Estado inválido. Debe ser "activo" o "inactivo"' });
  }

  // Actualizar el estado en la base de datos
  db.run('UPDATE usuarios SET estado = ? WHERE id = ?', [estado, id], function(err) {
    if (err) {
      return res.status(500).json({ mensaje: 'Error al actualizar el estado del usuario', error: err.message });
    }
    if (this.changes === 0) {
      return res.status(404).json({ mensaje: 'Usuario no encontrado' });
    }
    res.json({ mensaje: `Estado del usuario actualizado a ${estado}` });
  });
});


//Ruta para listar todos los usuarios co su estado
app.get('/usuarios', (req, res) => {
  db.all('SELECT id, nombres, correo, estado FROM usuarios', [], (err, rows) => {
    if (err) return res.status(500).json({ mensaje: 'Error al obtener usuarios', error: err.message });
    res.json(rows);
  });
});


// Ruta para crear un producto (solo vendedores)
app.post('/productos', verifyToken, (req, res) => {
  const { nombre, descripcion, caracteristicas, stock, precio, fecha_disponible, hora_disponible } = req.body;
  const vendedor_id = req.user.id;  // El ID del vendedor está en el JWT

  // Validación básica
  if (!nombre || !descripcion || !stock || !precio || !fecha_disponible || !hora_disponible) {
    return res.status(400).json({ mensaje: 'Faltan campos obligatorios' });
  }

  const estado = 'Disponible';  // Estado inicial del producto

  db.run(`
    INSERT INTO productos (
      vendedor_id, nombre, descripcion, caracteristicas,
      stock, precio, fecha_disponible, hora_disponible, estado
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  `, [vendedor_id, nombre, descripcion, caracteristicas, stock, precio, fecha_disponible, hora_disponible, estado],
    function (err) {
      if (err) {
        return res.status(500).json({ mensaje: 'Error al registrar producto', error: err.message });
      }

      res.status(201).json({ mensaje: 'Producto creado exitosamente', producto_id: this.lastID });
    }
  );
});


// Ruta para listar productos de un vendedor específico
app.get('/productos', verifyToken, (req, res) => {
  const vendedor_id = req.user.id;

  db.all('SELECT * FROM productos WHERE vendedor_id = ?', [vendedor_id], (err, rows) => {
    if (err) {
      return res.status(500).json({ mensaje: 'Error al obtener productos', error: err.message });
    }
    res.json(rows);
  });
});

//Ruta que devulva todos los productos disponibles
app.get('/productos-disponibles', (req, res) => {
  db.all('SELECT * FROM productos WHERE estado = "Disponible"', [], (err, rows) => {
    if (err) {
      return res.status(500).json({ mensaje: 'Error al obtener productos disponibles', error: err.message });
    }
    res.json(rows);
  });
});


// Ruta para modificar un producto existente
app.put('/modificar-producto/:id', verifyToken, (req, res) => {
  const { id } = req.params;  // El id del producto a modificar
  const { nombre, descripcion, caracteristicas, stock, precio, fecha_disponible, hora_disponible } = req.body;
  const vendedor_id = req.user.id; // El id del vendedor (usuario autenticado)

  const query = `
    UPDATE productos
    SET nombre = ?, descripcion = ?, caracteristicas = ?, stock = ?, precio = ?, fecha_disponible = ?, hora_disponible = ?
    WHERE id = ? AND vendedor_id = ?
  `;
  const params = [nombre, descripcion, caracteristicas, stock, precio, fecha_disponible, hora_disponible, id, vendedor_id];

  db.run(query, params, function(err) {
    if (err) return res.status(500).json({ mensaje: 'Error al modificar producto', error: err.message });
    if (this.changes === 0) return res.status(404).json({ mensaje: 'Producto no encontrado o no autorizado' });
    res.json({ mensaje: 'Producto actualizado exitosamente' });
  });
});

//Ruta que devulve los productos de u vededor
app.get('/productos', verifyToken, (req, res) => {
  const vendedor_id = req.user.id;

  db.all('SELECT * FROM productos WHERE vendedor_id = ?', [vendedor_id], (err, rows) => {
    if (err) {
      return res.status(500).json({ mensaje: 'Error al obtener productos', error: err.message });
    }
    res.json(rows);
  });
});


//Ruta para cambiar estado
app.put('/productos/:id/estado', verifyToken, (req, res) => {
  const { id } = req.params;
  const { estado } = req.body; // “Disponible” u “Agotado”
  const vendedor_id = req.user.id;

  db.run(
    'UPDATE productos SET estado = ? WHERE id = ? AND vendedor_id = ?',
    [estado, id, vendedor_id],
    function(err) {
      if (err) return res.status(500).json({ mensaje: 'Error al cambiar estado' });
      if (this.changes === 0) return res.status(404).json({ mensaje: 'Producto no encontrado o no autorizado' });
      res.json({ mensaje: `Estado cambiado a ${estado}` });
    }
  );
});

//QUERY BUSCAR PRODUCTOS
app.get('/buscar-productos', (req, res) => {
  const termino = req.query.q;

  const query = `
    SELECT * FROM productos
    WHERE nombre LIKE ? OR descripcion LIKE ?
  `;

  const likeTerm = `%${termino}%`;

  db.all(query, [likeTerm, likeTerm], (err, rows) => {
    if (err) {
      return res.status(500).json({ mensaje: 'Error al buscar productos', error: err.message });
    }
    res.json(rows);
  });
});


// Middleware para verificar el token correctamente
function verifyToken(req, res, next) {
  const authHeader = req.headers['authorization']; // obtiene "Bearer <token>"
  if (!authHeader) {
    return res.status(403).json({ mensaje: 'No se proporcionó token' });
  }

  // Separar el esquema y el token
  const parts = authHeader.split(' ');
  if (parts.length !== 2 || parts[0] !== 'Bearer') {
    return res.status(403).json({ mensaje: 'Formato de token inválido' });
  }
  const token = parts[1];

  jwt.verify(token, 'tu_clave_secreta', (err, decoded) => {
    if (err) {
      return res.status(401).json({ mensaje: 'Token inválido' });
    }
    req.user = decoded;  
    next();
  });
}



// Usar el middleware en rutas protegidas
app.put('/actualizar-contraseña', verifyToken, (req, res) => {
  const { nuevaContraseña } = req.body;
  const usuarioId = req.user.id;

  if (!nuevaContraseña) {
    return res.status(400).json({ mensaje: 'La nueva contraseña es requerida' });
  }

  db.run('UPDATE usuarios SET contraseña = ? WHERE id = ?', [nuevaContraseña, usuarioId], function(err) {
    if (err) {
      return res.status(500).json({ mensaje: 'Error al actualizar la contraseña', error: err.message });
    }
    res.status(200).json({ mensaje: 'Contraseña actualizada correctamente' });
  });
});

app.get('/usuarios', (req, res) => {
  db.all('SELECT * FROM usuarios', [], (err, rows) => {
    if (err) {
      return res.status(500).json({ mensaje: 'Error al obtener usuarios', error: err.message });
    }
    res.json(rows);
  });
});

// Iniciar servidor
app.listen(PORT, () => {
  console.log(`🚀 Servidor corriendo en http://localhost:${PORT}`);
});
