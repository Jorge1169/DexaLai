<?php
require_once 'config/conexiones.php'; 

header('Content-Type: application/json');

// Obtener parámetros de filtro
$filtroTexto = $_GET['texto'] ?? '';
$filtroLinea = $_GET['linea'] ?? '';
$filtroZona = $_GET['zona'] ?? '';

// Obtener la zona seleccionada del usuario (debes pasar este parámetro desde el frontend)
$zonaUsuario = $_GET['zona_usuario'] ?? '0';

// Construir la consulta base
$query = "SELECT p.id_prod, p.cod, p.nom_pro, p.lin, p.zona, z.nom as nombre_zona 
          FROM productos p 
          LEFT JOIN zonas z ON p.zona = z.id_zone 
          WHERE p.status = '1'";

// Aplicar filtro de zona del usuario
if ($zonaUsuario != '0') {
    $zonaUsuario = $conn_mysql->real_escape_string($zonaUsuario);
    $query .= " AND p.zona = '$zonaUsuario'";
}

// Aplicar filtros adicionales
if (!empty($filtroTexto)) {
    $filtroTexto = $conn_mysql->real_escape_string($filtroTexto);
    $query .= " AND (p.cod LIKE '%$filtroTexto%' OR p.nom_pro LIKE '%$filtroTexto%')";
}

if (!empty($filtroLinea)) {
    $filtroLinea = $conn_mysql->real_escape_string($filtroLinea);
    $query .= " AND p.lin = '$filtroLinea'";
}

if (!empty($filtroZona) && $zonaUsuario == '0') {
    // Solo permitir filtrar por zona si el usuario tiene acceso a todas las zonas
    $filtroZona = $conn_mysql->real_escape_string($filtroZona);
    $query .= " AND p.zona = '$filtroZona'";
}

$query .= " ORDER BY p.cod";

$result = $conn_mysql->query($query);

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode($productos);
?>