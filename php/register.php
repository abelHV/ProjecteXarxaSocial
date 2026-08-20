<?php
session_start();
require 'db.php'; // Conexión a la base de datos
require 'mailConfig.php'; // Configuración de PHPMailer para enviar correos

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST["firstName"]);
    $lastName = trim($_POST["lastName"]);
    $email = trim($_POST["email"]);
    $username = trim($_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(32)); // Generar un token único

    // Verificar si el correo ya está en uso en ambas tablas
    $checkSql = "SELECT mail FROM Usuari WHERE mail = ? UNION SELECT mail FROM UsuariPendent WHERE mail = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $email, $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        echo "<script>
                alert('⚠️ Este correo ya está registrado. Intenta con otro.');
                window.location.href = '../html/Registro.html';
              </script>";
        exit();
    }
    $checkStmt->close();

    // Insertar el usuario en la tabla temporal antes de confirmar
    $sql = "INSERT INTO UsuariPendent (mail, username, passHash, nom, cognom, token, dataCreacio, dataNaixement, localitzacio, descripcio) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NULL, NULL, NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $email, $username, $password, $firstName, $lastName, $token);

    if ($stmt->execute()) {
        // Enviar correo de confirmación
        $verificationLink = "localhost/CardCapture/ProjecteXarxaSocial/php/verify.php?token=$token";
        $subject = "¡Bienvenido a CardCapture! Confirma tu cuenta";
        
        // --- Nuevo diseño del cuerpo del correo ---
        $body = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verifica tu cuenta en CardCapture</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background-color: #294e73; /* Azul oscuro */
                    color: #ffffff;
                    padding: 30px 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                }
                .content {
                    padding: 30px;
                    text-align: center;
                    color: #333333;
                }
                .content p {
                    font-size: 16px;
                    line-height: 1.6;
                    margin-bottom: 20px;
                }
                .button-container {
                    padding: 0 30px 30px;
                    text-align: center;
                }
                .button {
                    display: inline-block;
                    background-color: #DE9929; /* Naranja/Dorado */
                    color: #ffffff;
                    padding: 15px 30px;
                    border-radius: 5px;
                    text-decoration: none;
                    font-weight: bold;
                    font-size: 18px;
                }
                .footer {
                    background-color: #f0f0f0;
                    color: #888888;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                }
                .footer a {
                    color: #294e73;
                    text-decoration: none;
                }
                .important-note {
                    margin-top: 25px;
                    font-size: 14px;
                    color: #666666;
                    font-style: italic;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h1>¡Bienvenido a CardCapture!</h1>
                </div>
                <div class="content">
                    <p>Hola <strong>' . $firstName . '</strong>,</p>
                    <p>Gracias por registrarte en CardCapture. Para activar tu cuenta y empezar a disfrutar de todas nuestras funciones, por favor, haz clic en el siguiente botón para verificar tu dirección de correo electrónico:</p>
                </div>
                <div class="button-container">
                    <a href="' . $verificationLink . '" class="button">Verificar mi cuenta</a>
                </div>
                <div class="content">
                    <p class="important-note">Si el botón no funciona, puedes copiar y pegar el siguiente enlace en tu navegador:</p>
                    <p><a href="' . $verificationLink . '">' . $verificationLink . '</a></p>
                    <p class="important-note">Si no solicitaste esta cuenta, por favor, ignora este mensaje.</p>
                </div>
                <div class="footer">
                    <p>&copy; ' . date("Y") . ' CardCapture. Todos los derechos reservados.</p>
                    <p>Si tienes alguna pregunta, contacta con nuestro <a href="#">equipo de soporte</a>.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        // --- Fin del nuevo diseño ---

        if (sendVerificationEmail($email, $subject, $body)) {
            echo "<script>
                    alert('📩 Se ha enviado un correo de verificación. Revisa tu bandeja de entrada.');
                    window.location.href = '../html/InicioSesion.php';
                  </script>";
        } else {
            echo "<script>
                    alert('⚠️ Error al enviar el correo de verificación.');
                    window.location.href = '../html/Registro.html';
                  </script>";
        }
        exit();
    } else {
        echo "<script>
                alert('⚠️ Error de registro: " . $stmt->error . "');
                window.location.href = '../html/Registro.html';
              </script>";
    }
    
    $stmt->close();
}
?>