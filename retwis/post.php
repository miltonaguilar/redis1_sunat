<?PHP
// Incluir las funciones principales del proyecto Retwis.
include("retwis.php");

// Verificar si el usuario está logueado y si se ha enviado un estado (tweet).
// Si no se cumple alguna de estas condiciones, redirige a la página principal.
if (!isLoggedIn() || !gt("status")) {
    header("Location:index.php");
    exit;
}

// Conexión con Redis.
$r = redisLink();

// Utilizar el comando INCR para obtener un nuevo ID para el post. INCR incrementa el valor del contador en Redis.
$postid = $r->incr("next_post_id");

// Limpiar el estado (tweet) reemplazando saltos de línea por espacios.
$status = str_replace("\n"," ",gt("status"));

// Usar el comando HMSET para almacenar información del post en una tabla hash en Redis.
// "post:$postid" será la tabla hash que almacena el ID del usuario, el tiempo y el cuerpo del post.
$r->hmset("post:$postid","user_id",$User['id'],"time",time(),"body",$status);

// Obtener todos los seguidores del usuario usando el comando ZRANGE.
// "followers:".$User['id'] es una lista ordenada que mantiene a todos los seguidores del usuario.
$followers = $r->zrange("followers:".$User['id'],0,-1);

// Añadir el propio ID del usuario a la lista de seguidores para asegurarse de que el post también se añade a sus propios posts.
$followers[] = $User['id'];

// Insertar el ID del post en la lista de posts de cada seguidor.
foreach($followers as $fid) {
    $r->lpush("posts:$fid",$postid);
}

// Insertar el post en la "timeline" (lista de posts) usando LPUSH, que inserta el post al principio de la lista.
$r->lpush("timeline",$postid);

// Usar el comando LTRIM para asegurarse de que la "timeline" solo contenga los últimos 1000 posts.
$r->ltrim("timeline",0,1000);

// Redirigir al usuario a la página principal después de publicar el post.
header("Location: index.php");
?>
