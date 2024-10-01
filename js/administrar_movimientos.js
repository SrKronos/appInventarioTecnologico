$(document).ready(function () {
  let productosTbody = $("#productos-tbody");
  const buscarProductoInput = $("#buscarProducto");

  let lista_productos = [];
  let lista_productos_filtrados = [];
  let textobuscado = "";
  let tipoMovimiento = $("#inputTipo").val();
  obtenerDatos();

  // Evento de búsqueda
  buscarProductoInput.on("input", function () {
    const query = $(this).val().toLowerCase();
    textobuscado = query;
    obtenerDatos(); // Recargar datos con el texto buscado
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
        opt: "obtenerMovimientos",
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

      let campoProducto = $(
        '<input type="text" readonly="true" class="fomr-control" value="' +
          producto.nombre +
          '">'
      );
      row.append($("<td>").append(campoProducto));

      let productotipo = producto.tipo === "I" ? "INGRESO" : "EGRESO";
      let campoTipo = $(
        '<input type="text" readonly="true" class="form-control" value="' +
          productotipo +
          '">'
      );
      row.append($("<td>").append(campoTipo));

      let campoCantidad = $(
        '<input type="text" readonly="true" class="fomr-control" value="' +
          producto.cantidad +
          '">'
      );
      row.append($("<td>").append(campoCantidad));

      let campoFecha = $(
        '<input type="text" readonly="true" class="fomr-control" value="' +
          producto.fecha +
          '">'
      );
      row.append($("<td>").append(campoFecha));

      let btnGuardar = $('<button class="btnDelete">❌</button>');
      btnGuardar.on("click", function () {
        eliminarMovimiento(lista_productos_filtrados[index]);
      });
      row.append($("<td>").append(btnGuardar));
      productosTbody.append(row);
    });
  }

  function eliminarMovimiento_old(datos_producto) {
    $.ajax({
      url: "../controller/administrar.php",
      type: "POST",
      data: { opt: "eliminarMovimiento", producto: datos_producto },
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
        console.error("Error en la solicitud:", error);
        Swal.fire({
          title: "Notificación",
          text: error.responseText || "Error en la solicitud",
          icon: "error",
        });
      },
    });
  }
  function eliminarMovimiento(datos_producto) {
    // Mostrar el SweetAlert de confirmación
    Swal.fire({
      title: "¿Estás seguro?",
      text: "¡No podrás revertir esto!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Sí, eliminarlo!",
    }).then((result) => {
      // Si el usuario confirma la eliminación
      if (result.isConfirmed) {
        // Realizar la solicitud AJAX para eliminar el movimiento
        $.ajax({
          url: "../controller/administrar.php",
          type: "POST",
          data: { opt: "eliminarMovimiento", producto: datos_producto },
          dataType: "json",
          success: function (data) {
            console.log(data);
            if (data.success) {
              // Mostrar mensaje de éxito
              Swal.fire({
                title: "¡Eliminado!",
                text: "El movimiento ha sido eliminado.",
                icon: "success",
              });
              obtenerDatos(); // Actualizar la lista después de eliminar
            } else {
              // Mostrar mensaje de advertencia si hay un error
              Swal.fire({
                title: "Notificación",
                text: data.message,
                icon: "warning",
              });
            }
          },
        });
      }
    });
  }

  // Eventos de búsqueda de productos para agregar movimiento
  let productoActivo = {};

  $("#inputProducto").on("input", function () {
    let query = $(this).val();
    let inlimite = document.getElementById("inlimite").value;

    if (query.length >= 2) {
      $.ajax({
        url: "../controller/administrar.php",
        type: "POST",
        data: { opt: "buscarProducto", limite: inlimite, texto: query },
        dataType: "json",
        success: function (data) {
          if (Array.isArray(data.productos) && data.productos.length > 0) {
            let listaHtml = "";
            data.productos.forEach(function (producto) {
              listaHtml += `<li data-id="${producto.id}" data-nombre="${producto.nombre}" data-stock="${producto.stock}">
                              ${producto.nombre} (Stock: ${producto.stock})
                            </li>`;
            });
            $("#listaProductos").html(listaHtml).show();
          } else {
            $("#listaProductos")
              .html("<li>No se encontraron productos</li>")
              .show();
          }
        },
        error: function (error) {
          console.error("Error en la búsqueda de productos", error);
        },
      });
    } else {
      $("#listaProductos").hide();
    }
  });

  $("#listaProductos").on("click", "li", function () {
    let idProducto = $(this).data("id");
    let nombreProducto = $(this).data("nombre");
    let stockProducto = $(this).data("stock");

    productoActivo = {
      id: idProducto,
      nombre: nombreProducto,
      stock: stockProducto,
      cantidad: $("#inputCantidad").val(),
      tipo: $("#inputTipo").val(),
    };

    $("#inputProducto").val(nombreProducto);
    $("#labelStock").val(stockProducto);
    $("#listaProductos").hide();
  });

  $("#btnGuardarMovimiento").on("click", function () {
    productoActivo.cantidad = $("#inputCantidad").val(); // Actualizar cantidad
    let tipoMovimiento = $("#inputTipo").val(); // Obtener el valor del tipo de movimiento (I o E)

    // Validar que se haya seleccionado un tipo de movimiento
    if (!tipoMovimiento) {
      Swal.fire({
        title: "Error",
        text: "Debes seleccionar un tipo de movimiento",
        icon: "warning",
      });
      return;
    }

    productoActivo.tipo = tipoMovimiento; // Actualizar tipo de movimiento en productoActivo

    // Validar que se haya seleccionado un producto
    if (!productoActivo.id) {
      Swal.fire({
        title: "Error",
        text: "Debes seleccionar un producto",
        icon: "warning",
      });
      return;
    }

    // Validar que la cantidad sea válida
    if (!productoActivo.cantidad || productoActivo.cantidad <= 0) {
      Swal.fire({
        title: "Error",
        text: "Debes ingresar una cantidad válida",
        icon: "warning",
      });
      return;
    }

    // Enviar los datos por AJAX
    $.ajax({
      url: "../controller/administrar.php",
      type: "POST",
      data: {
        opt: "registrarMovimiento",
        producto: productoActivo, // Incluir tipoMovimiento en el objeto productoActivo
      },
      dataType: "json",
      success: function (data) {
        if (data.success) {
          Swal.fire({
            title: "Notificación",
            text: data.message,
            icon: "success",
          });
          limpiarCampos();
          obtenerDatos(); // Refrescar la lista de productos
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
          text: error.responseText,
          icon: "error",
        });
      },
    });
  });

  function limpiarCampos() {
    // Limpiar los campos de entrada
    $("#inputProducto").val(""); // Limpiar el campo de producto
    $("#labelStock").val(""); // Limpiar el stock
    $("#inputCantidad").val("1"); // Reiniciar la cantidad a 1
    $("#inputTipo").val(""); // Reiniciar el select del tipo de movimiento a vacío
    $("#listaProductos").hide(); // Ocultar las sugerencias

    // Limpiar las variables usadas
    productoActivo = {}; // Reiniciar el objeto productoActivo
    textobuscado = ""; // Reiniciar la variable de búsqueda

    // Deshabilitar botones o cualquier otra acción que necesite ser reiniciada
    $("#btnGuardarMovimiento").prop("disabled", true); // Opcional: Deshabilitar botón de guardar
  }

  function pruebaDivAPdf() {
    const { jsPDF } = window.jspdf; // Acceso al espacio de nombres jsPDF
    var pdf = new jsPDF("p", "pt", "letter");
    var source = document.getElementById("tabla-exportar");

    // Agregar el encabezado al PDF
    pdf.setFontSize(18);
    pdf.text("REPORTE DE MOVIMIENTOS", 40, 30); // X: 40, Y: 30

    html2canvas(source)
      .then((canvas) => {
        var imgData = canvas.toDataURL("image/png");

        // Obtener el ancho del canvas
        var imgWidth = canvas.width;
        var imgHeight = canvas.height;

        // Calcular la relación de aspecto
        var pdfWidth = 595; // Ancho de A4 en puntos
        var scaleFactor = pdfWidth / imgWidth; // Escala para ajustar el ancho

        // Calcular el nuevo alto proporcionalmente
        var pdfHeight = imgHeight * scaleFactor;

        pdf.addImage(imgData, "PNG", 0, 50, pdfWidth, pdfHeight); // Ajusta la posición Y para que no se superponga con el encabezado
        pdf.save("reporte_movimientos.pdf");
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
