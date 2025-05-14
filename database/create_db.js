const sqlite3 = require('sqlite3').verbose();
const db = new sqlite3.Database('./database/users.db');

// Crear tabla de usuarios
db.serialize(() => {
  db.run(`CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cedula TEXT UNIQUE,
    nombre TEXT,
    apellidos TEXT,
    carrera TEXT,
    correo TEXT UNIQUE,
    contraseña TEXT PASSWORD_BCRYPT,
    rol TEXT
  )`, (err) => {
    if (err) {
      console.error("❌ Error creando la tabla:", err.message);
    } else {
      console.log("✅ Tabla 'usuarios' creada correctamente");
    }
  });
});

db.close();
