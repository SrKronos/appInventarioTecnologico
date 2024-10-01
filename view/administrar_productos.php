<?php
session_start();
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['nombre']) || empty($_SESSION['usuario']['fechahora'])) {
    header("Location: index.php");
    exit();
}
?>

<head>
    <meta charset="UTF-8">
    <title>Administrar Productos</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="../js/administrar_productos.js?<?php echo time(); ?>"></script>
</head>

<h2 class="titulo-pagina">Productos 👨‍💻👩‍💻🖨️🖱️⌨️📱💾</h2>
<!-- <div class="flotante">
    <button id="btnCerrarSesion" class="btnCerrarSesion" title="Cerrar Sesión">👋</button>
</div> -->
<div class="banner-opciones">
    <div class="area_interactividad form-control">
        <button id="agregarProductoBtn" class="btnAgregar">+Producto</button>
        <input type="text" id="buscarProducto" class="inBuscar" placeholder="Buscar producto...">
    </div>

    <div class="area_filtros">
        <div class="form-control">
            <label for="inlimite">Limite:</label>
            <input type="number" name="inlimite" id="inlimite" min=0 value="20">
        </div>          
    </div>
</div>
<button id="btnGenerarPDF">Generar Reporte PDF</button>
<table id="tabla-exportar" class="tabla-exportar">
    <thead>
        <tr>
            <th>Código</th>
            <th>Activo</th>
            <th>Producto</th>    
            <th>Descripción</th>                                                            
            <th>Precio</th>
            <th>Stock</th>            
            <th>Acción</th>
        </tr>
    </thead>
    <tbody id="productos-tbody" class="productos-tbody">
    </tbody> 
</table>
