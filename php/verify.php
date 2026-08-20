<?php
require 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Buscar usuario en la tabla temporal `UsuariPendent`
    $sql = "SELECT * FROM UsuariPendent WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Insertar en la tabla final `Usuari` (incluyendo imagen_perfil)
        $sqlInsert = "INSERT INTO Usuari (mail, username, passHash, nom, cognom, dataNaixement, localitzacio, descripcio, dataCreacio, actiu, imagen_perfil) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("sssssssss", $user['mail'], $user['username'], $user['passHash'], 
                                             $user['nom'], $user['cognom'], $user['dataNaixement'], 
                                             $user['localitzacio'], $user['descripcio'], $user['imagen_perfil']); // Añadido imagen_perfil
        
        if ($stmtInsert->execute()) {
            // Eliminar usuario de `UsuariPendent`
            $sqlDelete = "DELETE FROM UsuariPendent WHERE token = ?";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->bind_param("s", $token);
            $stmtDelete->execute();

            echo "<script>
                    alert('✅ Cuenta verificada correctamente. Ahora puedes iniciar sesión.');
                    window.location.href = '../html/InicioSesion.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('❌ Token inválido o ya utilizado.');
                window.location.href = '../html/Registro.html';
              </script>";
    }
}
?>