<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <title>Notificación a Transportista</title>
</head>

<body>

<?php
header('Content-Type: text/html; charset=UTF-8');

// Librerías para correo en carpeta PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// Verificar que los datos necesarios estén presentes
if (!isset($_POST['id_rec']) || !isset($_POST['m_pro']) || !isset($_POST['m_fle'])) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error', 
            allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: true,
        confirmButtonText: 'Aceptar',
            text: 'Faltan datos necesarios para enviar los correos',
            confirmButtonColor: '#A73737',
            confirmButtonText: 'ACEPTAR',
        }).then((result) => {
            if (result.isConfirmed) {
                window.history.back();
            }
        });
    </script>";
    exit;
}

// URLs de los formularios
$url_proveedor = "http://localhost/DexaLai/externo/correo_proveedores.php?id=" . $_POST['id_rec'];
$url_fletero = "http://localhost/DexaLai/externo/correo_fletero.php?id=" . $_POST['id_rec'];

try {
    // ==============================================
    // CORREO PARA EL PROVEEDOR
    // ==============================================
    if (!empty($_POST['m_pro'])) {
        $mail_proveedor = new PHPMailer(true); 
        $mail_proveedor->isSMTP();
        $mail_proveedor->Host = 'mail.laisa.com.mx';
        $mail_proveedor->SMTPAuth = true;
        $mail_proveedor->Username = 'contacto@laisa.com.mx';
        $mail_proveedor->Password = 'La&23Inf1nit@021'; 
        $mail_proveedor->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail_proveedor->Port = 465;

        // Destinatario
        $mail_proveedor->SetFrom('contacto@laisa.com.mx', 'Sistema de Recolecciones LAISA');
        $mail_proveedor->AddAddress($_POST['m_pro']);
        $mail_proveedor->addCC('sistemas2@glama.com.mx');

        // Cuerpo del correo para proveedor
        $mail_proveedor->isHTML(true);
        $mail_proveedor->Subject = 'Solicitud de datos para orden de recolección ' . $_POST['folio'];
        $mail_proveedor->CharSet = 'UTF-8';
        $mail_proveedor->Encoding = 'base64';
        $mail_proveedor->Body = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Solicitud de Datos - Proveedor</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f7f7f7">
                <tr>
                    <td align="center" style="padding: 30px 0;">
                        <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden;">
                            <!-- Header -->
                            <tr>
                                <td bgcolor="#230871" style="padding: 20px; text-align: center;">
                                    <h1 style="color: white; margin: 0; font-size: 24px;">SOLICITUD DE DATOS - PROVEEDOR</h1>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 30px;">
                                    <h2 style="color: #333; margin-top: 0;">Estimado Proveedor ' . $_POST['id_pro'] . ',</h2>
                                    <p style="color: #555; line-height: 1.6; font-size: 16px;">
                                        Le solicitamos amablemente completar la información de su remisión y peso para la orden de recolección:
                                    </p>
                                    
                                    <div style="background-color: #f9f9f9; border-left: 4px solid #230871; padding: 15px; margin: 20px 0;">
                                        <h3 style="color: #230871; margin: 0; font-size: 20px;">Orden: <strong>' . $_POST['folio'] . '</strong></h3>
                                    </div>
                                    
                                    <p style="color: #555; line-height: 1.6; font-size: 16px;">
                                        Es importante que complete esta información para procesar su orden correctamente.
                                    </p>
                                    
                                    <div style="text-align: center; margin: 30px 0;">
                                        <a href="' . $url_proveedor . '" style="display: inline-block; background-color: #230871; color: white; padding: 14px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
                                            Completar información del proveedor
                                        </a>
                                    </div>
                                    
                                    <div style="background-color: #fff4e6; border: 1px solid #ffd8b5; border-radius: 4px; padding: 15px; margin: 20px 0;">
                                        <h4 style="color: #cc6600; margin-top: 0;">⚠️ Información Importante</h4>
                                        <p style="color: #cc6600; margin: 0; font-size: 14px;">
                                            Este enlace es exclusivo para su uso. Por favor complete los datos solicitados a la brevedad.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td bgcolor="#f5f5f5" style="padding: 20px; text-align: center; font-size: 14px; color: #777;">
                                    <p style="margin: 0 0 10px 0;">Este es un mensaje automático, por favor no responder a este correo.</p>
                                    <p style="margin: 0;">Sistema de Recolecciones LAISA</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';

        $mail_proveedor->send();
    }

    // ==============================================
    // CORREO PARA EL FLETERO
    // ==============================================
    if (!empty($_POST['m_fle'])) {
        $mail_fletero = new PHPMailer(true); 
        $mail_fletero->isSMTP();
        $mail_fletero->Host = 'mail.laisa.com.mx';
        $mail_fletero->SMTPAuth = true;
        $mail_fletero->Username = 'contacto@laisa.com.mx';
        $mail_fletero->Password = 'La&23Inf1nit@021'; 
        $mail_fletero->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail_fletero->Port = 465;

        // Destinatario
        $mail_fletero->SetFrom('contacto@laisa.com.mx', 'Sistema de Recolecciones LAISA');
        $mail_fletero->AddAddress($_POST['m_fle']);
        $mail_fletero->addCC('sistemas2@glama.com.mx');

        // Cuerpo del correo para fletero
        $mail_fletero->isHTML(true);
        $mail_fletero->Subject = 'Solicitud de datos para orden de recolección ' . $_POST['folio'];
        $mail_fletero->CharSet = 'UTF-8';
        $mail_fletero->Encoding = 'base64';
        $mail_fletero->Body = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Solicitud de Datos - Fletero</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f7f7f7">
                <tr>
                    <td align="center" style="padding: 30px 0;">
                        <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden;">
                            <!-- Header -->
                            <tr>
                                <td bgcolor="#28a745" style="padding: 20px; text-align: center;">
                                    <h1 style="color: white; margin: 0; font-size: 24px;">SOLICITUD DE DATOS - FLETERO</h1>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 30px;">
                                    <h2 style="color: #333; margin-top: 0;">Estimado Fletero ' . $_POST['id_fle'] . ',</h2>
                                    <p style="color: #555; line-height: 1.6; font-size: 16px;">
                                        Le solicitamos amablemente completar la información de factura y peso del flete para la orden de recolección:
                                    </p>
                                    
                                    <div style="background-color: #f9f9f9; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;">
                                        <h3 style="color: #28a745; margin: 0; font-size: 20px;">Orden: <strong>' . $_POST['folio'] . '</strong></h3>
                                    </div>
                                    
                                    <p style="color: #555; line-height: 1.6; font-size: 16px;">
                                        Es importante que complete esta información para procesar su servicio correctamente.
                                    </p>
                                    
                                    <div style="text-align: center; margin: 30px 0;">
                                        <a href="' . $url_fletero . '" style="display: inline-block; background-color: #28a745; color: white; padding: 14px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
                                            Completar información del fletero
                                        </a>
                                    </div>
                                    
                                    <div style="background-color: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 4px; padding: 15px; margin: 20px 0;">
                                        <h4 style="color: #2e7d32; margin-top: 0;">💡 Información Importante</h4>
                                        <p style="color: #2e7d32; margin: 0; font-size: 14px;">
                                            Este enlace es exclusivo para su uso. Por favor complete los datos solicitados a la brevedad.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td bgcolor="#f5f5f5" style="padding: 20px; text-align: center; font-size: 14px; color: #777;">
                                    <p style="margin: 0 0 10px 0;">Este es un mensaje automático, por favor no responder a este correo.</p>
                                    <p style="margin: 0;">Sistema de Recolecciones LAISA</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';

        $mail_fletero->send();
    }

    // Mensaje de éxito
    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Correos enviados con éxito',
            allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: true,
        confirmButtonText: 'Aceptar',
            html: 'Se enviaron notificaciones a:<br>' +
                  '• Proveedor: " . addslashes($_POST['m_pro']) . "<br>' +
                  '• Fletero: " . addslashes($_POST['m_fle']) . "',
            confirmButtonColor: '#3762A7',
            confirmButtonText: 'ACEPTAR',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php?p=V_recoleccion&id=" . $_POST['id_rec'] . "';
            }
        });
    </script>";

} catch(Exception $e) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error en el envío',
            allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: true,
        confirmButtonText: 'Aceptar',
            text: 'Ocurrió un error al enviar los correos: " . addslashes($e->getMessage()) . "',
            confirmButtonColor: '#A73737',
            confirmButtonText: 'ACEPTAR',
        }).then((result) => {
            if (result.isConfirmed) {
                window.history.back();
            }
        });
    </script>";
}
?>

<!-- Incluir SweetAlert2 -->
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</body>
</html>