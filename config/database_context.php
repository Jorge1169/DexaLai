<?php
/**
 * CONTEXTO COMPLETO DE BASE DE DATOS PARA IA
 * Estructura detallada del sistema de recolecciones
 */

class DatabaseContext {
    
    public static function getSchemaDescription() {
        return [
            'sistema' => 'Sistema de Gestión de Recolecciones Logísticas',
            'estructura_completa' => [
                'tablas_principales' => [
                    'recoleccion' => [
                        'descripcion' => 'Tabla principal de recolecciones - información completa de cada recolección',
                        'campos_importantes' => [
                            'id_recol' => 'ID único de la recolección',
                            'folio' => 'Número de folio interno',
                            'fecha_r' => 'Fecha de recolección (formato YYYY-MM-DD)',
                            'id_prov' => 'ID del proveedor',
                            'id_transp' => 'ID del transportista/fletero',
                            'id_cli' => 'ID del cliente',
                            'peso_prov' => 'Peso en kg del proveedor',
                            'peso_fle' => 'Peso en kg del fletero',
                            'remision' => 'Número de remisión normal',
                            'remixtac' => 'Número de remisión especial Ixtac',
                            'factura_pro' => 'Factura del proveedor',
                            'factura_fle' => 'Factura del fletero',
                            'factura_v' => 'Factura de venta al cliente',
                            'status' => 'Estado (1=activo, 0=inactivo)',
                            'zona' => 'Zona de la recolección',
                            'pre_flete' => 'ID del precio de flete',
                            'tipo_fle' => 'Tipo de camión',
                            'nom_fle' => 'Nombre del chofer',
                            'placas_fle' => 'Placas de la unidad',
                            'folio_inv_pro' => 'Folio de contrarecibo proveedor',
                            'folio_inv_fle' => 'Folio de contrarecibo fletero',
                            'alias_inv_pro' => 'Alias del proveedor para contrarecibo',
                            'alias_inv_fle' => 'Alias del fletero para contrarecibo'
                        ]
                    ],
                    'proveedores' => [
                        'descripcion' => 'Catálogo de proveedores',
                        'campos' => [
                            'id_prov', 
                            'cod' => 'código del proveedor', 
                            'rs' => 'razón social'
                        ]
                    ],
                    'transportes' => [
                        'descripcion' => 'Catálogo de transportistas/fleteros',
                        'campos' => [
                            'id_transp', 
                            'placas' => 'placas del vehículo', 
                            'razon_so' => 'razón social'
                        ]
                    ],
                    'clientes' => [
                        'descripcion' => 'Catálogo de clientes',
                        'campos' => [
                            'id_cli', 
                            'cod' => 'código cliente', 
                            'nombre' => 'nombre del cliente'
                        ]
                    ],
                    'productos' => [
                        'descripcion' => 'Catálogo de productos',
                        'campos' => [
                            'id_prod', 
                            'nom_pro' => 'nombre producto',
                            'cod' => 'código producto'
                        ]
                    ],
                    'precios' => [
                        'descripcion' => 'Tabla de precios para compra, venta y fletes',
                        'campos' => [
                            'id_precio', 
                            'precio', 
                            'tipo' => 'FV=por viaje, FT=por tonelada',
                            'conmin' => 'peso mínimo para fletes por tonelada'
                        ]
                    ],
                    'zonas' => [
                        'descripcion' => 'Catálogo de zonas',
                        'campos' => [
                            'id_zone', 
                            'cod' => 'código zona', 
                            'PLANTA' => 'planta asociada'
                        ]
                    ],
                    'direcciones' => [
                        'descripcion' => 'Direcciones de proveedores y clientes',
                        'campos' => [
                            'id_direc',
                            'cod_al' => 'código bodega',
                            'noma' => 'nombre bodega'
                        ]
                    ]
                ],
                'formato_folio' => [
                    'descripcion' => 'El folio completo se genera concatenando: código_zona + fecha(aamm) + folio(4 dígitos)',
                    'ejemplo' => 'DKL-25100288 = DKL(zona) + 2510(octubre 2025) + 0288(folio)',
                    'generacion' => "CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0'))"
                ],
                'relaciones_completas' => [
                    'recoleccion -> proveedores' => 'r.id_prov = p.id_prov',
                    'recoleccion -> transportes' => 'r.id_transp = t.id_transp', 
                    'recoleccion -> clientes' => 'r.id_cli = c.id_cli',
                    'recoleccion -> zonas' => 'r.zona = z.id_zone',
                    'recoleccion -> precios (flete)' => 'r.pre_flete = pf.id_precio',
                    'recoleccion -> direcciones (proveedor)' => 'r.id_direc_prov = dp.id_direc',
                    'recoleccion -> direcciones (cliente)' => 'r.id_direc_cli = dc.id_direc',
                    'producto_recole -> recoleccion' => 'prc.id_recol = r.id_recol',
                    'producto_recole -> productos' => 'prc.id_prod = pr.id_prod',
                    'producto_recole -> precios (compra)' => 'prc.id_cprecio_c = pc.id_precio',
                    'producto_recole -> precios (venta)' => 'prc.id_cprecio_v = pv.id_precio'
                ]
            ],
            'calculos_financieros' => [
                'precio_flete_real' => [
                    'logica' => 'Depende del tipo de flete y condiciones especiales',
                    'tipo_FV' => 'precio base fijo (por viaje)',
                    'tipo_FT' => 'precio_base * (peso_fle/1000) - considera peso mínimo',
                    'condicion_especial' => 'Si precio_flete_base = 0, usar peso_prov para cálculos',
                    'funcion' => 'calcularPrecioFleteReal() con reglas específicas'
                ],
                'total_compra' => 'peso_prov * precio_compra',
                'total_venta' => [
                    'normal' => 'peso_fle * precio_venta',
                    'flete_gratis' => 'peso_prov * precio_venta (cuando precio_flete = 0)'
                ],
                'utilidad_estimada' => 'total_venta - total_compra - precio_flete_real',
                'contrarecibos' => [
                    'compra' => 'alias_inv_pro + folio_inv_pro',
                    'flete' => 'alias_inv_fle + folio_inv_fle',
                    'estados' => ['completas', 'solo-flete', 'solo-compra', 'pendientes']
                ]
            ],
            'consultas_comunes' => [
                'por_folio' => "Buscar por folio completo ej: 'DKL-25100288'",
                'por_mes' => "FILTRAR POR: MONTH(fecha_r) = mes AND YEAR(fecha_r) = año",
                'por_cliente' => "FILTRAR POR: c.nombre LIKE '%nombre_cliente%'",
                'por_proveedor' => "FILTRAR POR: p.rs LIKE '%nombre_proveedor%'", 
                'por_fletero' => "FILTRAR POR: t.razon_so LIKE '%nombre_fletero%'",
                'sin_factura_fletero' => "FILTRAR POR: (factura_fle IS NULL OR factura_fle = '')",
                'contrarecibos_pendientes' => "FILTRAR POR estado 'pendientes'",
                'por_producto' => "FILTRAR POR: pr.nom_pro LIKE '%nombre_producto%'"
            ],
            'ejemplos_consultas' => [
                'individual' => "SELECT completa FROM recoleccion WHERE folio_completo = 'DKL-25100288'",
                'mensual' => "SELECT resumen FROM recoleccion WHERE MONTH(fecha_r) = 11 AND YEAR(fecha_r) = 2024",
                'cliente_especifico' => "SELECT por_cliente WHERE cliente LIKE '%nombre%'",
                'utilidad_mensual' => "SELECT con_calculos WHERE mes = X"
            ]
        ];
    }
    
