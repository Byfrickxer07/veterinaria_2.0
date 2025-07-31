document.querySelector('.form-register').addEventListener('submit', function(event) {
    event.preventDefault();
    
    const userName = document.querySelector('input[name="userName"]').value;
    const userEmail = document.querySelector('input[name="userEmail"]').value;
    const userPassword = document.querySelector('input[name="userPassword"]').value;

    if (userName.length < 4) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'El nombre de usuario debe tener más de 4 caracteres.'
        });
        return;
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(userEmail)) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'El correo electrónico debe ser válido y contener "@"'
        });
        return;
    }

    const passwordPattern = /^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/;
    if (!passwordPattern.test(userPassword)) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'La contraseña debe tener al menos 8 caracteres, una letra mayúscula y un número.'
        });
        return;
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
        } else if (message.includes('El nombre de usuario o el correo electrónico ya están en uso')) {
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
                text: 'No se encontró el usuario'
            });
        } else if (message === 'Contraseña incorrecta') {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Contraseña incorrecta'
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
