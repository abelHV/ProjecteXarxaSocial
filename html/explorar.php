<?php
session_start();
$sesionIniciada = isset($_SESSION['user_id']);

$categorias = [
    'Magic' => 'Magic: The Gathering',
    'Pokemon' => 'Pokémon',
    'One Piece' => 'One Piece',
    'Yu-Gi-Oh' => 'Yu-Gi-Oh!',
    'My Little Pony' => 'My Little Pony',
    'Invizimals' => 'Invizimals'
];

$juegosInfo = [
    'Magic' => 'Magic: The Gathering es un juego de cartas coleccionables de estrategia profunda donde dos o más jugadores se enfrentan utilizando mazos personalizados con hechizos, criaturas y artefactos poderosos. Cada carta representa un recurso o una táctica en el campo de batalla, y los jugadores deben usar su ingenio y planificación para derrotar a sus oponentes.',
    'Pokemon' => 'El Juego de Cartas Coleccionables Pokémon permite a los jugadores coleccionar e intercambiar cartas que representan a sus Pokémon favoritos. El juego se centra en construir mazos estratégicos y utilizar los ataques y habilidades únicos de los Pokémon para derrotar al Pokémon Activo del oponente y reclamar cartas de Premio.',
    'One Piece' => 'El One Piece Card Game sumerge a los jugadores en el mundo de piratas, tesoros y frutas del diablo del famoso manga y anime. Los jugadores construyen mazos basados en personajes y tripulaciones, utilizando sus habilidades y eventos para enfrentarse a sus rivales en emocionantes duelos de estrategia y aventura.',
    'Yu-Gi-Oh' => 'Yu-Gi-Oh! Trading Card Game es un juego de duelos rápidos y tácticos donde los jugadores invocan monstruos, activan cartas mágicas y de trampa para superar a su oponente. Con una vasta biblioteca de cartas y estrategias, los duelistas deben adaptar sus mazos y tácticas para cada desafío.',
    'My Little Pony' => 'El juego de cartas de My Little Pony celebra la amistad y la magia a través de divertidas interacciones y colecciones. Los jugadores utilizan cartas de personajes adorables y eventos mágicos para resolver problemas y trabajar juntos, fomentando la cooperación y la diversión.',
    'Invizimals' => 'En el juego de cartas de Invizimals, los jugadores coleccionan criaturas invisibles que descubren a través de la tecnología. Cada Invizimal tiene habilidades únicas que los jugadores utilizan en batallas estratégicas, combinando elementos de exploración y combate en un emocionante juego de cartas.'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar Juegos - CardCapture</title>
            <link rel="shortcut icon" href="../img/logo.png" />

    <link rel="stylesheet" href="../css/INDEXmain.css">
    <link rel="stylesheet" href="../css/explorar.css">
    <style>
        /* Estilos para el contenedor de las cartas */
        

        .hero {
            display: grid;
            gap: 40px; /* Aumentamos el espacio entre las cartas */
            padding: 40px 20px;
            justify-items: center; /* Centrar las cartas en cada celda */
            background-color:rgba(222, 153, 41, 0); /* Fondo claro */
            box-shadow: none;
        }
        .logo {
            cursor: pointer;
        }

        .carta-contenedor {
            perspective: 1000px; /* Necesario para el efecto 3D */
            width: 200px; /* Tamaño mediano para las cartas */
            height: 300px;
            border-radius: 2em;
            position: relative;
            cursor: pointer;
            margin: 3em;
        }

        .carta {
            width: 100%;
            height: 100%;
            position: absolute;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.4, 0.2, 0.2, 1);
        }

        .carta.girada {
            transform: rotateY(180deg);
        }

        .cara {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden; /* Oculta la cara trasera cuando mira hacia afuera */
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.9em; /* Reducir ligeramente el tamaño de la fuente */
            text-align: center;
            padding: 15px;
            background-color: white;
        }

        .cara-frontal {
            background-color: #ddd;
            background-size: cover;
            background-position: center;
        }

        .cara-trasera {
            background-color: #eee;
            transform: rotateY(180deg);
            font-style: italic;
            color: #555;
            padding: 15px;
            overflow: auto; /* Añade scroll si el contenido excede la altura */
            font-size: 0.8em; /* Reducir aún más el tamaño de la fuente para más texto */
            line-height: 1.3;
        }

        /* Disposición para pantallas grandes (3 columnas) */
        @media (min-width: 992px) {
            .hero {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Disposición para pantallas medianas (2 columnas) */
        @media (max-width: 991px) and (min-width: 768px) {
            .hero {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Disposición para pantallas pequeñas (1 columna) */
        @media (max-width: 767px) {
            .hero {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="headerx">
        <div class="logo">CARDCAPTURE</div>
        <div class="user-menu">
            <div class="iconx" id="userIcon">
                <img src="../img/user.png" alt="Icono de usuario" class="user-icon">
            </div>
            <ul class="dropdown" id="dropdownMenu">
                <?php if (!$sesionIniciada): ?>
                    <li><a href="./InicioSesion.html">Iniciar Sesión</a></li>
                <?php else: ?>
                    <li><a href="./perfil.php">Perfil</a></li>
                    <li><a href="#">Favoritos</a></li>
                    <li><a href="./publicaciones.php">Venta</a></li>
                    <li><a href="./chat.php">Buzón</a></li>
                    <li><a href="../php/logout.php">Cerrar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <nav class="menu-categorias" id="menuCategorias">
        <button class="hamburger-btn" id="hamburgerBtn">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </button>
        <ul class="menu-lista" id="menuLista">
            <?php foreach ($categorias as $clave => $nombre): ?>
                <li><a href="../html/inicio.php?categoria=<?php echo urlencode($clave); ?>"><?php echo htmlspecialchars($nombre); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <main class="contenido-principal">
        <section class="hero">
            <?php
            $reversos = [
                'Magic' => '../img/reverseMTG.png',
                'Pokemon' => '../img/reversePKMN.png',
                'One Piece' => '../img/reverseOP.png',
                'Yu-Gi-Oh' => '../img/reverseYu.png',
                'My Little Pony' => '../img/reverseMLP.png',
                'Invizimals' => '../img/reverseINV.png'
            ];

            foreach ($categorias as $clave => $nombre): ?>
                <div class="carta-contenedor" data-juego="<?php echo htmlspecialchars($clave); ?>">
                    <div class="carta">
                        <div class="cara cara-frontal" style="background-image: url('<?php echo htmlspecialchars($reversos[$clave]); ?>');"></div>
                        <div class="cara cara-trasera"><?php echo htmlspecialchars($juegosInfo[$clave]); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </main>

    <footer id="footer">
        <canvas id="footerCanvas"></canvas>
        <div class="footer-container">
            <div class="footer-logo">
                <h2>CARDCAPTURE</h2>
                <p>Tu mercado definitivo para coleccionistas de cartas.</p>
            </div>
            <div class="footer-social">
                <h3>Síguenos</h3>
                <div class="social-icons">
                    <a href="#"><img src="../img/Facebook.png" alt="Facebook"></a>
                    <a href="#"><img src="../img/instagram.png" alt="Instagram"></a>
                    <a href="#"><img src="../img/twitter.png" alt="Twitter"></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p id="footerText">&copy; 2025 CardCapture. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="../js/script.js"></script>
    <script src="../js/footer_animation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // *** Lógica para el Dropdown de Usuario ***
            const userIcon = document.getElementById('userIcon');
            const dropdownMenu = document.getElementById('dropdownMenu');

            function toggleDropdown() {
                dropdownMenu.classList.toggle('open');
            }

            userIcon.addEventListener('click', toggleDropdown);

            document.addEventListener('click', function(event) {
                const isClickInside = userIcon.contains(event.target) || dropdownMenu.contains(event.target);
                if (!isClickInside && dropdownMenu.classList.contains('open')) {
                    dropdownMenu.classList.remove('open');
                }
            });

            // *** Lógica para el Menú de Categorías Sticky ***
            const menuCategorias = document.getElementById('menuCategorias');
            const header = document.querySelector('.headerx');
            const logo = document.querySelector('.logo');

            window.addEventListener('scroll', () => {
                if (window.scrollY > header.offsetHeight) {
                    menuCategorias.classList.add('sticky');
                } else {
                    menuCategorias.classList.remove('sticky');
                }
            });

            // *** Lógica para volver a la página anterior al hacer clic en el logo ***
            logo.addEventListener('click', function() {
                window.history.back();
            });

            // *** Lógica para girar las cartas al pasar el mouse ***
            const cartasContenedor = document.querySelectorAll('.carta-contenedor');

            cartasContenedor.forEach(contenedor => {
                contenedor.addEventListener('mouseenter', function() {
                    this.querySelector('.carta').classList.add('girada');
                });

                contenedor.addEventListener('mouseleave', function() {
                    this.querySelector('.carta').classList.remove('girada');
                });
            });
        });
    </script>
    <script src="../js/scriptHeader.js"></script>
    <script src="../js/footerAnimation.js"></script>
</body>
</html>