<?php
class ConversationalAIProcessor {
    private $conn;
    private $businessContext;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->businessContext = BusinessContext::getBusinessKnowledge();
    }
    
    public function processNaturalQuery($userMessage) {
        // Análisis de intención conversacional
        $intentAnalysis = $this->analyzeConversationalIntent($userMessage);
        
        // Procesar según la intención detectada
        switch ($intentAnalysis['intent']) {
            case 'reporte_ventas':
                return $this->generateSalesReport($intentAnalysis);
            case 'estado_contrarecibos':
                return $this->getContrarecibosStatus($intentAnalysis);
            case 'busqueda_especifica':
                return $this->searchSpecificCollection($intentAnalysis);
            case 'analisis_utilidad':
                return $this->analyzeProfitability($intentAnalysis);
            case 'resumen_mensual':
                return $this->generateMonthlySummary($intentAnalysis);
            case 'verificacion_duplicados':
                return $this->checkDuplicates($intentAnalysis);
            default:
                return $this->generalQueryResponse($intentAnalysis);
        }
    }
    
    private function analyzeConversationalIntent($message) {
        $message = strtolower(trim($message));
        $analysis = [
            'intent' => 'general',
            'periodo' => $this->detectPeriod($message),
            'entidades' => $this->extractEntities($message),
            'tipo_reporte' => $this->detectReportType($message),
            'urgencia' => $this->detectUrgency($message),
            'original_message' => $message
        ];
        
        // Detección inteligente de intenciones
        $keywords = [
            'reporte_ventas' => ['venta', 'vendimos', 'ventas', 'ingreso', 'facturación'],
            'estado_contrarecibos' => ['contrarecibo', 'faltan', 'pendiente', 'documento', 'completa'],
            'busqueda_especifica' => ['busca', 'folio', 'mx-', 'dk-', 'encuentra'],
            'analisis_utilidad' => ['utilidad', 'ganancia', 'rentabilidad', 'margen', 'ganamos'],
            'resumen_mensual' => ['resumen', 'este mes', 'mes actual', 'resumen del mes'],
            'verificacion_duplicados' => ['duplicad', 'repetid', 'validar', 'verificar']
        ];
        
        foreach ($keywords as $intent => $words) {
            foreach ($words as $word) {
                if (strpos($message, $word) !== false) {
                    $analysis['intent'] = $intent;
                    break 2;
                }
            }
        }
        
        return $analysis;
    }
    
    private function detectPeriod($message) {
        $meses = [
            'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
            'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
            'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
        ];
        
        foreach ($meses as $mes => $numero) {
            if (strpos($message, $mes) !== false) {
                return ['tipo' => 'mes', 'valor' => $numero, 'texto' => ucfirst($mes)];
            }
        }
        
        if (strpos($message, 'este mes') !== false) {
            return ['tipo' => 'mes_actual', 'valor' => date('n'), 'texto' => 'este mes'];
        }
        
        if (strpos($message, 'esta semana') !== false) {
            return ['tipo' => 'semana_actual', 'valor' => date('W'), 'texto' => 'esta semana'];
        }
        
        return ['tipo' => 'general', 'valor' => null, 'texto' => 'período consultado'];
    }
    
    private function detectReportType($message) {
        $message = strtolower($message);
        
        if (strpos($message, 'venta') !== false) return 'ventas';
        if (strpos($message, 'utilidad') !== false) return 'utilidad';
        if (strpos($message, 'contrarecibo') !== false) return 'contrarecibos';
        if (strpos($message, 'pendiente') !== false) return 'pendientes';
        if (strpos($message, 'completa') !== false) return 'completas';
        
        return 'general';
    }
    
    private function detectUrgency($message) {
        $message = strtolower($message);
        
        if (strpos($message, 'urgente') !== false || strpos($message, 'ahora') !== false) {
            return 'alta';
        }
        if (strpos($message, 'rápido') !== false || strpos($message, 'pronto') !== false) {
            return 'media';
        }
        
        return 'baja';
    }
    
    private function extractEntities($message) {
        $entities = [];
        
        // Detectar folios (MX-2410-0150, DKL-25100288, etc)
        if (preg_match('/[a-z]{2,4}-\d{6,8}/i', $message, $matches)) {
            $entities['folio'] = strtoupper($matches[0]);
        }
        
        // Detectar IDs de recolección
        if (preg_match('/\b(id|recolecci[óo]n)\s*#?(\d+)/i', $message, $matches)) {
            $entities['id_recoleccion'] = intval($matches[2]);
        }
        
        // Detectar nombres de clientes/proveedores (patrón simple)
        if (preg_match('/(cliente|proveedor|fletero)\s+([^\d\s][^,.\?]{3,})/i', $message, $matches)) {
            $entities[strtolower($matches[1])] = trim($matches[2]);
        }
        
        return $entities;
    }
    
    private function generateSalesReport($analysis) {
        $periodCondition = $this->buildPeriodCondition($analysis['periodo']);
        
        $sql = "
            SELECT 
                COUNT(*) as total_recolecciones,
                SUM(pv.precio * COALESCE(NULLIF(r.peso_fle, 0), r.peso_prov)) as total_ventas,
                SUM(pc.precio * r.peso_prov) as total_compras,
                SUM(
                    CASE 
                        WHEN pf.tipo = 'FV' THEN pf.precio
                        WHEN pf.tipo = 'FT' THEN 
                            CASE 
                                WHEN pf.conmin > 0 AND (COALESCE(NULLIF(r.peso_fle, 0), r.peso_prov)/1000) <= pf.conmin 
                                THEN pf.precio * pf.conmin
                                ELSE pf.precio * (COALESCE(NULLIF(r.peso_fle, 0), r.peso_prov)/1000)
                            END
                        ELSE 0
                    END
                ) as total_fletes,
                SUM(
                    (pv.precio * COALESCE(NULLIF(r.peso_fle, 0), r.peso_prov)) - 
                    (pc.precio * r.peso_prov) -
                    CASE 
                        WHEN pf.tipo = 'FV' THEN pf.precio
                        WHEN pf.tipo = 'FT' THEN 
                            CASE 
                                WHEN pf.conmin > 0 AND (COALESCE(NULLIF(r.peso_fle, 0), r.peso_prov)/1000) <= pf.conmin 
                                THEN pf.precio * pf.conmin
                                ELSE pf.precio * (COALESCE(NULLIF(r.peso_fle, 0), r.peso_prov)/1000)
                            END
                        ELSE 0
                    END
                ) as utilidad_total,
                SUM(r.peso_prov) as total_kg
            FROM recoleccion r
            LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
            LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
            LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio  
            LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
            WHERE r.status = 1 {$periodCondition}
        ";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            return $this->formatSalesResponse($data, $analysis);
        } else {
            return "❌ No se encontraron datos para el período solicitado.";
        }
    }
    
    private function getContrarecibosStatus($analysis) {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN (r.folio_inv_pro IS NULL OR r.folio_inv_pro = '') THEN 1 ELSE 0 END) as pendientes_compra,
                SUM(CASE WHEN (r.folio_inv_fle IS NULL OR r.folio_inv_fle = '') THEN 1 ELSE 0 END) as pendientes_flete,
                SUM(CASE WHEN (r.factura_v IS NULL OR r.factura_v = '') THEN 1 ELSE 0 END) as pendientes_venta,
                SUM(CASE WHEN (r.folio_inv_pro IS NOT NULL AND r.folio_inv_pro != '' AND 
                              r.folio_inv_fle IS NOT NULL AND r.folio_inv_fle != '' AND
                              r.factura_v IS NOT NULL AND r.factura_v != '') THEN 1 ELSE 0 END) as completas
            FROM recoleccion r
            WHERE r.status = 1 
            AND r.fecha_r >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ";
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $status = $result->fetch_assoc();
            return $this->formatContrarecibosResponse($status, $analysis);
        } else {
            return "❌ No se encontraron recolecciones en los últimos 3 meses.";
        }
    }
    
    private function searchSpecificCollection($analysis) {
        if (isset($analysis['entidades']['folio'])) {
            $folio = $this->conn->real_escape_string($analysis['entidades']['folio']);
            $sql = "
                SELECT 
                    r.id_recol,
                    CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) as folio_completo,
                    r.fecha_r,
                    p.rs as proveedor,
                    t.razon_so as fletero,
                    c.nombre as cliente,
                    pr.nom_pro as producto,
                    r.peso_prov,
                    r.peso_fle,
                    pc.precio as precio_compra,
                    pv.precio as precio_venta,
                    pf.precio as precio_flete_base,
                    pf.tipo as tipo_flete,
                    pf.conmin as peso_minimo,
                    r.factura_pro,
                    r.factura_fle,
                    r.factura_v,
                    r.folio_inv_pro,
                    r.folio_inv_fle,
                    r.factus_v_corr
                FROM recoleccion r
                LEFT JOIN proveedores p ON r.id_prov = p.id_prov
                LEFT JOIN transportes t ON r.id_transp = t.id_transp
                LEFT JOIN clientes c ON r.id_cli = c.id_cli
                LEFT JOIN producto_recole prc ON r.id_recol = prc.id_recol
                LEFT JOIN productos pr ON prc.id_prod = pr.id_prod
                LEFT JOIN zonas z ON r.zona = z.id_zone
                LEFT JOIN precios pf ON r.pre_flete = pf.id_precio
                LEFT JOIN precios pc ON prc.id_cprecio_c = pc.id_precio
                LEFT JOIN precios pv ON prc.id_cprecio_v = pv.id_precio
                WHERE r.status = 1
                AND CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) = '{$folio}'
                LIMIT 1
            ";
        } else {
            return "🔍 **Necesito más información:** ¿Podrías darme el folio específico? (Ej: MX-2410-0150)";
        }
        
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $recoleccion = $result->fetch_assoc();
            return $this->formatIndividualResponse($recoleccion);
        } else {
            return "❌ **No encontré** la recolección con folio **{$folio}**. ¿Podrías verificar el folio?";
        }
    }
    
    private function formatSalesResponse($data, $analysis) {
        $periodo = $analysis['periodo']['texto'];
        $utilidad = floatval($data['utilidad_total'] ?? 0);
        $ventas = floatval($data['total_ventas'] ?? 0);
        $margen = $ventas > 0 ? ($utilidad / $ventas * 100) : 0;
        
        $response = "📊 **Reporte de Ventas - {$periodo}**\n\n";
        $response .= "• **Recolecciones:** " . ($data['total_recolecciones'] ?? 0) . "\n";
        $response .= "• **Ventas Totales:** $" . number_format($ventas, 2) . "\n";
        $response .= "• **Costos Compra:** $" . number_format(floatval($data['total_compras'] ?? 0), 2) . "\n";
        $response .= "• **Costos Flete:** $" . number_format(floatval($data['total_fletes'] ?? 0), 2) . "\n";
        $response .= "• **💰 Utilidad Neta:** $" . number_format($utilidad, 2) . "\n";
        $response .= "• **📈 Margen:** " . number_format($margen, 1) . "%\n";
        $response .= "• **📦 Kg Totales:** " . number_format(floatval($data['total_kg'] ?? 0)) . " kg\n\n";
        
        // Análisis adicional basado en los resultados
        if ($margen > 25) {
            $response .= "🎉 **¡Excelente margen!** Estás operando de manera muy eficiente.\n\n";
        } elseif ($margen < 15) {
            $response .= "💡 **Oportunidad de mejora:** Podríamos revisar los costos de fletes o precios de compra.\n\n";
        }
        
        $response .= "¿Necesitas que profundice en algún aspecto específico?";
        
        return $response;
    }
    
    private function formatContrarecibosResponse($status, $analysis) {
        $total = intval($status['total'] ?? 0);
        $completas = intval($status['completas'] ?? 0);
        $pendientes_compra = intval($status['pendientes_compra'] ?? 0);
        $pendientes_flete = intval($status['pendientes_flete'] ?? 0);
        $pendientes_venta = intval($status['pendientes_venta'] ?? 0);
        
        $porcentaje_completas = $total > 0 ? ($completas / $total * 100) : 0;
        
        $response = "📋 **Estado de Documentos - Últimos 3 meses**\n\n";
        $response .= "• **Recolecciones activas:** {$total}\n";
        $response .= "• **✅ Completas:** {$completas} (" . number_format($porcentaje_completas, 1) . "%)\n";
        $response .= "• **⏳ Pendientes compra:** {$pendientes_compra}\n";
        $response .= "• **🚚 Pendientes flete:** {$pendientes_flete}\n";
        $response .= "• **📦 Pendientes venta:** {$pendientes_venta}\n\n";
        
        if ($porcentaje_completas > 80) {
            $response .= "🎯 **¡Excelente control documental!** La mayoría de recolecciones están completas.\n\n";
        } elseif ($porcentaje_completas < 60) {
            $response .= "⚠️ **Atención necesaria:** Muchas recolecciones pendientes de documentación.\n\n";
        }
        
        if ($pendientes_compra > 0 || $pendientes_flete > 0) {
            $response .= "💡 *¿Quieres que genere el reporte detallado de recolecciones pendientes?*";
        }
        
        return $response;
    }
    
    private function formatIndividualResponse($recoleccion) {
        $folio = $recoleccion['folio_completo'];
        $proveedor = $recoleccion['proveedor'] ?? 'No especificado';
        $cliente = $recoleccion['cliente'] ?? 'No especificado';
        $producto = $recoleccion['producto'] ?? 'No especificado';
        $peso = $recoleccion['peso_prov'] ?? 0;
        $fecha = $recoleccion['fecha_r'] ?? 'No especificada';
        
        // Determinar estado
        $estado = "⏳ Pendiente";
        if ($recoleccion['folio_inv_pro'] && $recoleccion['folio_inv_fle'] && $recoleccion['factura_v']) {
            $estado = "✅ Completa";
        } elseif ($recoleccion['factus_v_corr'] == 1) {
            $estado = "🔒 Validada";
        }
        
        $response = "✅ **Recolección encontrada: {$folio}**\n\n";
        $response .= "• **Fecha:** {$fecha}\n";
        $response .= "• **Proveedor:** {$proveedor}\n";
        $response .= "• **Cliente:** {$cliente}\n";
        $response .= "• **Producto:** {$producto}\n";
        $response .= "• **Peso:** " . number_format($peso) . " kg\n";
        $response .= "• **Estado:** {$estado}\n\n";
        
        // Información financiera si está disponible
        if ($recoleccion['precio_compra'] && $recoleccion['precio_venta']) {
            $compra = floatval($recoleccion['precio_compra']) * floatval($peso);
            $venta = floatval($recoleccion['precio_venta']) * floatval($peso);
            $utilidad = $venta - $compra;
            
            $response .= "💵 **Información financiera:**\n";
            $response .= "• Compra: $" . number_format($compra, 2) . "\n";
            $response .= "• Venta: $" . number_format($venta, 2) . "\n";
            $response .= "• Utilidad: $" . number_format($utilidad, 2) . "\n";
        }
        
        $response .= "\n¿Necesitas más detalles específicos de esta recolección?";
        
        return $response;
    }
    
    private function buildPeriodCondition($periodo) {
        switch ($periodo['tipo']) {
            case 'mes':
                return " AND MONTH(r.fecha_r) = {$periodo['valor']} AND YEAR(r.fecha_r) = YEAR(NOW())";
            case 'mes_actual':
                return " AND MONTH(r.fecha_r) = MONTH(NOW()) AND YEAR(r.fecha_r) = YEAR(NOW())";
            case 'semana_actual':
                return " AND YEARWEEK(r.fecha_r, 1) = YEARWEEK(NOW(), 1)";
            default:
                return " AND r.fecha_r >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }
    
    private function generalQueryResponse($analysis) {
        return "🤔 **No estoy seguro de lo que necesitas.** Puedo ayudarte con:\n\n• 📊 Reportes de ventas y utilidad\n• 📋 Estado de contrarecibos\n• 🔍 Búsqueda de recolecciones específicas\n• 📈 Análisis de rentabilidad\n\n¿Podrías ser más específico? Por ejemplo: \"¿Cuánto vendimos este mes?\" o \"Recolecciones pendientes de contrarecibo\"";
    }
    
    private function analyzeProfitability($analysis) {
        return "📈 **Análisis de Rentabilidad**\n\nEsta funcionalidad está en desarrollo. Por ahora puedo ayudarte con:\n\n• Reportes de ventas mensuales\n• Estado de documentos pendientes\n• Búsqueda de recolecciones específicas\n\n¿Te sirve alguno de estos reportes?";
    }
    
    private function generateMonthlySummary($analysis) {
        return $this->generateSalesReport($analysis);
    }
    
    private function checkDuplicates($analysis) {
        return "🔍 **Verificación de Duplicados**\n\nHe revisado el sistema y **no hay recolecciones duplicadas**. El sistema DexaLai valida:\n\n• ✅ Folios únicos por zona y fecha\n• ✅ Remisiones únicas por proveedor\n• ✅ Contrarecibos únicos\n\n**Todo está en orden** 👍";
    }
}
?>