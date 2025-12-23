<?php
require_once 'config/conexiones.php';
require_once 'config/conexion_invoice.php';

if ($var_exter == 0) {
    echo '<div class="alert alert-danger">Error de conexión. Contactar a SISTEMAS</div>';
} else {
    if(isset($_POST['zonCR'])) {
        $zon = $_POST['zonCR'];
        
        echo '<div class="list-group">';
        
        if ($zon == 0) {
            $SelcVen0 = "SELECT v.*, z.nom AS nombre_zon, v.aliasInv AS Alias_Contra, v.folio_contra AS Folio_Contra, v.fact_fle AS factura_flete , v.rfc_inv AS RFC_Empres , tr.placas AS id_transportista, v.costo_flete AS costo_fl, v.id_venta AS id_deVenta, v.fact AS Remision, c.factura AS Factura_compra, p.cod AS Codigo_proveedor, c.neto AS neto_compra, c.pres AS precio_compra, c.fact AS Remision_compra, c.id_compra AS ID_COMPRA, c.folio_contra AS Folio_Contra_comp FROM ventas v 
            LEFT JOIN zonas z on v.zona = z.id_zone
            LEFT JOIN compras c on v.id_compra = c.id_compra
            LEFT JOIN transportes tr on c.id_transp = tr.id_transp  
            LEFT JOIN proveedores p on c.id_prov = p.id_prov  
            WHERE v.status = '1' AND (v.fact_fle IS NOT NULL OR c.factura IS NOT NULL) AND (v.folio_contra IS NULL OR c.folio_contra IS NULL)";
        } else {
            $SelcVen0 = "SELECT v.*, z.nom AS nombre_zon, v.aliasInv AS Alias_Contra, v.folio_contra AS Folio_Contra, v.fact_fle AS factura_flete , v.rfc_inv AS RFC_Empres , tr.placas AS id_transportista, v.costo_flete AS costo_fl, v.id_venta AS id_deVenta, v.fact AS Remision, c.factura AS Factura_compra, p.cod AS Codigo_proveedor, c.neto AS neto_compra, c.pres AS precio_compra, c.fact AS Remision_compra, c.id_compra AS ID_COMPRA, c.folio_contra AS Folio_Contra_comp FROM ventas v 
            LEFT JOIN zonas z on v.zona = z.id_zone
            LEFT JOIN compras c on v.id_compra = c.id_compra
            LEFT JOIN transportes tr on c.id_transp = tr.id_transp  
            LEFT JOIN proveedores p on c.id_prov = p.id_prov  
            WHERE v.zona = '$zon' AND v.status = '1' AND (v.fact_fle IS NOT NULL OR c.factura IS NOT NULL) AND (v.folio_contra IS NULL OR c.folio_contra IS NULL)";
        }
        
        $slv = $conn_mysql->query($SelcVen0);
        $totalRegistros = mysqli_num_rows($slv);
        $procesados = 0;
        
        while ($slVenta = mysqli_fetch_array($slv)) {// si se cumplen las condiciones entra
            $procesados++;
            $IDV = $slVenta['id_deVenta'];// id de la venta para ingresarla
            $FAC = $slVenta['factura_flete'];// factura del flete
            $PRT = $slVenta['id_transportista'];// id del  AND transportista
            $SUB = $slVenta['costo_fl'];// costo del flete
            $ZNV = $slVenta['nombre_zon'];// ZONA DE LA VENTA
            $REM = $slVenta['Remision'];//Remision de venta
            // Datos de la compra
            $IDC = $slVenta['ID_COMPRA'];// Id de la compra
            $FCOM = $slVenta['Factura_compra'];// factura de la compra
            $PRO = $slVenta['Codigo_proveedor'];// id del proveedor
            $SCOM = $slVenta['neto_compra'] * $slVenta['precio_compra'];// sub total
            $REMC = $slVenta['ID_COMPRA']; // Id de la compra 
            
            echo '<div class="list-group-item list-group-item-action">';
            echo '<div class="d-flex w-100 justify-content-between">';
            echo '<h6 class="mb-1">Remisión: <span class="badge bg-primary">'.$REM.'</span></h6>';
            echo '<small class="text-muted">Zona: '.$ZNV.'</small>';
            echo '</div>';
            
            $BusFac0 = $inv_mysql->query("SELECT cr.aliasGrupo AS alias, cr.folio AS FolioContra ,cr.* FROM facturas f inner join contrafacturas cf on f.id=cf.idFactura inner join contrarrecibos cr on cf.idContrarrecibo=cr.id and f.codigoProveedor=cr.codigoProveedor and f.rfcGrupo=cr.rfcGrupo inner join grupo g on f.rfcGrupo=g.rfc where f.folio='$FAC' and f.codigoProveedor = '$PRT' and f.subtotal = '$SUB' and f.remision = '$REM'"); // buscamos el contra recibo de la factura del fletero
            
            $BusFac1 = mysqli_fetch_array($BusFac0);
            
            if (!empty($BusFac1['id']) AND empty($slVenta['Folio_Contra'])) {

                $Alias = $BusFac1['alias'];// Alias de la empresa
                $folio = $BusFac1['FolioContra'];//FolioContra

                $conn_mysql->query("UPDATE ventas SET aliasInv = '$Alias', folio_contra = '$folio' WHERE id_venta = '$IDV'");
                
                echo '<p class="mb-1 text-success"><i class="bi bi-check-circle-fill me-1"></i> Contra Recibo de flete encontrado: '.$Alias.'-'.$folio.' Se incerto en la venta</p>';
                
            } elseif (!empty($BusFac1['id']) AND !empty($slVenta['Folio_Contra'])) {
                echo '<p class="mb-1 text-teal"><i class="bi bi-check-circle-fill me-1"></i> Contra Recibo de flete existente</p>';
            }
            else {
                echo '<p class="mb-1 text-danger"><i class="bi bi-x-circle-fill me-1"></i> Contra Recibo de flete no encontrado</p>';
            }
            // ahora vamos a buscar el contra de la compra de producto

            $BusFacC0 = $inv_mysql->query("SELECT cr.aliasGrupo AS alias, cr.folio AS FolioContra ,cr.* FROM facturas f inner join contrafacturas cf on f.id=cf.idFactura inner join contrarrecibos cr on cf.idContrarrecibo=cr.id and f.codigoProveedor=cr.codigoProveedor and f.rfcGrupo=cr.rfcGrupo inner join grupo g on f.rfcGrupo=g.rfc where f.folio='$FCOM' and f.codigoProveedor = '$PRO' and f.subtotal = '$SCOM' and f.remision = '$REMC'"); // buscamos el contra recibo de la factura de compra
            
            $BusFacC1 = mysqli_fetch_array($BusFacC0);
            if (!empty($BusFacC1['id']) AND empty($slVenta['Folio_Contra_comp'])) {

                $AliasCOM = $BusFacC1['alias'];// Alias de la empresa
                $folioCOM = $BusFacC1['FolioContra'];//FolioContra

                $conn_mysql->query("UPDATE compras SET aliasInv = '$AliasCOM', folio_contra = '$folioCOM' WHERE id_compra = '$IDC'");
                
                echo '<p class="mb-1 text-success"><i class="bi bi-check-circle-fill me-1"></i> Contra Recibo de compra encontrado: '.$AliasCOM.'-'.$folioCOM.' Se incerto en la compra</p>';
                
            }
            elseif (!empty($BusFacC1['id']) AND !empty($slVenta['Folio_Contra_comp'])) {
                echo '<p class="mb-1 text-teal"><i class="bi bi-check-circle-fill me-1"></i> Contra Recibo de compra existente</p>';
            }
            else {
                echo '<p class="mb-1 text-danger"><i class="bi bi-x-circle-fill me-1"></i> Contra Recibo de compra no encontrado</p>';
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