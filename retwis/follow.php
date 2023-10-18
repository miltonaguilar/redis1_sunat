<?PHP
// Incluir las funciones principales del proyecto Retwis.
include("retwis.php");

// Conectar a Redis.
$r = redisLink();

// Verificar si el usuario está logueado, si no tiene "uid" o si el parámetro "f" no está presente.
// Además, recupera el nombre de usuario usando el comando HGET en Redis.
// Si alguna de estas condiciones no se cumple, redirige al usuario al index.
if (!isLoggedIn() || !gt("uid") || gt("f") === false ||
    !($username = $r->hget("user:".gt("uid"),"username"))) {
    // Redirección al index.
    header("Location:index.php");
    exit;
}

// Convertir los parámetros a enteros.
$f = intval(gt("f"));
$uid = intval(gt("uid"));

// Si el uid no es el mismo que el id del usuario actualmente logueado, se realiza la acción de seguir o dejar de seguir.
if ($uid != $User['id']) {
    if ($f) {
        // Si el parámetro "f" es verdadero, añadir al usuario a las listas de seguidores y siguiendo usando ZADD.
        $r->zadd("followers:".$uid,time(),$User['id']); // Añadir al usuario actual a la lista de seguidores del usuario objetivo.
        $r->zadd("following:".$User['id'],time(),$uid); // Añadir al usuario objetivo a la lista de personas que el usuario actual sigue.
    } else {
        // Si el parámetro "f" es falso, remover al usuario de las listas de seguidores y siguiendo usando ZREM.
        $r->zrem("followers:".$uid,$User['id']); // Remover al usuario actual de la lista de seguidores del usuario objetivo.
        $r->zrem("following:".$User['id'],$uid); // Remover al usuario objetivo de la lista de personas que el usuario actual sigue.
    }
}

// Redirección al perfil del usuario.
header("Location: profile.php?u=".urlencode($username));
?>
