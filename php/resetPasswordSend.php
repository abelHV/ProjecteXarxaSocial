<?php
require 'mailConfig.php'; // Incluye PHPMailer y la conexión a la base de datos
require 'db.php'; // Incluye la conexión a la base de datos

// Obtener el correo o nombre de usuario enviado por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrUsername = $_POST['email'];

    // Buscar al usuario en la base de datos
    $stmt = $conn->prepare("SELECT * FROM usuari WHERE mail = ? OR username = ?");
    $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Generar un código aleatorio
        $resetCode = bin2hex(random_bytes(32)); // Código de 64 caracteres hexadecimales
        $expiryTime = date('Y-m-d H:i:s', strtotime('+30 minutes')); // Expira en 30 minutos

        // Actualizar la base de datos con el código y la fecha de expiración
        $updateStmt = $conn->prepare("UPDATE usuari SET resetPassCode = ?, resetPassExpiry = ? WHERE id_user = ?");
        $updateStmt->bind_param("ssi", $resetCode, $expiryTime, $user['id_user']);
        $updateStmt->execute();

        // Crear el enlace de reseteo
        $resetLink = "http://localhost/php/ProjecteXarxaSocial/php/resetPassword.php?code=$resetCode&mail=" . urlencode($user['mail']);

        // Contenido del correo electrónico
        $subject = 'Restabliment de Contrasenya';
        $body = "
            <p>Hola {$user['username']},</p>
            <p>Has sol·licitat restablir la teva contrasenya. Si no has realitzat aquesta sol·licitud, ignora aquest missatge.</p>
            <p>Per restablir la teva contrasenya, fes clic a l'enllaç següent:</p>
            <p><a href='$resetLink'>Vull restablir la meva contrasenya</a></p>
            <p>Aquest enllaç expirarà en 30 minuts.</p>
        ";

        // Enviar el correo electrónico
        if (sendVerificationEmail($user['mail'], $subject, $body)) {
            echo "S'ha enviat un correu amb instruccions per restablir la teva contrasenya.";
        } else {
            echo "Hi ha hagut un error en enviar el correu. Si us plau, intenta-ho més tard.";
        }
    } else {
        echo "No s'ha trobat cap usuari amb aquest correu o nom d'usuari.";
    }
}
?>