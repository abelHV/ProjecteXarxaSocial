document.addEventListener("DOMContentLoaded", function () {
    // Obtener el token de la URL
    const params = new URLSearchParams(window.location.search);
    document.getElementById("token").value = params.get("token");

    document.getElementById("resetForm").addEventListener("submit", function (event) {
        event.preventDefault();
        
        let username = document.getElementById("username").value.trim();
        let newPassword = document.getElementById("newPassword").value.trim();
        let token = document.getElementById("token").value;
        let messageBox = document.getElementById("message");

        if (!username || !newPassword) {
            messageBox.textContent = "Por favor, completa todos los campos.";
            messageBox.style.color = "red";
            return;
        }

        if (newPassword.length < 6) {
            messageBox.textContent = "La contraseña debe tener al menos 6 caracteres.";
            messageBox.style.color = "red";
            return;
        }

        // Enviar datos al servidor
        fetch("resetPasswd.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `token=${token}&username=${encodeURIComponent(username)}&new_password=${encodeURIComponent(newPassword)}`
        })
        .then(response => response.text())
        .then(data => {
            messageBox.textContent = data;
            messageBox.style.color = data.includes("correctamente") ? "green" : "red";
        })
        .catch(error => {
            messageBox.textContent = "Error en la conexión.";
            messageBox.style.color = "red";
        });
    });
});
