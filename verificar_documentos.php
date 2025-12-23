<?php
require_once 'config/conexiones.php';
require_once 'config/conexion_invoice.php';

if ($var_exter == 0) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    logActivity('INV', 'Error de conexión en verificar');
    exit;
}

// Inicializar contadores
$resultados = [
    'facturas_proveedor_eliminadas' => 0,
    'contrarecibos_proveedor_eliminados' => 0,
    'facturas_fletero_eliminadas' => 0,
    'contrarecibos_fletero_eliminados' => 0,
    'facturas_proveedor_rechazadas' => 0,
    'facturas_fletero_rechazadas' => 0,
    'total_actualizaciones' => 0
];

try {
    logActivity('INV', 'Se comprobó si existen las facturas y contra recibos en invoice ');
    
    // Consulta para obtener recolecciones activas con documentos
    $query = "SELECT r.id_recol, r.factura_pro, r.folio_inv_pro, r.factura_fle, r.folio_inv_fle, 
                     r.FacCexis, r.CRcomexis, r.FacFexis, r.CRfleexis,
                     pr.cod AS codigo_proveedor, tr.placas AS codigo_fletero
              FROM recoleccion r
              LEFT JOIN proveedores pr ON r.id_prov = pr.id_prov
              LEFT JOIN transportes tr ON r.id_transp = tr.id_transp
              WHERE r.status = '1'
              AND r.factura_fle != 'N/A'
              AND (r.factura_pro IS NOT NULL OR r.folio_inv_pro IS NOT NULL 
                   OR r.factura_fle IS NOT NULL OR r.folio_inv_fle IS NOT NULL)";

    $result = $conn_mysql->query($query);

    while ($recoleccion = mysqli_fetch_array($result)) {
        $actualizaciones = [];
        
        // VERIFICACIÓN PARA PROVEEDOR
        // Verificar factura de proveedor
        if (!empty($recoleccion['factura_pro']) && !empty($recoleccion['codigo_proveedor'])) {
            $facturaProQuery = $inv_mysql->query("SELECT id, status FROM facturas WHERE folio = '{$recoleccion['factura_pro']}' AND codigoProveedor = '{$recoleccion['codigo_proveedor']}'");
            
            if (mysqli_num_rows($facturaProQuery) == 0) {
                // CASO 1: Factura no existe - Eliminar factura Y contrarecibo
                $nuevoValorFac = $recoleccion['FacCexis'] + 1;
                $nuevoValorCR = $recoleccion['CRcomexis'] + 1;
                $actualizaciones[] = "FacCexis = '$nuevoValorFac', factura_pro = NULL, doc_pro = NULL";
                $actualizaciones[] = "CRcomexis = '$nuevoValorCR', folio_inv_pro = NULL, alias_inv_pro = NULL";
                $resultados['facturas_proveedor_eliminadas']++;
                $resultados['contrarecibos_proveedor_eliminados']++;
            } else {
                // Factura existe, verificar si está rechazada
                $facturaData = mysqli_fetch_assoc($facturaProQuery);
                if (isset($facturaData['status']) && $facturaData['status'] === 'Rechazado') {
                    // CASO 2: Factura rechazada - Eliminar factura Y contrarecibo
                    $nuevoValorFac = $recoleccion['FacCexis'] + 1;
                    $nuevoValorCR = $recoleccion['CRcomexis'] + 1;
                    $actualizaciones[] = "FacCexis = '$nuevoValorFac', factura_pro = NULL, doc_pro = NULL";
                    $actualizaciones[] = "CRcomexis = '$nuevoValorCR', folio_inv_pro = NULL, alias_inv_pro = NULL";
                    $resultados['facturas_proveedor_rechazadas']++;
                    $resultados['contrarecibos_proveedor_eliminados']++;
                } else {
                    // Factura existe y no está rechazada, verificar contrarecibo
                    if (!empty($recoleccion['folio_inv_pro'])) {
                        $contraReciboQuery = $inv_mysql->query("SELECT cr.id 
                            FROM contrarrecibos cr 
                            WHERE cr.folio = '{$recoleccion['folio_inv_pro']}' 
                            AND cr.codigoProveedor = '{$recoleccion['codigo_proveedor']}'");
                        
                        if (mysqli_num_rows($contraReciboQuery) == 0) {
                            // CASO 3: Solo el contrarecibo no existe - Eliminar solo contrarecibo
                            $nuevoValorCR = $recoleccion['CRcomexis'] + 1;
                            $actualizaciones[] = "CRcomexis = '$nuevoValorCR', folio_inv_pro = NULL, alias_inv_pro = NULL";
                            $resultados['contrarecibos_proveedor_eliminados']++;
                        }
                    }
                }
            }
        } else {
            // No hay factura de proveedor, pero verificar si hay contrarecibo para eliminar
            if (!empty($recoleccion['folio_inv_pro']) && !empty($recoleccion['codigo_proveedor'])) {
                $contraReciboQuery = $inv_mysql->query("SELECT cr.id 
                    FROM contrarrecibos cr 
                    WHERE cr.folio = '{$recoleccion['folio_inv_pro']}' 
                    AND cr.codigoProveedor = '{$recoleccion['codigo_proveedor']}'");
                
                if (mysqli_num_rows($contraReciboQuery) == 0) {
                    // Solo el contrarecibo no existe
                    $nuevoValorCR = $recoleccion['CRcomexis'] + 1;
                    $actualizaciones[] = "CRcomexis = '$nuevoValorCR', folio_inv_pro = NULL, alias_inv_pro = NULL";
                    $resultados['contrarecibos_proveedor_eliminados']++;
                }
            }
        }

        // VERIFICACIÓN PARA FLETERO
        // Verificar factura de fletero
        if (!empty($recoleccion['factura_fle']) && !empty($recoleccion['codigo_fletero'])) {
            $facturaFleteroQuery = $inv_mysql->query("SELECT id, status FROM facturas WHERE folio = '{$recoleccion['factura_fle']}' AND codigoProveedor = '{$recoleccion['codigo_fletero']}'");
            
            if (mysqli_num_rows($facturaFleteroQuery) == 0) {
                // CASO 1: Factura no existe - Eliminar factura Y contrarecibo
                $nuevoValorFac = $recoleccion['FacFexis'] + 1;
                $nuevoValorCR = $recoleccion['CRfleexis'] + 1;
                $actualizaciones[] = "FacFexis = '$nuevoValorFac', factura_fle = NULL, doc_fle = NULL, im_tras_inv = NULL, im_rete_inv = NULL, sub_tot_inv = NULL, total_inv = NULL";
                $actualizaciones[] = "CRfleexis = '$nuevoValorCR', folio_inv_fle = NULL, alias_inv_fle = NULL";
                $resultados['facturas_fletero_eliminadas']++;
                $resultados['contrarecibos_fletero_eliminados']++;
            } else {
                // Factura existe, verificar si está rechazada
                $facturaFleteroData = mysqli_fetch_assoc($facturaFleteroQuery);
                if (isset($facturaFleteroData['status']) && $facturaFleteroData['status'] === 'Rechazado') {
                    // CASO 2: Factura rechazada - Eliminar factura Y contrarecibo
                    $nuevoValorFac = $recoleccion['FacFexis'] + 1;
                    $nuevoValorCR = $recoleccion['CRfleexis'] + 1;
                    $actualizaciones[] = "FacFexis = '$nuevoValorFac', factura_fle = NULL, doc_fle = NULL, im_tras_inv = NULL, im_rete_inv = NULL, sub_tot_inv = NULL, total_inv = NULL";
                    $actualizaciones[] = "CRfleexis = '$nuevoValorCR', folio_inv_fle = NULL, alias_inv_fle = NULL";
                    $resultados['facturas_fletero_rechazadas']++;
                    $resultados['contrarecibos_fletero_eliminados']++;
                } else {
                    // Factura existe y no está rechazada, verificar contrarecibo
                    if (!empty($recoleccion['folio_inv_fle'])) {
                        $contraReciboFleteroQuery = $inv_mysql->query("SELECT cr.id 
                            FROM contrarrecibos cr 
                            WHERE cr.folio = '{$recoleccion['folio_inv_fle']}' 
                            AND cr.codigoProveedor = '{$recoleccion['codigo_fletero']}'");
                        
                        if (mysqli_num_rows($contraReciboFleteroQuery) == 0) {
                            // CASO 3: Solo el contrarecibo no existe - Eliminar solo contrarecibo
                            $nuevoValorCR = $recoleccion['CRfleexis'] + 1;
                            $actualizaciones[] = "CRfleexis = '$nuevoValorCR', folio_inv_fle = NULL, alias_inv_fle = NULL";
                            $resultados['contrarecibos_fletero_eliminados']++;
                        }
                    }
                }
            }
        } else {
            // No hay factura de fletero, pero verificar si hay contrarecibo para eliminar
            if (!empty($recoleccion['folio_inv_fle']) && !empty($recoleccion['codigo_fletero'])) {
                $contraReciboFleteroQuery = $inv_mysql->query("SELECT cr.id 
                    FROM contrarrecibos cr 
                    WHERE cr.folio = '{$recoleccion['folio_inv_fle']}' 
                    AND cr.codigoProveedor = '{$recoleccion['codigo_fletero']}'");
                
                if (mysqli_num_rows($contraReciboFleteroQuery) == 0) {
                    // Solo el contrarecibo no existe
                    $nuevoValorCR = $recoleccion['CRfleexis'] + 1;
                    $actualizaciones[] = "CRfleexis = '$nuevoValorCR', folio_inv_fle = NULL, alias_inv_fle = NULL";
                    $resultados['contrarecibos_fletero_eliminados']++;
                }
            }
        }

        // Aplicar actualizaciones si hay alguna
        if (!empty($actualizaciones)) {
            // Eliminar duplicados en caso de que se hayan agregado múltiples actualizaciones para el mismo campo
            $actualizacionesUnicas = array_unique($actualizaciones);
            $updateQuery = "UPDATE recoleccion SET " . implode(', ', $actualizacionesUnicas) . " WHERE id_recol = '{$recoleccion['id_recol']}'";
            $conn_mysql->query($updateQuery);
            $resultados['total_actualizaciones']++;
        }
    }

    echo json_encode(['success' => true, 'data' => $resultados]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>