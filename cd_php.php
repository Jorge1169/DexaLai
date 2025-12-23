<?php
// config
include "config/conexiones.php";

if(isset($_POST['factura'])){///primera parte

	$factura = $_POST['factura'];

	$AlertCo00 = $conn_mysql->query("SELECT * FROM compras WHERE fact = '$factura' AND status = '1'");
	$AlertCo01 = mysqli_fetch_array($AlertCo00);
	if (empty($AlertCo01['id_compra'])) {
		?>
		<span class="badge text-bg-success">Libre</span>
		<?php	
	}else {
		?>
		<span class="badge text-bg-warning">Ocupado</span>
		<?php
	}
	
}
if(isset($_POST['facturav'])){///primera parte

	$facturav = $_POST['facturav'];

	$AlertCo00 = $conn_mysql->query("SELECT * FROM ventas WHERE fact = '$facturav' AND status = '1'");
	$AlertCo01 = mysqli_fetch_array($AlertCo00);
	if (empty($AlertCo01['id_compra'])) {
		?>
		<span class="badge text-bg-success">Libre</span>
		<?php	
	}else {
		?>
		<span class="badge text-bg-warning">Ocupado</span>
		<?php
	}
	
}

if(isset($_POST['cod_al'])){///primera parte

	$cod_al = $_POST['cod_al'];

	$AlertCo00 = $conn_mysql->query("SELECT * FROM direcciones WHERE cod_al = '$cod_al' AND status = '1'");
	$AlertCo01 = mysqli_fetch_array($AlertCo00);
	if (empty($AlertCo01['id_direc'])) {
		?>
		<span class="badge text-bg-success">Libre</span>
		<?php	
	}else {
		?>
		<span class="badge text-bg-warning">Ocupado</span>
		<?php
	}
	
}
if(isset($_POST['codigo_CLIENTE'])){
	$codigo_CLIENTE = $_POST['codigo_CLIENTE'];
    $zona = $_POST['zona'] ?? ''; // Agregar este parámetro

    // Si no se envía zona, mantener el comportamiento original
    if(empty($zona)) {
    	$AlertCo00 = $conn_mysql->query("SELECT * FROM clientes WHERE cod = '$codigo_CLIENTE' AND status = '1'");
    	$AlertCo01 = mysqli_fetch_array($AlertCo00);
    	if (empty($AlertCo01['id_cli'])) {
    		?>
    		<span class="badge text-bg-success">Libre</span>
    		<?php	
    	} else {
    		?>
    		<span class="badge text-bg-warning">Ocupado</span>
    		<?php
    	}
    } else {
        // Verificar si existe en la misma zona
    	$AlertCo00 = $conn_mysql->query("SELECT * FROM clientes WHERE cod = '$codigo_CLIENTE' AND zona = '$zona' AND status = '1'");
    	$AlertCo01 = mysqli_fetch_array($AlertCo00);
    	if (empty($AlertCo01['id_cli'])) {
    		?>
    		<span class="badge text-bg-success">Libre</span>
    		<?php	
    	} else {
    		?>
    		<span class="badge text-bg-warning">Ocupado en esta zona</span>
    		<?php
    	}
    }
}
if(isset($_POST['codigo_PROVEEDOR'])){
	$codigo_PROVEEDOR = $_POST['codigo_PROVEEDOR'];
    $zona = $_POST['zona'] ?? ''; // Agregar este parámetro

    // Si no se envía zona, mantener el comportamiento original
    if(empty($zona)) {
    	$AlertCo00 = $conn_mysql->query("SELECT * FROM proveedores WHERE cod = '$codigo_PROVEEDOR' AND status = '1'");
    	$AlertCo01 = mysqli_fetch_array($AlertCo00);
    	if (empty($AlertCo01['id_prov'])) {
    		?>
    		<span class="badge text-bg-success">Libre</span>
    		<?php	
    	} else {
    		?>
    		<span class="badge text-bg-warning">Ocupado</span>
    		<?php
    	}
    } else {
        // Verificar si existe en la misma zona
    	$AlertCo00 = $conn_mysql->query("SELECT * FROM proveedores WHERE cod = '$codigo_PROVEEDOR' AND zona = '$zona' AND status = '1'");
    	$AlertCo01 = mysqli_fetch_array($AlertCo00);
    	if (empty($AlertCo01['id_prov'])) {
    		?>
    		<span class="badge text-bg-success">Libre</span>
    		<?php	
    	} else {
    		?>
    		<span class="badge text-bg-warning">Ocupado en esta zona</span>
    		<?php
    	}
    }
}
if(isset($_POST['codProd'])){ ///primera parte

	$codProd = $_POST['codProd'];

	$AlertCo00 = $conn_mysql->query("SELECT * FROM productos WHERE cod = '$codProd' AND status = '1'");
	$AlertCo01 = mysqli_fetch_array($AlertCo00);
	if (empty($AlertCo01['id_prod'])) {
		?>
		<span class="badge text-bg-success">Libre</span>
		<?php	
	}else {
		?>
		<span class="badge text-bg-warning">Ocupado</span>
		<?php
	}
	
}
if(isset($_POST['placas'])){

	$placas = $_POST['placas'];
    $zona = $_POST['zona'] ?? ''; // Agregar este parámetro

    // Si no se envía zona, mantener el comportamiento original
    if(empty($zona)) {
    	$AlertCo00 = $conn_mysql->query("SELECT * FROM transportes WHERE placas = '$placas' AND status = '1'");
    	$AlertCo01 = mysqli_fetch_array($AlertCo00);
    	if (empty($AlertCo01['id_transp'])) {
    		?>
    		<span class="badge text-bg-success">Libre</span>
    		<?php	
    	} else {
    		?>
    		<span class="badge text-bg-warning">Ocupado</span>
    		<?php
    	}
    } else {
        // Verificar si existe en la misma zona
    	$AlertCo00 = $conn_mysql->query("SELECT * FROM transportes WHERE placas = '$placas' AND zona = '$zona' AND status = '1'");
    	$AlertCo01 = mysqli_fetch_array($AlertCo00);
    	if (empty($AlertCo01['id_transp'])) {
    		?>
    		<span class="badge text-bg-success">Libre</span>
    		<?php	
    	} else {
    		?>
    		<span class="badge text-bg-warning">Ocupado en esta zona</span>
    		<?php
    	}
    }
}

