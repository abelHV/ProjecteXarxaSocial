document.addEventListener("DOMContentLoaded", () => {
    const url = `https://api.pokemontcg.io/v2/cards/`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const apiData = document.getElementById("apiData");
            apiData.innerHTML = "";

            // Acceder al array 'data' dentro del objeto JSON
            if (data && data.data && data.data.length > 0) {
                data.data.forEach(card => { // Iterar sobre el array 'data'
                    const cardElement = document.createElement("div");
                    cardElement.innerHTML = `<img src="${card.images.large}" alt="${card.name}" style="width:13em; border-radius: 5px;  margin: 1em;">`;
                    apiData.appendChild(cardElement);
                });
            } else {
                apiData.innerHTML = "<p>No hay cartas disponibles.</p>";
            }
        })
        .catch(error => {
            console.error("Error al obtener los datos de la API", error);
            document.getElementById("apiData").innerHTML = "<p>Error al cargar los datos.</p>";
        });
});