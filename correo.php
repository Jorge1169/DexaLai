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

try {
    // Conexión con el correo de envío
    $mail = new PHPMailer(true); 
    $mail->isSMTP();
    $mail->Host ='mail.laisa.com.mx';
    $mail->SMTPAuth = true;
    $mail->Username = 'contacto@laisa.com.mx';
    $mail->Password = 'La&23Inf1nit@021'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Destinatario
    $mail->SetFrom('contacto@laisa.com.mx','Sistema de Notificaciones LAISA');
    $mail->AddAddress('contacto@laisa.com.mx', 'Contacto');
    $mail->addCC('sistemas2@glama.com.mx');
    $mail->addCC($_POST['correoTr']);

    // Cuerpo
    $mail->isHTML(true);
    $mail->Subject = 'URGENTE: Evidencia pendiente para remisión ' . $_POST['remisionV'];
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Body = '
      <!DOCTYPE html>
      <html lang="es">
      <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Recordatorio de Evidencia</title>
      </head>
      <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f7f7f7;">
          <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f7f7f7">
              <tr>
                  <td align="center" style="padding: 30px 0;">
                      <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden;">
                          <!-- Header -->
                          <tr>
                              <td bgcolor="#230871" style="padding: 20px; text-align: center;">
                                  <h1 style="color: white; margin: 0; font-size: 24px;">RECORDATORIO DE EVIDENCIA PENDIENTE</h1>
                              </td>
                          </tr>
                          
                          <!-- Content -->
                          <tr>
                              <td style="padding: 30px;">
                                  <h2 style="color: #333; margin-top: 0;">Hola Transportista ' . $_POST['id_trans'] . ',</h2>
                                  <p style="color: #555; line-height: 1.6; font-size: 16px;">
                                      Te recordamos que falta por subir la evidencia de entrega para la factura con remisión:
                                  </p>
                                  
                                  <div style="background-color: #f9f9f9; border-left: 4px solid #230871; padding: 15px; margin: 20px 0;">
                                      <h3 style="color: #230871; margin: 0; font-size: 20px;">Remisión: <strong>' . $_POST['remisionV'] . '</strong></h3>
                                  </div>
                                  
                                  <p style="color: #555; line-height: 1.6; font-size: 16px;">
                                      Es importante que completes este proceso lo antes posible.
                                  </p>
                                  
                                  <div style="text-align: center; margin: 30px 0;">
                                      <a href="' . $_POST['link_inv'] . '" style="display: inline-block; background-color: #230871; color: white; padding: 14px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
                                          Subir evidencia en InvoiceCheck
                                      </a>
                                  </div>
                                  
                                  <div style="background-color: #fff4e6; border: 1px solid #ffd8b5; border-radius: 4px; padding: 15px; margin: 20px 0;">
                                      <h4 style="color: #cc6600; margin-top: 0;">⚠️ Importante</h4>
                                      <p style="color: #cc6600; margin: 0; font-size: 14px;">
                                          Si ya subiste la evidencia, por favor ignora este mensaje. De lo contrario, te pedimos completar este requisito a la brevedad.
                                      </p>
                                  </div>
                              </td>
                          </tr>
                          
                          <!-- Footer -->
                          <tr>
                              <td bgcolor="#f5f5f5" style="padding: 20px; text-align: center; font-size: 14px; color: #777;">
                                  <p style="margin: 0 0 10px 0;">Este es un mensaje automático, por favor no responder a este correo.</p>
                                  <p style="margin: 0;">Sistema de Notificaciones LAISA</p>
                              </td>
                          </tr>
                      </table>
                  </td>
              </tr>
          </table>
      </body>
      </html>';
    
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        //error_log("PHPMailer: $str");
    };
    
    $mail->Send();

    echo "<script>

   // Función para leer cookies
    function getCookie(name) {
        const value = `; \${document.cookie}`;
        const parts = value.split(`; \${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return 'light'; // Tema predeterminado si no hay cookie
    }
    
    const currentTheme = getCookie('theme') || 'light';

            Swal.fire({
              icon: 'success',
              title: 'Correo enviado con éxito',
              text: 'Se notificó al Transportista',
              // Configuración dinámica del tema
              theme: currentTheme,
              // Personalización de colores según el tema
              color: currentTheme === 'dark' ? '#ffffff' : '#212529',
              background: currentTheme === 'dark' ? '#111827' : '#ffffff',

              confirmButtonColor: '#3762A7',
              confirmButtonText: 'ACEPTAR',
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href='index.php?p=V_venta&id=".$_POST['id_venta']."'
              }
            });
          </script>";
} catch(Exception $e) {
    echo "<script>

       // Función para leer cookies
    function getCookie(name) {
        const value = `; \${document.cookie}`;
        const parts = value.split(`; \${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return 'light'; // Tema predeterminado si no hay cookie
    }
    
    const currentTheme = getCookie('theme') || 'light';


            Swal.fire({
              icon: 'error',
              title: 'Error de envío',
              text: 'El correo no fue enviado con éxito',
              // Configuración dinámica del tema
              theme: currentTheme,
              // Personalización de colores según el tema
              color: currentTheme === 'dark' ? '#ffffff' : '#212529',
              background: currentTheme === 'dark' ? '#111827' : '#ffffff',

              confirmButtonColor: '#A73737',
              confirmButtonText: 'ACEPTAR',
            }).then((result) => {
              if (result.isConfirmed) {
                window.location.href='index.php?p=V_venta&id=".$_POST['id_venta']."'
              }
            });
          </script>";
}
?>

</body>
</html>