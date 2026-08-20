<?php
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Asegúrate de que este require no se duplique si ya está en otro lugar
// require '../vendor/autoload.php'; // Removido si ya está en la línea 2

function sendVerificationEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cardcapture0@gmail.com';
        $mail->Password = 'ndfk dlte kaag aesa'; // Usa App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8'; // Asegura la codificación correcta

        // Configuración del correo
        $mail->setFrom('cardcapture0@gmail.com', 'CardCapture');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body); // ¡Añadido! Versión de texto plano

        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        // Puedes añadir aquí un mensaje de error más explícito para depuración
        // echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
?>