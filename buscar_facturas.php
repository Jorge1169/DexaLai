<?php
require_once 'config/conexiones.php';
require_once 'config/conexion_invoice.php';

if ($var_exter == 0) {
    echo '<div class="alert alert-danger">Error de conexión. Contactar a SISTEMAS</div>';
} else {
    if(isset($_POST['zon'])) { 
        $zon = $_POST['zon'];
        
        echo '<div class="list-group">';
        
        if ($zon == 0) {
            $SelcVen0 = "SELECT v.*, z.nom AS nombre_zon, p.cod AS codigo_prov, tr.placas AS id_transportista, v.costo_flete AS costo_fl , c.id_compra AS id_compra, c.neto AS neto_com, c.pres AS precio_com  FROM ventas v 
            LEFT JOIN zonas z on v.zona = z.id_zone
            LEFT JOIN compras c on v.id_compra = c.id_compra
            LEFT JOIN transportes tr on c.id_transp = tr.id_transp  
            LEFT JOIN proveedores p on c.id_prov = p.id_prov  
            WHERE v.status = '1' AND (c.factura is null OR v.fact_fle is null)";
        } else {
            $SelcVen0 = "SELECT v.*, z.nom AS nombre_zon, p.cod AS codigo_prov, tr.placas AS id_transportista, v.costo_flete AS costo_fl , c.id_compra AS id_compra, c.neto AS neto_com, c.pres AS precio_com  FROM ventas v 
            LEFT JOIN zonas z on v.zona = z.id_zone
            LEFT JOIN compras c on v.id_compra = c.id_compra
            LEFT JOIN transportes tr on c.id_transp = tr.id_transp  
            LEFT JOIN proveedores p on c.id_prov = p.id_prov  
            WHERE v.zona = '$zon' AND v.status = '1' AND (c.factura is null OR v.fact_fle is null)";
        }
        
        $slv = $conn_mysql->query($SelcVen0);
        $totalRegistros = mysqli_num_rows($slv);
        $procesados = 0;
        
        while ($slVenta = mysqli_fetch_array($slv)) {
            $procesados++;
            $REM = $slVenta['fact'];
            $PRV = $slVenta['codigo_prov'];
            $IDT = $slVenta['id_transportista'];
            $IDV = $slVenta['id_venta'];
            $CSF = $slVenta['costo_fl'];
            $IDC = $slVenta['id_compra'];
            $ZNV = $slVenta['nombre_zon'];
            $TOT = $slVenta['neto_com'] * $slVenta['precio_com'];
            
            echo '<div class="list-group-item list-group-item-action">';
            echo '<div class="d-flex w-100 justify-content-between">';
            echo '<h6 class="mb-1">Remisión: <span class="badge bg-primary">'.$REM.'</span></h6>';
            echo '<small class="text-muted">Zona: '.$ZNV.'</small>';
            echo '</div>';
            
            $BusFac0 = $inv_mysql->query("SELECT * FROM facturas WHERE codigoProveedor = '$PRV' AND remision = '$REM'");
            //$BusFac0 = $inv_mysql->query("SELECT * FROM facturas WHERE codigoProveedor = '$PRV' AND remision like '%$REM%' AND total = '$TOT'");
            $BusFac1 = mysqli_fetch_array($BusFac0);
            
            if (!empty($BusFac1['id']) AND $BusFac1['ea'] == '1') {
                $fecha_timestamp = strtotime($BusFac1['fechaFactura']);
                $fecha_form = date("ymd", $fecha_timestamp);
                $ubicacion = $BusFac1['ubicacion'].'EA_'.str_replace("-", "", $BusFac1['codigoProveedor'].'_'.$BusFac1['folio'].'_'.$fecha_form);
                $FactInv = $BusFac1['folio'];
                $id_fact = $BusFac1['id'];

                $conn_mysql->query("UPDATE compras SET factura = '$FactInv', d_prov = '$ubicacion' WHERE id_compra = '$IDC'");
                
                echo '<p class="mb-1 text-success"><i class="bi bi-check-circle-fill me-1"></i> Factura del proveedor encontrada: '.$FactInv.' Se incerto en la compra</p>';
                
            } elseif (!empty($BusFac1['id']) AND $BusFac1['ea'] != '1') {

                echo '<p class="mb-1 text-orange"><i class="bi bi-check-circle-fill me-1"></i> Factura sin evidencia</p>';
                
            }
            else {
                echo '<p class="mb-1 text-danger"><i class="bi bi-x-circle-fill me-1"></i> factura del proveeedor no encontrada</p>';
            }

            $BusFF0 = $inv_mysql->query("SELECT * FROM facturas WHERE codigoProveedor = '$IDT' AND subtotal = '$CSF' AND remision = '$REM'");
            $BusFF1 = mysqli_fetch_array($BusFF0);

            if (!empty($BusFF1['id']) AND empty($slVenta['fact_fle']) AND $BusFF1['ea'] == '1') {

                $fecha_timestampFF1 = strtotime($BusFF1['fechaFactura']);
                $fecha_formFF = date("ymd", $fecha_timestampFF1);
                $ubicacionFF = $BusFF1['ubicacion'].'EA_'.str_replace("-", "", $BusFF1['codigoProveedor'].'_'.$BusFF1['folio'].'_'.$fecha_formFF);
                $FactInvFF = $BusFF1['folio'];
                $im_tras_inv = $BusFF1['impuestoTraslado'];
                $im_rete_inv = $BusFF1['impuestoRetenido'];
                $rfc_inv = $BusFF1['rfcGrupo'];
                $total_inv = $BusFF1['total'];

                $conn_mysql->query("UPDATE ventas SET fact_fle = '$FactInvFF', d_fletero = '$ubicacionFF', im_tras_inv = '$im_tras_inv', im_rete_inv = '$im_rete_inv', total_inv = '$total_inv', rfc_inv = '$rfc_inv'  WHERE id_venta = '$IDV'");

                echo '<p class="mb-1 text-success"><i class="bi bi-check-circle-fill me-1"></i> Factura de flete encontrada: '.$FactInvFF.' Se incerto en la venta</p>';

            } elseif (!empty($BusFF1['id']) AND empty($slVenta['fact_fle']) AND $BusFac1['ea'] != '1') {
             echo '<p class="mb-1 text-orange"><i class="bi bi-exclamation-triangle-fill me-1"></i> Factura encontrada falta evidencia</p>';
         } elseif (!empty($BusFF1['id']) AND !empty($slVenta['fact_fle'])) {
            echo '<p class="mb-1 text-teal"><i class="bi bi-exclamation-triangle-fill me-1"></i> Ya cuenta con factura de flete</p>';
        } else {

            echo '<p class="mb-1 text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> Factura de flete no encontrada</p>';
        }

        echo '<small class="text-muted">Progreso: '.$procesados.' de '.$totalRegistros.'</small>';
        echo '</div>';
    }

    echo '</div>';

    if ($totalRegistros == 0) {
        echo '<div class="alert alert-info">No hay registros para procesar</div>';
    } else {
            //echo '<div class="alert alert-success mt-3">Proceso completado. Se procesaron '.$procesados.' registros.</div>';
    }
}
}
?>