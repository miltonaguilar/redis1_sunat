<?PHP
// Incluir las funciones principales del proyecto Retwis.
include("retwis.php");

// Incluir el archivo del encabezado de la página.
include("header.php");

// Conexión con Redis.
$r = redisLink();

// Comprobar si se ha pasado un nombre de usuario ("u") y si ese usuario existe en la base de datos de Redis.
// Si no es así, se redirige al usuario a la página principal.
if (!gt("u") || !($userid = $r->hget("users",gt("u")))) {
    header("Location: index.php");
    exit(1);
}

// Mostrar el nombre del usuario en la página.
echo("<h2 class=\"username\">".utf8entities(gt("u"))."</h2>");

// Si el usuario está logueado y no está visitando su propio perfil,
// verifica si está siguiendo o no al usuario del perfil que está visitando.
if (isLoggedIn() && $User['id'] != $userid) {
    // Usar el comando ZSCORE para comprobar si el usuario actual sigue al usuario del perfil.
    // Si no lo sigue, $isfollowing será NULL.
    $isfollowing = $r->zscore("following:".$User['id'],$userid);
    if (!$isfollowing) {
        // Si el usuario actual no está siguiendo al usuario del perfil, muestra un botón para seguirlo.
        echo("<a href=\"follow.php?uid=$userid&f=1\" class=\"button\">Follow this user</a>");
    } else {
        // Si el usuario actual ya está siguiendo al usuario del perfil, muestra un botón para dejar de seguirlo.
        echo("<a href=\"follow.php?uid=$userid&f=0\" class=\"button\">Stop following</a>");
    }
}
?>

<?PHP
// Establecer el punto de inicio para mostrar los posts. Si no se especifica un punto de inicio, se usa 0.
$start = gt("start") === false ? 0 : intval(gt("start"));

// Mostrar los posts del usuario con paginación.
showUserPostsWithPagination(gt("u"),$userid,$start,10);

// Incluir el archivo del pie de página.
include("footer.php");
?>