// Agregar esta sección para validar código de almacén
if(isset($_POST['codigo_ALMACEN'])){
    $codigo_ALMACEN = $_POST['codigo_ALMACEN'];
    $zona = $_POST['zona'] ?? '';

    // Si no se envía zona, mantener el comportamiento original
    if(empty($zona)) {
        $AlertCo00 = $conn_mysql->query("SELECT * FROM almacenes WHERE cod = '$codigo_ALMACEN' AND status = '1'");
        $AlertCo01 = mysqli_fetch_array($AlertCo00);
        if (empty($AlertCo01['id_alma'])) {
            ?>
            <span class="badge text-bg-success">Libre</span>
            <?php	
        } else {
            ?>
            <span class="badge text-bg-warning">Ocupado</span>
            <?php
        }
    } else {
        // Verificar si existe en la misma zona
        $AlertCo00 = $conn_mysql->query("SELECT * FROM almacenes WHERE cod = '$codigo_ALMACEN' AND zona = '$zona' AND status = '1'");
        $AlertCo01 = mysqli_fetch_array($AlertCo00);
        if (empty($AlertCo01['id_alma'])) {
            ?>
            <span class="badge text-bg-success">Libre</span>
            <?php	
        } else {
            ?>
            <span class="badge text-bg-warning">Ocupado en esta zona</span>
            <?php
        }
    }
}
// Validar código de dirección para edición (excluyendo el actual)
if(isset($_POST['cod_al_edit'])) {
    $cod_al = $_POST['cod_al_edit'];
    $id_actual = $_POST['id_actual'] ?? 0;
    
    $AlertCo00 = $conn_mysql->query("SELECT * FROM direcciones WHERE cod_al = '$cod_al' AND status = '1' AND id_direc != '$id_actual'");
    $AlertCo01 = mysqli_fetch_array($AlertCo00);
    if (empty($AlertCo01['id_direc'])) {
        ?>
        <span class="badge text-bg-success">Libre</span>
        <?php	
    } else {
        ?>
        <span class="badge text-bg-warning">Ocupado</span>
        <?php
    }
}
?>