document.addEventListener("DOMContentLoaded", function () {
    fetch("session.php")
        .then(response => response.json())
        .then(data => {
            let menu = document.getElementById("user-menu");

            if (data.isLoggedIn) {
                menu.innerHTML = `<button id="logout-btn">Cerrar Sesión</button>`;
                document.getElementById("logout-btn").addEventListener("click", logout);
            } else {
                menu.innerHTML = `<a href="login.html">Iniciar Sesión</a>`;
            }
        });
});

function logout() {
    fetch("logout.php")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
}