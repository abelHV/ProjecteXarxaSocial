const cardImages = [
  "./../img/reverseMTG.png",
  "./../img/reversePKMN.png",
  "./../img/reverseOP.png",
  "./../img/reverseYu.png",
  "./../img/reverseMLP.png",
  "./../img/reverseINV.png"
];

const createCard = () => {
  const card = document.createElement("div");
  card.className = "falling-card";

  // Posiciones iniciales y finales aleatorias
  const startX = Math.random();
  const endX = Math.random();

  card.style.setProperty("--start-x", startX);
  card.style.setProperty("--end-x", endX);

  // Asegura que la carta empiece fuera de la pantalla
  card.style.top = "-150px";
  card.style.left = `calc(100vw * ${startX})`;

  // Duración y retraso aleatorios
  card.style.animationDuration = `${Math.random() * 5 + 5}s`;
  card.style.animationDelay = `${Math.random() * 30}s`;

  // Imagen aleatoria
  const randomCard = cardImages[Math.floor(Math.random() * cardImages.length)];
  console.log(randomCard);
  card.style.backgroundImage = `url(${randomCard})`;

  // Añade la carta al contenedor
  const container = document.querySelector("body");
  container.appendChild(card);

  // Elimina la carta al terminar la animación
  card.addEventListener("animationend", () => {
    card.remove();
  });
};

// Crea una nueva carta cada 3 segundos
setInterval(createCard, 4000);
