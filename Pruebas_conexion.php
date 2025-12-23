<?php

$var_exter = '0'; // Resultados de conexión: 1=exitosa, 0=fallida
$er_mess = ''; // Variable para almacenar el mensaje de error

$mysql_host_inv = "186.96.54.230:3306";
$mysql_user_inv = "lai_dkl";
$mysql_pass_inv = "ld0U3fsoKfdex";
$mysql_dbname_inv = "invoice";
try {
// Conexión MySQL con mysqli
$inv_mysql = mysqli_connect($mysql_host_inv, $mysql_user_inv, $mysql_pass_inv, $mysql_dbname_inv);
echo "Conexión Exitosa";
} catch (Exception $ex) {
    
    echo "fallo mysql<br>";
}
if (!$inv_mysql) {
    $var_exter = '0'; // conexión con error
    $error_code = mysqli_connect_errno(); // Código de error
    $er_mess = mysqli_connect_error(); // Mensaje de error
    
    // Detalles completos del error
    echo "Error en conexión a MySQL:<br>";
    echo "Código de error: " . $error_code . "<br>";
    echo "Mensaje: " . $er_mess . "<br>";
    
} else {
    $var_exter = '1'; // conexión exitosa
    echo "Conexión exitosa a MySQL.<br>";
    echo "Información del servidor: " . mysqli_get_host_info($inv_mysql) . "<br>";
    echo "Versión del servidor MySQL: " . mysqli_get_server_info($inv_mysql) . "<br>";
}
?>