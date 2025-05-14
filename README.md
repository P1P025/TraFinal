# UNI_MARKET-MAIN

**UniMarket** es una plataforma web para conectar estudiantes emprendedores con compradores dentro de una comunidad universitaria. Este repositorio contiene el código fuente completo del frontend (HTML, CSS, JS) y del backend (PHP con base de datos MySQL), incluyendo scripts de despliegue y pruebas en contenedores Docker.

---

## 🚀 Flujo de Integración y Despliegue

A continuación se describe el flujo utilizado para pruebas, integración y despliegue continuo del proyecto.

### 1. Preparación y ejecución en entorno local con Docker

Antes de realizar cualquier commit en ramas principales, se ejecutó el entorno local en contenedores Docker para verificar el correcto funcionamiento de la aplicación y de la base de datos:

```bash
docker-compose up --build
```

Esto levantó:

- Un contenedor con Apache + PHP
- Un contenedor con MySQL (puerto 3307)
- Persistencia de datos en volúmenes
- Conexión entre servicios mediante red Docker

**Pruebas realizadas:**

- Registro de usuarios
- Inicio de sesión (login)
- Conexión exitosa a base de datos
- Validación de errores desde el frontend y backend

---

### 2. Commit de código luego de pruebas exitosas

Después de verificar que el sistema funcionaba correctamente dentro de Docker, se realizó un primer commit para guardar estos cambios:

```bash
git add .
git commit -m "🧪 Probado en entorno Docker - login y conexión funcional"
```

---

### 3. Integración a la rama `main`

Para mantener la rama `main` actualizada con los últimos cambios locales, se realizó un pull y luego un commit con mejoras adicionales:

```bash
git checkout main
git pull origin main
# Se resolvieron conflictos si fue necesario
git add .
git commit -m "🔄 Integración de pruebas locales a rama main"
```

---

### 4. Despliegue a rama `master` (Producción)

Como último paso, se subieron los cambios a la rama `master`, utilizada para reflejar el estado actual en producción:

```bash
git checkout master
git merge main
git push origin master
```

Este flujo garantiza que:

- `main` contiene el estado más actualizado y probado del código
- `master` refleja el entorno productivo y estable

---

## 🧪 Herramientas y Tecnologías

- HTML, CSS, JavaScript
- PHP (API backend)
- MySQL (base de datos)
- Docker y Docker Compose
- Git (flujo de ramas: main → master)
- Navegadores (para pruebas de frontend)

---

## 📝 Próximos pasos

- Añadir autenticación con tokens (JWT)
- Implementar panel de administración por roles
- Crear scripts de pruebas automatizadas para validación CI
- Integrar GitHub Actions para despliegue automático

