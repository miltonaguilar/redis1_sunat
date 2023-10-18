<?PHP
require 'Predis/Autoloader.php';
Predis\Autoloader::register();

/**
 * Genera y retorna una cadena al azar usando MD5.
 *
 * @return string Una cadena MD5 generada aleatoriamente.
 */
function getrand() {
    $data = random_bytes(16);
    return md5($data);
}
/**
 * Verifica si un usuario está autenticado.
 *
 * @return bool Verdadero si el usuario está autenticado, falso en caso contrario.
 */
function isLoggedIn() {
    global $User, $_COOKIE;

    if (isset($User)) return true;

    if (isset($_COOKIE['auth'])) {
        $r = redisLink();
        $authcookie = $_COOKIE['auth'];
        if ($userid = $r->hget("auths",$authcookie)) {
            if ($r->hget("user:$userid","auth") != $authcookie) return false;
            loadUserInfo($userid);
            return true;
        }
    }
    return false;
}
/**
 * Carga la información del usuario por ID y la almacena en una variable global.
 *
 * @param int $userid ID del usuario.
 * @return bool Verdadero en éxito.
 */
function loadUserInfo($userid) {
    global $User;

    $r = redisLink();
    $User['id'] = $userid;
    $User['username'] = $r->hget("user:$userid","username");
    return true;
}

/**
 * Establece una conexión con la base de datos Redis y retorna el objeto cliente.
 *
 * @return object Cliente de Redis.
 */
function redisLink() {
    static $r = false;

    if ($r) return $r;

    $options = [
        'scheme' => 'tcp',
        'host'   => 'redis-16482.c15.us-east-1-2.ec2.cloud.redislabs.com',
        'port'   => 16482,
        'password' => 'u7kaMTO2MmgRj3gkRe8pvbVSfcn5C2M2'
    ];
   
    $r = new Predis\Client($options);

    //Conexión a localhost
    //$r = new Predis\Client();
    return $r;
}
/**
 * Obtiene un parámetro de las variables globales GET, POST o COOKIE.
 *
 * @param string $param Nombre del parámetro.
 * @return mixed Valor del parámetro o falso si no se encuentra.
 */
# Access to GET/POST/COOKIE parameters the easy way
function g($param) {
    global $_GET, $_POST, $_COOKIE;

    if (isset($_COOKIE[$param])) return $_COOKIE[$param];
    if (isset($_POST[$param])) return $_POST[$param];
    if (isset($_GET[$param])) return $_GET[$param];
    return false;
}
/**
 * Obtiene y limpia un parámetro de las variables globales GET, POST o COOKIE.
 *
 * @param string $param Nombre del parámetro.
 * @return mixed Valor limpio del parámetro o falso si no se encuentra.
 */
function gt($param) {
    $val = g($param);
    if ($val === false) return false;
    return trim($val);
}
/**
 * Convierte una cadena a su representación en entidades UTF-8.
 *
 * @param string $s Cadena a convertir.
 * @return string Cadena convertida en entidades UTF-8.
 */
function utf8entities($s) {
    return htmlentities($s,ENT_COMPAT,'UTF-8');
}
/**
 * Muestra un mensaje de error y sugiere al usuario volver atrás.
 *
 * @param string $msg Mensaje de error.
 */
function goback($msg) {
    include("header.php");
    echo('<div id ="error">'.utf8entities($msg).'<br>');
    echo('<a href="javascript:history.back()">Please return back and try again</a></div>');
    include("footer.php");
    exit;
}
/**
 * Calcula el tiempo transcurrido desde un momento dado en una forma legible.
 *
 * @param int $t Tiempo en segundos desde la época Unix.
 * @return string Representación legible del tiempo transcurrido.
 */
function strElapsed($t) {
    $d = time()-$t;
    if ($d < 60) return "$d seconds";
    if ($d < 3600) {
        $m = (int)($d/60);
        return "$m minute".($m > 1 ? "s" : "");
    }
    if ($d < 3600*24) {
        $h = (int)($d/3600);
        return "$h hour".($h > 1 ? "s" : "");
    }
    $d = (int)($d/(3600*24));
    return "$d day".($d > 1 ? "s" : "");
}
/**
 * Muestra una publicación basada en su ID.
 *
 * @param int $id ID de la publicación.
 * @return bool Verdadero si la publicación existe, falso en caso contrario.
 */
function showPost($id) {
    $r = redisLink();
    $post = $r->hgetall("post:$id");
    if (empty($post)) return false;

    $userid = $post['user_id'];
    $username = $r->hget("user:$userid","username");
    $elapsed = strElapsed($post['time']);
    $userlink = "<a class=\"username\" href=\"profile.php?u=".urlencode($username)."\">".utf8entities($username)."</a>";

    echo('<div class="post">'.$userlink.' '.utf8entities($post['body'])."<br>");
    echo('<i>posted '.$elapsed.' ago via web</i></div>');
    return true;
}

/**
 * Muestra una lista de publicaciones de un usuario determinado, comenzando desde una posición específica.
 * Si el ID del usuario es -1, muestra la línea de tiempo global.
 *
 * @param int $userid ID del usuario.
 * @param int $start Posición inicial en la lista.
 * @param int $count Número de publicaciones a mostrar.
 * @return bool Verdadero si hay más publicaciones para mostrar, falso en caso contrario.
 */
function showUserPosts($userid,$start,$count) {
    $r = redisLink();
    $key = ($userid == -1) ? "timeline" : "posts:$userid";
    $posts = $r->lrange($key,$start,$start+$count);
    $c = 0;
    foreach($posts as $p) {
        if (showPost($p)) $c++;
        if ($c == $count) break;
    }
    return count($posts) == $count+1;
}
/**
 * Muestra una lista paginada de publicaciones para un usuario determinado.
 *
 * @param string $username Nombre de usuario.
 * @param int $userid ID del usuario.
 * @param int $start Posición inicial en la lista.
 * @param int $count Número de publicaciones a mostrar por página.
 */
function showUserPostsWithPagination($username,$userid,$start,$count) {
    global $_SERVER;
    $thispage = $_SERVER['PHP_SELF'];

    $navlink = "";
    $next = $start+10;
    $prev = $start-10;
    $nextlink = $prevlink = false;
    if ($prev < 0) $prev = 0;

    $u = $username ? "&u=".urlencode($username) : "";
    if (showUserPosts($userid,$start,$count))
        $nextlink = "<a href=\"$thispage?start=$next".$u."\">Older posts &raquo;</a>";
    if ($start > 0) {
        $prevlink = "<a href=\"$thispage?start=$prev".$u."\">&laquo; Newer posts</a>".($nextlink ? " | " : "");
    }
    if ($nextlink || $prevlink)
        echo("<div class=\"rightlink\">$prevlink $nextlink</div>");
}
/**
 * Muestra los nombres de usuario de los últimos usuarios registrados.
 */
function showLastUsers() {
    $r = redisLink();
    $users = $r->zrevrange("users_by_time",0,9);
    echo("<div>");
    foreach($users as $u) {
        echo("<a class=\"username\" href=\"profile.php?u=".urlencode($u)."\">".utf8entities($u)."</a> ");
    }
    echo("</div><br>");
}

?>
