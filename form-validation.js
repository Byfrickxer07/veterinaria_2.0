// Validación de campos del formulario
document.addEventListener('DOMContentLoaded', function() {
    // Obtener los campos del formulario
    const nameInput = document.querySelector('input[name="userName"]');
    const lastNameInput = document.querySelector('input[name="userLastName"]');
    const dniInput = document.querySelector('input[name="userDNI"]');
    const phoneInput = document.querySelector('input[name="userPhone"]');

    // Función para validar solo letras
    function validateLetters(input) {
        input.addEventListener('input', function(e) {
            // Reemplazar cualquier caracter que no sea letra o espacio con una cadena vacía
            this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g, '');
        });
    }

    // Función para validar solo números con máximo de 15 caracteres
    function validateNumbers(input) {
        input.addEventListener('input', function(e) {
            // Limitar a 15 caracteres
            if (this.value.length > 15) {
                this.value = this.value.slice(0, 15);
                return;
            }
            // Reemplazar cualquier caracter que no sea número
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    // Aplicar validaciones a los campos correspondientes
    if (nameInput) validateLetters(nameInput);
    if (lastNameInput) validateLetters(lastNameInput);
    if (dniInput) validateNumbers(dniInput);
    if (phoneInput) validateNumbers(phoneInput);
});
