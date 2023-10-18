<?PHP
// Incluir las funciones principales del proyecto Retwis.
include("retwis.php");

// Verificar si el usuario está logueado. Si no lo está, se le redirige al index.
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Incluir el encabezado de la página.
include("header.php");

// Conectar a Redis.
$r = redisLink();

// Comienza el área del formulario para hacer una nueva publicación.
?>
<div id="postform">
<form method="POST" action="post.php">
<?=utf8entities($User['username'])?>, ¿qué estás haciendo?
<br>
<table>
<tr><td><textarea cols="70" rows="3" name="status"></textarea></td></tr>
<tr><td align="right"><input type="submit" name="doit" value="Actualizar"></td></tr>
</table>
</form>
<div id="homeinfobox">
<?php
// Usar el comando ZCARD para contar el número de seguidores del usuario y mostrarlo.
echo $r->zcard("followers:".$User['id'])." seguidores<br>";

// Usar el comando ZCARD para contar el número de personas que el usuario sigue y mostrarlo.
echo $r->zcard("following:".$User['id'])." siguiendo<br>";
?>
</div>
</div>

<?PHP
// Obtener el inicio de la paginación o usar 0 si no está presente.
$start = gt("start") === false ? 0 : intval(gt("start"));

// Mostrar las publicaciones del usuario con paginación.
showUserPostsWithPagination(false, $User['id'], $start, 10);

// Incluir el pie de página.
include("footer.php");
?>