    public static function getQueryTemplates() {
        return [
            'consulta_individual' => "
                SELECT 
                    r.id_recol,
                    CONCAT(z.cod, '-', DATE_FORMAT(r.fecha_r, '%y%m'), LPAD(r.folio, 4, '0')) as folio_completo,
                    r.fecha_r,
                    p.rs as proveedor,
                    t.razon_so as fletero, 
                    c.nombre as cliente,
                    pr.nom_pro as producto,
                    r.remision,
                    r.remixtac,
                    r.factura_pro,
                    r.factura_fle, 
                    r.factura_v,
                    r.peso_prov,
                    r.peso_fle,
                    pc.precio as precio_compra,
                    pv.precio as precio_venta,
                    pf.precio as precio_flete_base,
                    pf.tipo as tipo_flete,
                    pf.conmin as peso_minimo,
                    r.tipo_fle as tipo_camion,
                    r.nom_fle as nombre_chofer,
                    r.placas_fle as placas_unidad,
                    r.alias_inv_pro,
                    r.folio_inv_pro,
                    r.alias_inv_fle, 
                    r.folio_inv_fle,
                    z.PLANTA as planta_zona
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
            ",
            'consulta_general' => "
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
                    pf.tipo as tipo_flete
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
            "
        ];
    }
}
?>