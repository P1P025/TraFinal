
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('token'); // Asumiendo que el token se guarda en el localStorage
  
    // Realizamos la solicitud GET para obtener los productos disponibles
    fetch('http://localhost:3000/productos-disponibles', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`, // Incluir el token en la cabecera
      }
    })
    .then(response => response.json())
    .then(data => {
      console.log('Productos disponibles:', data);
  
      // Suponiendo que tienes un contenedor con id 'productos-container' para mostrar los productos
      const productosContainer = document.getElementById('productos-container');
      productosContainer.innerHTML = ''; // Limpiar el contenedor
  
      // Mostrar los productos
      data.forEach(producto => {
        const productoElemento = document.createElement('div');
        productoElemento.classList.add('producto');
        productoElemento.innerHTML = `
          <h3>${producto.nombre}</h3>
          <p>${producto.descripcion}</p>
          <p>Precio: ${producto.precio}</p>
        `;
        productosContainer.appendChild(productoElemento);
      });
    })
    .catch(error => {
      console.error('Error al cargar los productos:', error);
    });
  });
  