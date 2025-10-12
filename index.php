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
            position: relative;
            overflow: hidden;
        }

        .forms-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
        }

        .container-form {
            display: flex;
            border-radius: 20px;
            box-shadow: 0 5px 7px rgba(0, 0, 0, .1);
            height: 500px;
            max-width: 900px;
            transition: all 1s ease;
            margin: 10px;
            position: relative;
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
            background-color: #fff;
            outline: none;
            border: solid 2px #1A636D;
            border-radius: 20px;
            padding: 10px 20px;
            color: #1A636D;
            cursor: pointer;
            transition: all .3s ease;
            font-weight: 500;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .info-childs input:hover {
            background-color: #1A636D;
            border: solid 2px #fff;
            color: #fff;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .info-childs input:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
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

        .form-login input[type="submit"] {
            background-color: #42909E;
            color: #fff;
            border-radius: 20px;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
        }

        .form-login input[type="submit"]:hover {
            background-color: #65B1BF;
        }

        .hide {
            position: absolute;
            transform: translateY(-300%);
            opacity: 0;
            pointer-events: none;
        }

        /* Asegurar que SweetAlert2 no afecte el layout */
        .swal2-container {
            z-index: 9999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .swal2-popup {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
        }

        /* Prevenir scroll y mantener posición fija */
        body.swal2-shown {
            overflow: hidden !important;
        }

        /* Asegurar que el contenedor de formularios mantenga su posición */
        .forms-container {
            will-change: transform;
            backface-visibility: hidden;
        }

        .form-register .alerta-error,
        .form-register .alerta-exito {
            display: none;
        }

        .alerta-error {
            background-color: #F66060;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

       

        .form-register .error {
            outline: solid 2px #9d2222;
        }

        /* Responsive Design */
        @media screen and (max-width: 1024px) {
            .forms-container {
                width: 95%;
                max-width: 800px;
            }
            
            .container-form {
                height: auto;
                min-height: 500px;
            }
        }

        @media screen and (max-width: 768px) {
            .forms-container {
                width: 95%;
                max-width: 600px;
            }
            
            .info-childs h2 {
                font-size: 2rem;
            }
            
            .form-information-childs h2 {
                font-size: 1.8rem;
            }
            
            .form-register {
                gap: 15px;
            }
            
            .form-register div {
                flex: 1 1 100%;
            }
        }

        @media screen and (max-width: 580px) {
            body {
                padding: 10px;
            }
            
            .forms-container {
                width: 100%;
                max-width: 100%;
                position: relative;
                top: auto;
                left: auto;
                transform: none;
            }

            .container-form {
                height: auto;
                flex-direction: column;
                margin: 0;
                border-radius: 15px;
            }

            .information {
                width: 100%;
                padding: 25px 20px;
                border-top-left-radius: 15px;
                border-top-right-radius: 15px;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }

            .info-childs h2 {
                font-size: 1.8rem;
                margin-bottom: 10px;
            }
            
            .info-childs p {
                font-size: 14px;
                margin: 10px 0 20px 0;
                line-height: 1.4;
            }
            
            .info-childs input {
                padding: 10px 20px;
                font-size: 14px;
                margin-top: 10px;
            }

            .form-information {
                width: 100%;
                padding: 25px 20px;
                border-top-left-radius: 0;
                border-top-right-radius: 0;
                border-bottom-left-radius: 15px;
                border-bottom-right-radius: 15px;
            }
            
            .form-information-childs h2 {
                font-size: 1.6rem;
                margin-bottom: 10px;
            }
            
            .form-information-childs p {
                font-size: 14px;
                margin-bottom: 20px;
            }

            .form-register {
                gap: 15px;
            }
            
            .form-register div {
                flex: 1 1 100%;
            }
            
            .form label {
                margin-bottom: 12px;
            }
            
            .form input[type="submit"] {
                padding: 10px 15px;
                font-size: 14px;
                margin-top: 15px;
            }
            
            .alerta-error {
                font-size: 13px;
                padding: 8px 12px;
                margin-top: 8px;
            }

            .hide {
                transform: translateY(100%);
            }
        }

        @media screen and (max-width: 480px) {
            .info-childs h2 {
                font-size: 1.6rem;
            }
            
            .form-information-childs h2 {
                font-size: 1.4rem;
            }
            
            .info-childs p,
            .form-information-childs p {
                font-size: 13px;
            }
            
            .form label input {
                font-size: 14px;
                padding: 12px;
            }
            
            .form input[type="submit"] {
                padding: 10px 15px;
                font-size: 14px;
            }
        }
    </style>

    <div class="forms-container">
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
                                <input type="text" placeholder="Nombre Usuario" name="userName" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+" inputmode="text" maxlength="50" oninput="this.value=this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ ]+/g,'')">
                            </label>
                        </div>
                        <div>
                            <label>
                                <i class='bx bx-user'></i>
                                <input type="text" placeholder="Apellido" name="userLastName" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]+" inputmode="text" maxlength="50" oninput="this.value=this.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ ]+/g,'')">
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
                                <input type="text" placeholder="DNI" name="userDNI" pattern="\d{8}" inputmode="numeric" maxlength="8" oninput="this.value=this.value.replace(/\D/g,'')">
                            </label>
                        </div>
                        <div>
                            <label>
                                <i class='bx bx-phone'></i>
                                <input type="tel" placeholder="Teléfono" name="userPhone" pattern="\d{10}" inputmode="numeric" maxlength="10" oninput="this.value=this.value.replace(/\D/g,'')">
                            </label>
                        </div>
                        <div>
                            <label>
                                <i class='bx bx-lock-alt'></i>
                                <input type="password" placeholder="Contraseña" name="userPassword" required>
                            </label>
                        </div>
                        
                        <input type="submit" value="Registrarse">
                        <div class="alerta-error" id="alerta-registro" style="display: none;">Todos los campos son obligatorios</div>
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
                        <div class="alerta-error" id="alerta-login" style="display: none;">Todos los campos son obligatorios</div>
                        <div class="alerta-exito">Te registraste correctamente</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="alertas.js"></script>
</body>
</html>
