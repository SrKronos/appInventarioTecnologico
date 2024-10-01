$(document).ready(function () {
    $.ajax({
        url: '../controlador/controlador.grupo.php', // Ruta al script que cierra la sesión
        type: 'POST',
        data: {
            opt: 'obtenerProducto',
            grupo:idGrupo,
            marca:marca
        },
        dataType: 'json',
        success: function (response) {
            crearElementoProducto(response);
        },
        error: function (error) {
            console.log('Error:', error);
        }
    });
});

function crearElementoProducto(productos) {
    // Crear la sección fuera del bucle forEach
    //const section = document.createElement('section');
    let contenedorproductos = document.querySelector('section');
    productos.forEach(producto => {
        // Crear la tarjeta del producto
        const cardProducto = document.createElement('div');
        cardProducto.className = 'cardProducto';
        // Crear y llenar el div del título
        const divTitulo = document.createElement('div');
        divTitulo.className = 'titulo';
        const spanNombreProducto = document.createElement('span');
        spanNombreProducto.className = 'nombreproducto';
        spanNombreProducto.textContent = producto.producto;
        const spanPrecioProducto = document.createElement('span');
        spanPrecioProducto.className = 'precioproducto';
        spanPrecioProducto.textContent = producto.precio1;
        divTitulo.appendChild(spanNombreProducto);
        divTitulo.appendChild(spanPrecioProducto);

        // Crear y llenar el div de la descripción
        const divDescripcion = document.createElement('div');
        divDescripcion.className = 'descripcion';
        const pDescripcion = document.createElement('p');
        pDescripcion.textContent = producto.descripcion;
        divDescripcion.appendChild(pDescripcion);

        // Anidar elementos de la tarjeta
        cardProducto.appendChild(divTitulo);
        cardProducto.appendChild(divDescripcion);

        // Añadir la tarjeta a la sección
        contenedorproductos.appendChild(cardProducto);
    });

   
}
