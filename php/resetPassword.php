<?php
require 'mailConfig.php'; // Incluye PHPMailer y la conexión a la base de datos
require 'db.php'; // Incluye la conexión a la base de datos

// Obtener los parámetros GET
$code = $_GET['code'] ?? '';
$email = $_GET['mail'] ?? '';

// Verificar si el código y el correo son válidos
$stmt = $conn->prepare("SELECT * FROM usuari WHERE mail = ? AND resetPassCode = ? AND resetPassExpiry > NOW()");
$stmt->bind_param("ss", $email, $code);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("El codi de restabliment no és vàlid o ha expirat.");
}

// Procesar el formulario de cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        echo "Les contrasenyes no coincideixen.";
    } else {
        // Actualizar la contraseña en la base de datos
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE usuari SET passHash = ?, resetPassCode = NULL, resetPassExpiry = NULL WHERE id_user = ?");
        $updateStmt->bind_param("si", $hashedPassword, $user['id_user']);
        $updateStmt->execute();

        // Enviar correo de confirmación
        $subject = 'Contrasenya Restablida';
        $body = "
            <p>Hola {$user['username']},</p>
            <p>La teva contrasenya ha estat restablerta amb èxit.</p>
            <p>Si no has realitzat aquest canvi, contacta amb nosaltres immediatament.</p>
        ";
        sendVerificationEmail($user['mail'], $subject, $body);

        // Redirigir a la página principal
        header("Location: ../html/inicio.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <title>Restablir Contrasenya</title>
    <link rel="stylesheet" href="../css/cssSesion.css" />
</head>
<body>
    <div class="paTras">
        <a href="./../index.php" class="tornar">&#8592; Tornar a la pàgina principal</a>
    </div>
    <div class="login-container">
        <h1>Restablir Contrasenya</h1>
        <form action="../php/login.php" method="POST">
            <div class="input-group">
                <label for="username">Nova Contrasenya:</label>
                <input type="password" id="username" name="username" required />
            </div>
            <div class="input-group">
                <label for="password">Confirma la Contrasenya:</label>
                <input type="password" id="password" name="password" required />
            </div>
            <button type="submit">Canviar Contrasenya</button>
        </form>
    </div>
</body>
</html>