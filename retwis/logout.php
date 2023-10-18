<?PHP
// Incluir las funciones principales del proyecto Retwis.
include("retwis.php");

// Verificar si el usuario está logueado; si no, redireccionar a la página principal.
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Conectar a Redis.
$r = redisLink();

// Generar un nuevo secreto de autenticación.
$newauthsecret = getrand();
// Obtener el ID del usuario actual.
$userid = $User['id'];
// Usar el comando HGET para obtener el secreto de autenticación anterior del usuario.
// "user:$userid" es una tabla hash en Redis que almacena información sobre el usuario, incluido su secreto de autenticación.
$oldauthsecret = $r->hget("user:$userid", "auth");

// Actualizar el secreto de autenticación en la tabla hash del usuario con el nuevo valor.
$r->hset("user:$userid", "auth", $newauthsecret);
// Añadir el nuevo secreto de autenticación a la tabla hash "auths", que mapea secretos a IDs de usuario.
$r->hset("auths", $newauthsecret, $userid);
// Eliminar el antiguo secreto de autenticación de la tabla hash "auths".
$r->hdel("auths", $oldauthsecret);

// Redirigir al usuario a la página principal después de actualizar el secreto de autenticación.
header("Location: index.php");
?>
