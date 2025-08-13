<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Formulario de Registro e Inicio de Sesión</title>
</head>
<body>
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background-color: #7ABFC6;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-image: url(img/2.jpg);
            background-size: cover;
        }

        .container-form {
            display: flex;
            border-radius: 20px;
            box-shadow: 0 5px 7px rgba(0, 0, 0, .1);
            height: 500px;
            max-width: 900px;
            transition: all 1s ease;
            margin: 10px;
        }

        .information {
            width: 40%;
            display: flex;
            align-items: center;
            text-align: center;
            background-color: #52A7B7;
            border-top-left-radius: 20px;
            border-bottom-left-radius: 20px;
        }

        .info-childs {
            width: 100%;
            padding: 0 30px;
        }

        .info-childs h2 {
            font-size: 2.5rem;
            color: #fff;
        }

        .info-childs p {
            margin: 15px 0;
            color: #f0f0f0;
        }

        .info-childs input {
            background-color: transparent;
            outline: none;
            border: solid 2px #1A636D;
            border-radius: 20px;
            padding: 10px 20px;
            color: #1A636D;
            cursor: pointer;
            transition: background-color .3s ease;
        }

        .info-childs input:hover {
            background-color: #42909E;
            border: none;
            color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
        }

        .form-information {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60%;
            text-align: center;
            background-color: #E3F3F6;
            border-top-right-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .form-information-childs {
            padding: 0 30px;
        }

        .form-information-childs h2 {
            color: #333;
            font-size: 2rem;
        }

        .form-information-childs p {
            color: #555;
        }

        .form-register {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .form-register div {
            flex: 1 1 calc(50% - 20px);
        }

        .form-register label {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            border-radius: 20px;
            padding: 0 10px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
        }

        .form-register label input {
            width: 100%;
            padding: 10px;
            background-color: #fff;
            border: none;
            outline: none;
            border-radius: 20px;
            color: #333;
        }

        .form-register label i {
            color: #a7a7a7;
        }

        .form-register input[type="submit"] {
            background-color: #42909E;
            color: #fff;
            border-radius: 20px;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
        }

        .form-register input[type="submit"]:hover {
            background-color: #65B1BF;
        }

        .hide {
            position: absolute;
            transform: translateY(-300%);
        }

        .form-register .alerta-error,
        .form-register .alerta-exito {
            display: none;
        }

       

        .form-register .error {
            outline: solid 2px #9d2222;
        }

        @media screen and (max-width:750px) {
            html {
                font-size: 12px;
            }
        }

        @media screen and (max-width:580px) {
            html {
                font-size: 15px;
            }

            .container-form {
                height: auto;
                flex-direction: column;
            }

            .information {
                width: 100%;
                padding: 20px;
                border-top-left-radius: 20px;
                border-top-right-radius: 20px;
                border-bottom-left-radius: 0px;
                border-bottom-right-radius: 0;
            }

            .form-information {
                width: 100%;
                padding: 20px;
                border-top-left-radius: 0px;
                border-top-right-radius: 0px;
                border-bottom-left-radius: 20px;
                border-bottom-right-radius: 20px;
            }

            .hide {
                transform: translateY(150%);
            }
        }
    </style>

    <div class="container-form register">
        <div class="information">
            <div class="info-childs">
                <h2>Bienvenido</h2>
                <p>Para unirte a nuestra comunidad por favor Inicia Sesión con tus datos</p>
                <input type="button" value="Iniciar Sesión" id="sign-in">
            </div>
        </div>
        <div class="form-information">
            <div class="form-information-childs">
                <h2>Crear una Cuenta</h2>
                <form class="form form-register" action="register.php" method="post" novalidate>
                    <div>
                        <label>
                            <i class='bx bx-user'></i>
                            <input type="text" placeholder="Nombre Usuario" name="userName" required pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{1,15}$" maxlength="15" title="Solo letras, máximo 15 caracteres">
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-user'></i>
                            <input type="text" placeholder="Apellido" name="userLastName" required pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ ]{1,15}$" maxlength="15" title="Solo letras, máximo 15 caracteres">
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-envelope'></i>
                            <input type="email" placeholder="Correo Electrónico" name="userEmail" required>
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-globe'></i>
                            <input type="text" placeholder="DNI" name="userDNI" required pattern="^[0-9]{1,15}$" maxlength="15" title="Solo números, máximo 15 caracteres">
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-phone'></i>
                            <input type="tel" placeholder="Teléfono" name="userPhone" required pattern="^[0-9]{1,15}$" maxlength="15" title="Solo números, máximo 15 caracteres">
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" placeholder="Contraseña" name="userPassword" required>
                        </label>
                    </div>
                    
                    <input type="submit" value="Registrarse">
                    <div class="alerta-error">Todos los campos son obligatorios</div>
                    <div class="alerta-exito">Te registraste correctamente</div>
                </form>
            </div>
        </div>
    </div>

    <div class="container-form login hide">
        <div class="information">
            <div class="info-childs">
                <h2>¡¡Bienvenido nuevamente!!</h2>
                <p>Para unirte a nuestra comunidad por favor Inicia Sesión con tus datos</p>
                <input type="button" value="Registrarse" id="sign-up">
            </div>
        </div>
        <div class="form-information">
            <div class="form-information-childs">
                <h2>Iniciar Sesión</h2>
                <p>o Iniciar Sesión con una cuenta</p>
                <form class="form form-login" action="login.php" method="post" novalidate>
                    <div>
                        <label>
                            <i class='bx bx-envelope'></i>
                            <input type="email" placeholder="Correo Electrónico" name="userEmail" required>
                        </label>
                    </div>
                    <div>
                        <label>
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" placeholder="Contraseña" name="userPassword" required>
                        </label>
                    </div>
                    <input type="submit" value="Iniciar Sesión">
                    <div class="alerta-error">Todos los campos son obligatorios</div>
                    <div class="alerta-exito">Te registraste correctamente</div>
                </form>
            </div>
        </div>
    </div>
    <script src="index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="alertas.js"></script>
</body>
</html>
