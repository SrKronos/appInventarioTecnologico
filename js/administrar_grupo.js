$(document).ready(function () {
  let gruposTbody = $("#grupos-tbody");
  const buscarGrupoInput = $("#buscarGrupo");

  let lista_grupos = [];
  let lista_grupos_filtrados = [];
  let textobuscado = "";

  obtenerGruposTodos();
  renderCustomSelectGrupo();
  function obtenerGrupos() {
    $.ajax({
      url: "../controlador/controlador.grupo.php",
      type: "POST",
      data: {
        opt: "obtenerGrupos",
      },
      dataType: "json",
      success: function (data) {
        lista_grupos = data;
        renderCustomSelectGrupo();
      },
      error: function (error) {
        console.error("Error:", error);
      },
    });
  }
  function renderCustomSelectGrupo() {
    const optionsContainer = $("#custom-select-grupos").find(
      ".options-container"
    );
    optionsContainer.empty();

    lista_grupos.forEach(function (grupo) {
      let option = $('<div class="option">')
        .append("<span>" + grupo.etiqueta + "</span>")
        .append(
          '<input type="checkbox" class="checkbox" data-id="' +
            grupo.codigo +
            '">'
        );
      optionsContainer.append(option);
    });
  }

  // Agregar nuevo grupo
  $("#agregarGrupoBtn").click(function () {
    let nuevo_grupo = {
      codigo: 0,
      grupo: "",
      grupo_padre: "",
      activo: 1,
      descripcion: "",
      etiqueta: "",
      img: "",
    };

    lista_grupos_filtrados.unshift(nuevo_grupo);
    renderGrupos();
  });

  // Agregar evento de búsqueda
  buscarGrupoInput.on("input", function () {
    const query = $(this).val().toLowerCase();
    textobuscado = query;
    obtenerGrupos();
    lista_grupos_filtrados = lista_grupos.filter((grupo) =>
      grupo.grupo.toLowerCase().includes(query)
    );
    renderGrupos();
  });

  // Función para obtener grupos
  function obtenerGruposTodos() {
    $.ajax({
      url: "../controlador/controlador.grupo.php",
      type: "POST",
      data: {
        opt: "obtenerGruposTodos",
        texto: textobuscado,
      },
      dataType: "json",
      success: function (data) {
        if (data && Array.isArray(data)) {
          lista_grupos = data;
          lista_grupos_filtrados = lista_grupos;
          renderGrupos();
        } else {
          console.error("La respuesta no contiene un array de grupos.");
        }
      },
      error: function (error) {
        console.error("Error:", error);
      },
    });
  }

  // Renderizar grupos
  function renderGrupos() {
    gruposTbody.empty();
    lista_grupos_filtrados.forEach(function (grupo, index) {
      let row = $("<tr>");
      row.append("<td>" + grupo.codigo + "</td>");

      let campoOrden = $(
        '<input type="number" class="form-control" value="' + grupo.orden + '">'
      );
      campoOrden.on("change", function () {
        lista_grupos_filtrados[index].orden = $(this).val();
        $(this).addClass("modificado");
      });
      row.append($("<td>").append(campoOrden));

      // Select para activo/inactivo
      let selectActivo = $('<select class="form-control">');
      selectActivo.append(
        '<option value="1"' +
          (grupo.activo == 1 ? "selected" : "") +
          ">Activo</option>"
      );
      selectActivo.append(
        '<option value="0"' +
          (grupo.activo == 0 ? "selected" : "") +
          ">Inactivo</option>"
      );
      selectActivo.on("change", function () {
        lista_grupos_filtrados[index].activo = $(this).val();
        $(this).addClass("modificado");
      });

      row.append($("<td>").append(selectActivo));

      // Campo editable de grupo
      let campoGrupo = $(
        '<input type="text" class="form-control" value="' + grupo.grupo + '">'
      );
      campoGrupo.on("change", function () {
        lista_grupos_filtrados[index].grupo = $(this).val();
        $(this).addClass("modificado");
      });
      row.append($("<td>").append(campoGrupo));

      //agregar select de grupo padre
      let selectGrupoPadre = $('<select class="form-control">');
      selectGrupoPadre.append(
        '<option value="">Seleccionar Grupo Padre</option>'
      );
      lista_grupos.forEach(function (grupito, index) {
        let opcion = $(
          '<option value="' +
            grupito.codigo +
            '">' +
            grupito.grupo +
            "</option>"
        );
        if (grupito.codigo === grupo.grupo_padre) {
          opcion.attr("selected", "selected");
        }
        selectGrupoPadre.append(opcion);
      });

      selectGrupoPadre.on("change", function (e) {
        lista_productos_filtrados[index].idgrupo = $(this).val();
        $(this).addClass("modificado");
      });
      row.append($("<td>").append(selectGrupoPadre));

      // Campo editable de etiqueta
      let campoEtiqueta = $(
        '<input type="text" class="form-control" value="' +
          grupo.etiqueta +
          '">'
      );
      campoEtiqueta.on("change", function () {
        lista_grupos_filtrados[index].etiqueta = $(this).val();
        $(this).addClass("modificado");
      });
      row.append($("<td>").append(campoEtiqueta));

      //Campo Imagen Editable
      let campoImagen = $('<a href="#" class="img-link">');
      let imgPreview = $(
        '<img src="../img/imggrupos/' + grupo.img + '" class="img_preview">'
      );
      campoImagen.append(imgPreview);

      // Manejar caso de imagen vacía
      if (!grupo.img) {        
        imgPreview.attr("src", "../img/imggrupos/placeholder.jpg");
      }

      let inputFile = $('<input type="file" style="display: none;">');
      $("body").append(inputFile);

      campoImagen.on("click", function (event) {
        event.preventDefault();
        inputFile.trigger("click");
      });

      // inputFile.on("change", function (event) {
      //   const file = event.target.files[0];
      //   const reader = new FileReader();
      //   $(this).addClass("modificado");
      //   reader.onload = function (e) {
      //     imgPreview.attr("src", e.target.result);
      //     // Aquí actualizarás el nombre del archivo si es necesario
      //     // Por ejemplo, si deseas guardar el archivo en el servidor, puedes enviar una solicitud
      //     // AJAX con el archivo y obtener el nuevo nombre de archivo
      //   };

      //   reader.readAsDataURL(file);
      // });

      inputFile.on("change", function (event) {
        if (grupo.grupo.trim()) {
          const file = event.target.files[0];
          const reader = new FileReader();

          // Leer y mostrar la imagen seleccionada
          reader.onload = function (e) {
            imgPreview.attr("src", e.target.result);
          };

          reader.readAsDataURL(file);

          // Crear un objeto FormData para enviar el archivo y el nombre del grupo
          let formData = new FormData();
          formData.append("file", file);
          formData.append("grupo", grupo.grupo);
          formData.append("opt", "actualziarImagenGrupo");
          // Enviar el nombre del grupo para formar el nombre del archivo

          // Enviar la imagen al servidor usando AJAX
          $.ajax({
            url: "../controlador/controlador.grupo.php", // Cambia a la ruta correcta
            type: "POST",
            data: formData,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function (data) {
              console.log("Succes:"+data.success);
              console.log("Message:"+data.success);
              if (data.success===true) {
                // Manejar la respuesta del servidor (puedes actualizar el nombre de archivo si es necesario)
                Swal.fire({
                  title: "Notificación",
                  text: data.message,
                  icon: "success",
                });
              } else {
                // Manejar la respuesta del servidor (puedes actualizar el nombre de archivo si es necesario)
                Swal.fire({
                  title: "Notificación",
                  text: data.message,
                  icon: "warning",
                });
              }
            },
            error: function (xhr, status, error) {
              Swal.fire({
                title: "Notificación",
                text: "Error al actualizar la imagen:" + error,
                icon: "warning",
              });
            },
          });
        }
      });

      row.append($("<td>").append(campoImagen));

      // Botón de guardar
      let btnGuardar = $('<button class="btnSave">💾</button>');
      btnGuardar.on("click", function () {
        guardarGrupo(lista_grupos_filtrados[index]);
      });
      row.append($("<td>").append(btnGuardar));

      gruposTbody.append(row);
    });
  }

  // Guardar grupo
  function guardarGrupo(datos_grupo) {
    datos_grupo.img = datos_grupo.grupo+".jpg";
    $.ajax({
      url: "../controlador/controlador.grupo.php",
      type: "POST",
      data: {
        opt: "guardarGrupo",
        grupo: datos_grupo,
      },
      dataType: "json",
      success: function (data) {
        if (data.success) {
          Swal.fire({
            title: "Notificación",
            text: "Grupo "+datos_grupo.grupo+" guardado!",
            icon: "success",
          });
          //console.log("Grupo guardado exitosamente.");
          obtenerGrupos();
        } else {
          console.error("Error al guardar el grupo:", data.message);
          Swal.fire({
            title: "Notificación",
            text: "Grupo "+datos_grupo.grupo+" no guardado,"+data.message,
            icon: "warning",
          });
        }
      },
      error: function (error) {
        Swal.fire({
          title: "Notificación",
          text: "Error al intentar guardar "+datos_grupo.grupo+", error:"+error,
          icon: "error",
        });
      },
    });
  }

});
