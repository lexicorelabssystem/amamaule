<?php
/**
 * Astra functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra
 * @since 1.0.0
 */

 function custom_welcome_email($user_id) {
    $user = get_userdata($user_id);
    $email = $user->user_email;
    $username = $user->user_login;

    // Genera una contraseña aleatoria
    $password = wp_generate_password();

    // Actualiza la contraseña del usuario
    wp_set_password($password, $user_id);

    $subject = '¡Bienvenido (a) a AMA Maule 2024!';
    $message = '
    <html>
    <head>
        <style>
            body {
                font-family: Montserrat, sans-serif;
            }
            .container {
                background-color: #f4f4f4;
                padding: 20px;
                border-radius: 10px;
            }
            .button {
                display: inline-block;
                
                font-size: 16px;
                background-color: #d83d3b;
                text-decoration: none;
                border-radius: 5px;
                font-weight:bold;
                border-radius:50px;
            }

            .button a{
                color:#ffffff!important;
                padding: 10px 20px!important;
                display:block;
            }            
            .button:hover {
                background-color: #4bd18e;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>¡Bienvenido (a) a AMA Maule 2024, ' . $username . '!</h1>
            <p>Nos alegra celebrar una nueva versión de AMA y que puedas ser parte de esta iniciativa maulina.</p>
            <p><em>AMA es el Encuentro de Artistas del Maule, un espacio que desde el 2021 promueve la colaboración y el fortalecimiento entre artistas, agrupaciones de diversas disciplinas y agentes culturales.</em></p>
            <p><em>En esta IV versión tendremos Rondas de Vinculación, Mentorías, Talleres, Diálogos y Muestras Artísticas.</em></p>
            <p>Te contamos que ya tienes disponible tu usuario y contraseña para ingresar a nuestra plataforma, que te permitirá agendar en las <b>Rondas de Vinculación</b> e inscribirte a los <b>Talleres</b> a partir del Lunes 4 de noviembre, por lo que te recomendamos confirmar pronto tu participación.</p>
            <p><strong>Tus credenciales de inicio de sesión son:</strong></p>
            <p>Nombre de usuario: ' . $username . '</p>
            <p>Contraseña: ' . $password . '</p>
            <p>Puedes confirmar si deseas participar en AMA Maule 2024 en las instancias mencionadas haciendo click en el siguiente link:</p>
            <span class="button"><a href="https://amamaule.cl/formulario-de-participacion-en-rondas-de-vinculacion/" >Confirmar Participación</a></span>
            <p>Para todas las otras actividades podrás participar libremente.</p>
            <p>Para conocer todos los detalles, visita nuestra página web: <a href="https://www.amamaule.cl">amamaule.cl</a></p>
            <p>Te esperamos</p>
            <p><b>¡Gracias!</b></p>
        </div>
    </body>
    </html>
    ';

    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($email, $subject, $message, $headers);
}

add_action('user_register', 'custom_welcome_email');



remove_action('register_new_user', 'wp_send_new_user_notifications');
remove_action('edit_user_created_user', 'wp_send_new_user_notifications');
function ama_maule_enqueue_child_styles() {
	$style_path = get_stylesheet_directory() . '/style.css';
	wp_enqueue_style(
		'ama-maule-child-style',
		get_stylesheet_uri(),
		array( 'astra-theme-css' ),
		file_exists( $style_path ) ? filemtime( $style_path ) : wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'ama_maule_enqueue_child_styles', 20 );
