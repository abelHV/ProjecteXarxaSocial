document.addEventListener("DOMContentLoaded", function () {
    console.log("JavaScript cargado correctamente");

    // Agregar efecto de vibración sutil a los iconos de redes sociales
    const icons = document.querySelectorAll(".icon");

    function animateIcons() {
        icons.forEach((icon, index) => {
            setInterval(() => {
                let randomX = (Math.random() - 0.5) * 6; // Movimiento aleatorio
                let randomY = (Math.random() - 0.5) * 6;
                icon.style.transform = `translate(${randomX}px, ${randomY}px)`;
            }, 1000 + index * 300);
        });
    }

    animateIcons();

    // Animación de partículas en el fondo del footer
    const canvas = document.getElementById("footerCanvas");
    const ctx = canvas.getContext("2d");
    let particles = [];

    function initCanvas() {
        canvas.width = footer.clientWidth;
        canvas.height = footer.clientHeight;

        particles = [];
        for (let i = 0; i < 50; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                radius: Math.random() * 2 + 1,
                dx: (Math.random() - 0.5) * 1.5,
                dy: (Math.random() - 0.5) * 1.5
            });
        }
    }

    function animateParticles() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fillStyle = "white";
            ctx.fill();

            p.x += p.dx;
            p.y += p.dy;

            if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
            if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
        });

        requestAnimationFrame(animateParticles);
    }

    initCanvas();
    animateParticles();
});


document.addEventListener("DOMContentLoaded", function () {
    console.log("JavaScript para el header cargado correctamente");

    // Configuración del canvas en el header
    const canvas = document.getElementById("headerCanvas");
    const ctx = canvas.getContext("2d");
    let particles = [];

    function initCanvas() {
        canvas.width = header.clientWidth;
        canvas.height = header.clientHeight;

        particles = [];
        for (let i = 0; i < 50; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                radius: Math.random() * 2 + 1,
                dx: (Math.random() - 0.5) * 1.5,
                dy: (Math.random() - 0.5) * 1.5
            });
        }
    }

    function animateParticles() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fillStyle = "white";
            ctx.fill();

            p.x += p.dx;
            p.y += p.dy;

            if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
            if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
        });

        requestAnimationFrame(animateParticles);
    }

    initCanvas();
    animateParticles();

    // Redimensionar el canvas si la ventana cambia de tamaño
    window.addEventListener("resize", initCanvas);
});
