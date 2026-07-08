@component('mail::message')
# Bienvenido/a a la Plataforma AMA

Hola {{ $user->name }},

Tu perfil de artista ha sido creado en la Plataforma AMA. A continuación encontrarás tus credenciales de acceso temporales:

**Email:** {{ $user->email }}
**Contraseña temporal:** {{ $plainPassword }}

@component('mail::button', ['url' => $loginUrl])
Iniciar sesión
@endcomponent

Por seguridad, deberás cambiar tu contraseña la primera vez que ingreses.

Si no esperabas este correo, puedes ignorarlo.

Saludos,<br>
Equipo AMA
@endcomponent
