<?PHP
// Incluir las funciones principales del proyecto Retwis.
include("retwis.php");

// Comprobar que todos los campos del formulario de registro estén completos.
if (!gt("username") || !gt("password") || !gt("password2"))
    goback("¡Todos los campos del formulario de registro son necesarios!");
// Comprobar que las contraseñas ingresadas coincidan.
if (gt("password") != gt("password2"))
    goback("¡Los dos campos de contraseña no coinciden!");

// Comprobar si el nombre de usuario está disponible.
$username = gt("username");
$password = gt("password");

// Conexión con Redis.
$r = redisLink();

// Usar el comando HGET para obtener el ID del usuario asociado al nombre ingresado. 
// Si el nombre de usuario ya está en uso, se devuelve un error.
if ($r->hget("users", $username))
    goback("Lo siento, el nombre de usuario seleccionado ya está en uso.");

// Si todo está bien, registra al usuario.
// Usar el comando INCR para obtener el próximo ID de usuario.
$userid = $r->incr("next_user_id");
$authsecret = getrand();

// Usar el comando HSET para asociar el nombre de usuario con su ID.
$r->hset("users", $username, $userid);

// Usar el comando HMSET para guardar los detalles del usuario en Redis.
$r->hmset("user:$userid",
    "username", $username,
    "password", $password,
    "auth", $authsecret);

// Usar el comando HSET para asociar el secreto de autenticación con el ID del usuario.
$r->hset("auths", $authsecret, $userid);

// Usar el comando ZADD para agregar el nombre de usuario a la lista de usuarios ordenados por tiempo.
$r->zadd("users_by_time", time(), $username);

// Registrar al usuario y establecer la cookie de autenticación.
setcookie("auth", $authsecret, time() + 3600 * 24 * 365);

// Incluir el encabezado de la página.
include("header.php");
?>
<h2>¡Bienvenido a bordo!</h2>
Hey <?= utf8entities($username) ?>, ahora tienes una cuenta, <a href="index.php">¡un buen comienzo es escribir tu primer mensaje!</a>.
<?PHP
// Incluir el pie de página.
include("footer.php");
?>
