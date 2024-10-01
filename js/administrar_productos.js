//Usado para poder interactuar con la ventana de administrar productos
$(document).ready(function () {
  let productosTbody = $("#productos-tbody");
  const buscarProductoInput = $("#buscarProducto");

  let opciones = [
    { valor: 0, texto: "Inactivo" },
    { valor: 1, texto: "Activo" },
  ];

  let lista_productos = [];
  let lista_productos_filtrados = [];

  let textobuscado = "";

  obtenerDatos();
  // Agregar nuevo producto
  $("#agregarProductoBtn").click(function () {
    //console.log("clic");
    // Crear un objeto de nuevo producto con valores vacíos o predeterminados
    let nuevo_producto = {
      id: "",
      estado: 1,
      nombre: "",
      descripcion: "",
      precio: 0.0,
      stock: 0,
    };

    // Agregar nuevo producto a la lista filtrada y a la tabla
    lista_productos_filtrados.unshift(nuevo_producto);
    renderProductos();
  });

  // Agregar evento de búsqueda
  buscarProductoInput.on("input", function () {
    const query = $(this).val().toLowerCase();
    textobuscado = query;
    obtenerDatos();
    lista_productos_filtrados = lista_productos.filter((producto) =>
      producto.nombre.toLowerCase().includes(query)
    );
    renderProductos();
  });

  function obtenerDatos() {
    let inlimite = document.getElementById("inlimite").value;
    $.ajax({
      url: "../controller/administrar.php",
      type: "POST",
      data: {
        opt: "obtenerProductos",
        limite: inlimite,
        texto: textobuscado,
      },
      dataType: "json",
      success: function (data) {
        lista_productos = data.productos;
        lista_productos_filtrados = lista_productos;
        renderProductos();
      },
      error: function (error) {
        console.error("Error:", error);
      },
    });
  }

  function renderProductos() {
    productosTbody.empty();
    ini = "<td>";
    fin = "</td>";

    lista_productos_filtrados.forEach(function (producto, index) {
      let row = $("<tr>");
      row.append(ini + producto.id + fin);

      // Agregar select de campoActivo
      let selectCampoActivo = $('<select class="form-control">');
      opciones.forEach(function (opcion) {
        let optionElement = $(
          '<option value="' + opcion.valor + '">' + opcion.texto + "</option>"
        );

        if (producto.estado) {
          optionElement.attr("selected", true);
        }
        selectCampoActivo.append(optionElement);
      });

      selectCampoActivo.on("change", function () {
        lista_productos_filtrados[index].estado = $(this).val();
        $(this).addClass("modificado");
      });

      row.append($("<td>").append(selectCampoActivo));

      //Campo editable de producto es decir el nombre
      let campoProducto = $(
        '<input type="text" class="fomr-control" value="' +
          producto.nombre +
          '">'
      );
      campoProducto.on("change", function () {
        lista_productos_filtrados[index].nombre = $(this).val();
        $(this).addClass("modificado");
      });
      row.append($("<td>").append(campoProducto));

      // Interactividad Descripcion
      let campoDescripcion = $(
        '<textarea style="width: 100%" class="form-control">' +
          producto.descripcion +
          "</textarea>"
      );
      campoDescripcion.on("change", function () {
        lista_productos_filtrados[index].descripcion = $(this).val();
        $(this).addClass("modificado");
      });
      row.append($("<td>").append(campoDescripcion));

      //Interactivdad Precio1
      let campoPrecio = $(
        '<input type="number" class="fomr-control"  style="width: 70px" step="0.01" value=' +
          producto.precio +
          ">"
      );
      campoPrecio.on("change", function () {
        lista_productos_filtrados[index].precio = $(this).val();
        $(this).addClass("modificado");
      });
      row.append($("<td>").append(campoPrecio));

      //Interactivdad Precio1
      let campoStock = $(
        '<input type="number" class="fomr-control"  style="width: 70px" step="0.01" value=' +
          producto.stock +
          ">"
      );
      campoStock.on("change", function () {
        lista_productos_filtrados[index].stock = $(this).val();
        $(this).addClass("modificado");
      });
      row.append($("<td>").append(campoStock));

      //boton de guardar
      let btnGuardar = $('<button class="btnSave">💾</button>');
      btnGuardar.on("click", function () {
        //funcion que guarde los datos envie el index del
        guardarProducto(lista_productos_filtrados[index]);
      });
      row.append($("<td>").append(btnGuardar));
      productosTbody.append(row);
    });
  }

  // Guardar Productos
  function guardarProducto(datos_producto) {
    $.ajax({
      url: "../controller/administrar.php",
      type: "POST",
      data: { opt: "guardarProducto", producto: datos_producto },
      dataType: "json",
      success: function (data) {
        if (data.success) {
          Swal.fire({
            title: "Notificación",
            text: data.message,
            icon: "success",
          });
          obtenerDatos();
        } else {
          Swal.fire({
            title: "Notificación",
            text: data.message,
            icon: "warning",
          });
        }
      },
      error: function (error) {
        Swal.fire({
          title: "Notificación",
          text: error,
          icon: "error",
        });
      },
    });
  }

  // Toggle visibility of options on click
  $(".selected-option").on("click", function () {
    $(this).siblings(".options-container").toggle();
  });

  // Cerrar el menú desplegable si se hace clic fuera
  $(document).on("click", function (event) {
    if (!$(event.target).closest(".custom-select").length) {
      $(".options-container").hide();
    }
  });

  // Manejar los custom selects de grupos y marcas
  $(document).on("change", ".checkbox", function () {
    obtenerDatos();
  });

  //Cerrar Sesion
  $("#btnCerrarSesion").click(function () {
    $.ajax({
      url: "../controller/inicio.php",
      type: "POST",
      datatype: "JSON",
      data: {
        opt: "cerrarsesion",
      },
      success: function () {
        window.location.href = "../index.php";
      },
      error: function () {
        console.log("Error al Cerrar Sesión");
      },
    });
  });

  function generarReportePDF() {
    const { jsPDF } = window.jspdf;

    // Crear una nueva instancia de jsPDF
    const doc = new jsPDF();

    // Agregar un título al PDF
    doc.setFontSize(20);
    doc.text("Reporte de Productos", 10, 10);
    doc.setFontSize(12);
    doc.text("Lista de productos disponibles:", 10, 20);

    // Obtener el contenido de la tabla
    const productosTable = document.getElementById("productos-tbody");

    // Crear una tabla en el PDF
    let rowIndex = 30; // Posición vertical inicial
    doc.setFont("helvetica", "normal");

    // Agregar encabezados de la tabla
    doc.setFontSize(12);
    const encabezados = [
      "Código",
      "Activo",
      "Producto",
      "Descripción",
      "Precio",
      "Stock",
    ];
    const encabezadosAncho = [20, 30, 50, 60, 30, 30]; // Anchos de columnas

    encabezados.forEach((titulo, index) => {
      doc.text(
        titulo,
        10 + encabezadosAncho.slice(0, index).reduce((a, b) => a + b, 0),
        rowIndex
      );
    });
    rowIndex += 10; // Espacio entre encabezados y filas

    // Iterar sobre las filas de la tabla y agregar al PDF
    const filas = productosTable.getElementsByTagName("tr");
    for (let i = 0; i < filas.length; i++) {
      const celdas = filas[i].getElementsByTagName("td");
      const filaDatos = [];

      for (let j = 0; j < celdas.length; j++) {
        filaDatos.push(celdas[j].innerText); // Agregar el contenido de cada celda a un array
      }

      // Agregar los datos de la fila al PDF
      filaDatos.forEach((dato, index) => {
        doc.text(
          dato,
          10 + encabezadosAncho.slice(0, index).reduce((a, b) => a + b, 0),
          rowIndex
        );
      });
      rowIndex += 10; // Espacio entre filas
    }

    // Guardar el PDF
    doc.save("reporte_productos.pdf");
  }

  function pruebaDivAPdf() {
    const { jsPDF } = window.jspdf; // Acceso al espacio de nombres jsPDF
    var pdf = new jsPDF("p", "pt", "letter");
    var source = document.getElementById("tabla-exportar");

    // Agregar el encabezado al PDF
    pdf.setFontSize(18);
    pdf.text("REPORTE DE PRODUCTOS", 40, 30); // X: 40, Y: 30

    html2canvas(source)
      .then((canvas) => {
        var imgData = canvas.toDataURL("image/png");

        // Obtener el ancho del canvas
        var imgWidth = canvas.width;
        var imgHeight = canvas.height;

        // Calcular el ancho máximo para A4
        var pdfWidth = 595; // Ancho de A4 en puntos
        var scaleFactor = pdfWidth / imgWidth; // Escala para ajustar el ancho

        // Calcular el nuevo alto proporcionalmente
        var pdfHeight = imgHeight * scaleFactor;

        // Agregar la imagen al PDF, ajustando el ancho y alto
        pdf.addImage(imgData, "PNG", 0, 50, pdfWidth, pdfHeight); // Ajusta la posición Y para que no se superponga con el encabezado
        pdf.save("reporte_productos.pdf");
      })
      .catch((error) => {
        console.error("Error al generar el canvas:", error);
      });
  }

  // Event listener para el botón de generar reporte
  document
    .getElementById("btnGenerarPDF")
    .addEventListener("click", function () {
      pruebaDivAPdf();
    });
});
