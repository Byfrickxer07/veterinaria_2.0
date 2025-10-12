// Función para verificar si el DNI ya existe
function verificarDNIExistente(dni) {
    return fetch('verificar_dni.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'dni=' + encodeURIComponent(dni)
    })
    .then(response => response.text())
    .then(result => result === 'existe');
}

document.querySelector('.form-register').addEventListener('submit', async function(event) {
    event.preventDefault();
    
    const userName = document.querySelector('input[name="userName"]').value;
    const userLastName = document.querySelector('input[name="userLastName"]').value;
    const userEmail = document.querySelector('input[name="userEmail"]').value;
    const userDNI = document.querySelector('input[name="userDNI"]').value;
    const userPhone = document.querySelector('input[name="userPhone"]').value;
    const userPassword = document.querySelector('input[name="userPassword"]').value;

    // Validaciones específicas primero
    if (userName && userName.length < 4) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'El nombre de usuario debe tener más de 4 caracteres.'
        });
        return;
    }

    if (userLastName && userLastName.length < 2) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'El apellido debe tener al menos 2 caracteres.'
        });
        return;
    }

    if (userEmail) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(userEmail)) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'El correo electrónico debe ser válido y contener "@"'
            });
            return;
        }
    }

    if (userDNI && userDNI.length !== 8) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'El DNI debe tener exactamente 8 dígitos.'
        });
        return;
    }

    if (userPhone && userPhone.length !== 10) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'El teléfono debe tener exactamente 10 dígitos.'
        });
        return;
    }

    if (userPassword) {
        const passwordPattern = /^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/;
        if (!passwordPattern.test(userPassword)) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número.'
            });
            return;
        }
    }

    // Validar que todos los campos estén completos (al final)
    if (!userName || !userLastName || !userEmail || !userDNI || !userPhone || !userPassword) {
        const alerta = document.getElementById('alerta-registro');
        alerta.style.display = 'block';
        setTimeout(() => {
            alerta.style.display = 'none';
        }, 4000);
        return;
    }

    // Verificar si el DNI ya existe (después de completar todos los campos)
    try {
        const dniExiste = await verificarDNIExistente(userDNI);
        if (dniExiste) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'El DNI ya está en uso.'
            });
            return;
        }
    } catch (error) {
        console.error('Error verificando DNI:', error);
    }
    
    const formData = new FormData(this);
    
    fetch('register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(message => {
        if (message.includes('Correo electrónico no válido')) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: message
            });
        } else if (message.includes('El correo electrónico ya está en uso') || 
                   message.includes('El DNI ya está en uso') || 
                   message.includes('El correo electrónico y el DNI ya están en uso')) {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: message
            });
        } else if (message.includes('Registro exitoso')) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: message
            }).then(() => {
                // Limpiar todos los campos del formulario de registro
                document.querySelector('input[name="userName"]').value = '';
                document.querySelector('input[name="userLastName"]').value = '';
                document.querySelector('input[name="userEmail"]').value = '';
                document.querySelector('input[name="userDNI"]').value = '';
                document.querySelector('input[name="userPhone"]').value = '';
                document.querySelector('input[name="userPassword"]').value = '';
                
                // Cambiar al formulario de login
                document.querySelector('.container-form.register').classList.add('hide');
                document.querySelector('.container-form.login').classList.remove('hide');
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un problema con el registro.'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un problema con la solicitud.'
        });
    });
});

document.querySelector('.form-login').addEventListener('submit', function(event) {
    event.preventDefault();
    
    const userEmail = document.querySelector('.form-login input[name="userEmail"]').value;
    const userPassword = document.querySelector('.form-login input[name="userPassword"]').value;

    // Validar que los campos estén completos
    if (!userEmail || !userPassword) {
        const alerta = document.getElementById('alerta-login');
        alerta.style.display = 'block';
        setTimeout(() => {
            alerta.style.display = 'none';
        }, 4000);
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(message => {
        if (message === 'admin') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Inicio de sesión exitoso como administrador'
            }).then(() => {
                window.location.href = 'admin_dashboard.php';
            });
        } else if (message === 'cliente') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Inicio de sesión exitoso como cliente'
            }).then(() => {
                window.location.href = 'client_dashboard.php';
            });
        } else if (message === 'doctor') {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Inicio de sesión exitoso como doctor'
            }).then(() => {
                window.location.href = 'doctor_dashboard.php';
            });
        } else if (message === 'No se encontró el usuario') {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'El correo no está registrado'
            });
        } else if (message === 'Contraseña incorrecta') {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'La contraseña es incorrecta'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Hubo un problema con el inicio de sesión.'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un problema con la solicitud.'
        });
    });
});
