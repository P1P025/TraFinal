console.log("carrito.js cargado ✅");

let carrito = [];
const listaCarrito = document.getElementById("lista-carrito");
const totalElemento = document.getElementById("total");
const contadorCarrito = document.getElementById("contadorCarrito");

function agregarAlCarrito(nombre, precio) {
  const productoExistente = carrito.find(p => p.nombre === nombre);

  if (productoExistente) {
    productoExistente.cantidad++;
  } else {
    carrito.push({ nombre, precio, cantidad: 1 });
  }

  actualizarCarrito();
}

function actualizarCarrito() {
  listaCarrito.innerHTML = "";
  let total = 0;

  carrito.forEach((item, index) => {
    const li = document.createElement("li");
    li.innerHTML = `
      ${item.nombre} - $${item.precio.toLocaleString()} x ${item.cantidad}
      <button class="btn-menos">-</button>
      <button class="btn-mas">+</button>
      <button class="btn-eliminar">Eliminar</button>
    `;

    // Botón de disminuir cantidad
    li.querySelector(".btn-menos").onclick = () => {
      if (item.cantidad > 1) {
        item.cantidad--;
      } else {
        carrito.splice(index, 1); 
      }
      actualizarCarrito();
    };

    // Botón de aumentar cantidad
    li.querySelector(".btn-mas").onclick = () => {
      item.cantidad++;
      actualizarCarrito();
    };

    // Botón de eliminar el producto directamente
    li.querySelector(".btn-eliminar").onclick = () => {
      carrito.splice(index, 1);
      actualizarCarrito();
    };

    listaCarrito.appendChild(li);
    total += item.precio * item.cantidad;
  });

  totalElemento.textContent = total.toLocaleString();
  contadorCarrito.textContent = carrito.reduce((acc, item) => acc + item.cantidad, 0); 
}

document.getElementById("finalizarCompraBtn").addEventListener("click", () => {
  const resumenDiv = document.getElementById("resumenPedido");
  if (carrito.length === 0) {
    alert("El carrito está vacío.");
    return;
  }

  let resumenHTML = "<h3>Resumen del Pedido:</h3><ul>";
  let total = 0;

  carrito.forEach(item => {
    resumenHTML += `<li>${item.nombre} x${item.cantidad} - $${(item.precio * item.cantidad).toLocaleString()}</li>`;
    total += item.precio * item.cantidad;
  });

  resumenHTML += `</ul><p><strong>Total:</strong> $${total.toLocaleString()}</p>`;
  resumenHTML += `<button id="confirmarCompraBtn">Confirmar Compra</button>`;

  resumenDiv.innerHTML = resumenHTML;
  resumenDiv.classList.remove("oculto");

  // Esperar click en "Confirmar Compra"
  document.getElementById("confirmarCompraBtn").addEventListener("click", () => {
    fetch("procesar_compra.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({ productos: carrito })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Compra realizada con éxito");
        carrito = [];
        actualizarCarrito();
        resumenDiv.innerHTML = "";
        resumenDiv.classList.add("oculto");
      } else {
        alert("Error en la compra: " + data.error);
      }
    });
  });
});


