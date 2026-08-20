document.addEventListener("DOMContentLoaded", function () {
    const editButton = document.querySelector(".edit");
    const descriptionField = document.querySelector(".profile-card textarea");

    editButton.addEventListener("click", function (event) {
        event.preventDefault();
        if (descriptionField.hasAttribute("readonly")) {
            descriptionField.removeAttribute("readonly");
            descriptionField.focus();
            editButton.textContent = "Guardar ✎";
        } else {
            descriptionField.setAttribute("readonly", true);
            editButton.textContent = "Editar perfil ✎";
        }
    });
});