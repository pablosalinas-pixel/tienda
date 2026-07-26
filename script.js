// ==============
// Producto
// ==============
class Producto {
    constructor(id, nombre, precio, stock) {
        this.id = id;
        this.nombre = nombre;
        this.precio = precio;
        this.stock = stock;
    }
    mostrarInformacion() {
        return `${this.nombre} - $${this.precio}`;
    }
}

// Productos disponibles agregan desde PHP/MySQL
let productos = [];

// ==============
// Agregar productos desde  MySQL
// ==============
function cargarProductosDesdeBD() {
    fetch('obtener_productos.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            productos = data.productos.map(p => new Producto(p.id_producto, p.nombre, p.precio, p.stock));
            mostrarProductos(productos);
        }
    })
    .catch(err => {
        console.error('Error cargando productos:', err);
        // Fallback: productos hardcodeados si falla la BD
        productos = [
            new Producto(1, "Notebook HP G4", 550000, 9),
            new Producto(2, "Mouse Gamer con RGB", 25000, 30),
            new Producto(3, "Teclado Mecánico primus", 45000, 10),
            new Producto(4, "Monitor Sony 27", 190000, 8),
            new Producto(5, "Audífonos stereo Bluetooth", 45000, 10),
            new Producto(6, "Webcam Full 4k", 40000, 8)
        ];
        mostrarProductos(productos);
    });
}

// ==============
// Mostrar productos
// ==============
function mostrarProductos(lista) {
    const container = document.getElementById("results-container");
    container.innerHTML = "";
    lista.forEach(producto => {
        const card = document.createElement("div");
        card.classList.add("product-card");
        const stockClass = producto.stock < 5 ? 'style="color:#dc3545; font-weight:bold;"' : '';
        const botonDeshabilitado = producto.stock <= 0 ? 'disabled style="background:#6c757d; cursor:not-allowed;"' : '';
        card.innerHTML = `
            <h3>${producto.nombre}</h3>
            <p>Precio: $${producto.precio.toLocaleString()}</p>
            <p ${stockClass}>Stock: ${producto.stock}</p>
            <button onclick="agregarAlCarrito(${producto.id})" ${botonDeshabilitado}>
                ${producto.stock > 0 ? 'Agregar al carrito' : 'Agotado'}
            </button>
        `;
        container.appendChild(card);
    });
}

// ==============
// Localizar productos
// ==============
function buscarProductos() {
    const texto = document.getElementById("product-search").value.toLowerCase();
    const filtrados = productos.filter(producto =>
        producto.nombre.toLowerCase().includes(texto)
    );
    mostrarProductos(filtrados);
}

// ==============
// carrito con sesion mediante php
// ==============
function agregarAlCarrito(id) {
    const formData = new FormData();
    formData.append('accion', 'agregar');
    formData.append('id', id);

    fetch('carrito.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.mensaje);
            actualizarVistaCarrito();
            cargarProductosDesdeBD(); // Recargar para ver stock actualizado
        } else {
            alert(data.mensaje);
        }
    })
    .catch(err => console.error('Error:', err));
}

function actualizarVistaCarrito() {
    fetch('carrito.php?accion=obtener')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById("estado-carrito").textContent = 
                `Productos en carrito: ${data.total_items}`;
            
            const itemsDiv = document.getElementById("cart-items");
            itemsDiv.innerHTML = "";
            if (data.carrito.length === 0) {
                itemsDiv.innerHTML = "<p style='color:#999;'>El carrito esta vacio</p>";
            } else {
                data.carrito.forEach(item => {
                    const div = document.createElement("div");
                    div.className = "cart-item";
                    div.innerHTML = `
                        <span>${item.nombre} x${item.cantidad}</span>
                        <span>$${(item.precio * item.cantidad).toLocaleString()}
                        <button class="btn-eliminar" onclick="eliminarDelCarrito(${item.id})">✕</button></span>
                    `;
                    itemsDiv.appendChild(div);
                });
            }
            document.getElementById("cart-total").textContent = data.total_precio.toLocaleString();
        }
    });
}

function eliminarDelCarrito(id) {
    const formData = new FormData();
    formData.append('accion', 'eliminar');
    formData.append('id', id);

    fetch('carrito.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        actualizarVistaCarrito();
        cargarProductosDesdeBD(); // Recargar productos
    });
}

function toggleCarrito() {
    const detalle = document.getElementById("cart-details");
    detalle.style.display = detalle.style.display === "none" || detalle.style.display === "" ? "block" : "none";
    if (detalle.style.display === "block") {
        actualizarVistaCarrito();
    }
}

// ==============
// Evento
// ==============
document.getElementById("search-btn").addEventListener("click", buscarProductos);
document.getElementById("product-search").addEventListener("keyup", buscarProductos);

window.addEventListener("load", () => {
    document.getElementById("promotion-message").textContent =
        "Promocion 30% de descuento solo en productos seleccionados.";
    cargarProductosDesdeBD(); // Cargar desde BD al iniciar
    actualizarVistaCarrito();
});

// ==============
// Actualizar el stock luego de volver de pagar
// ==============
window.addEventListener("pageshow", (event) => {
    // Se ejecuta al volver a la pagina
    if (event.persisted) {
        cargarProductosDesdeBD();
        actualizarVistaCarrito();
    }
});