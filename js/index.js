$(document).ready(function () {
    $("#btnLogin").click(function () {
      let usuario = document.getElementById("usuario").value;
      let clave = document.getElementById("clave").value;
  
      if (validarFormulario(usuario, clave)) {
        $.ajax({
          url: "controller/inicio.php",
          type: "POST",
          datatype: "JSON",
          data: {
            opt: "login",
            usuario: usuario,
            clave: clave,
          },
          success: function (data) {
            data = JSON.parse(data);
            let mensaje = data.mensaje;
            let tipo = data.response;
            mostrarMensaje(mensaje, tipo);
          },
          error: function (error) {          
            mostrarMensaje(error, "error");
          },
        });
      } else {
        mostrarMensaje("Los campos Correo y Clave son obligatorios", "advertencia");
      }
    });
  
    function validarFormulario(usuario, clave) {
      return usuario.trim() !== "" && clave.trim() !== "";
    }
  
    function mostrarMensaje(mensaje, tipo) {  
      switch (tipo) {
        case "ok":
          Swal.fire({
            title: "Bienvenido",
            text: mensaje,
            icon: "success",
          }).then((result) => {
            if (result.isConfirmed) {
              window.location.href = "/appInventarioTienda/view/principal.php";
            }
          });
          break;
        case "error":
        case "advertencia":
        case "info":
          Swal.fire({
            title: "Notificación",
            text: mensaje,
            icon: tipo === "advertencia" ? "warning" : tipo,
          });
          break;
      }
    }
  });