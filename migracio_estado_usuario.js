const sqlite3 = require('sqlite3').verbose();
const db = new sqlite3.Database('./usuarios.db');

db.serialize(() => {
  db.run(`ALTER TABLE usuarios ADD COLUMN estado TEXT DEFAULT 'activo'`, (err) => {
    if (err && !err.message.includes('duplicate column')) {
      console.error("❌ Error al agregar columna:", err.message);
    } else {
      console.log("✅ Columna 'estado' agregada correctamente (o ya existía)");
    }
  });
});

db.close();
