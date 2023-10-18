<?PHP
// Incluir las funciones principales del proyecto Retwis.
include("retwis.php");

// Verificar que el usuario haya ingresado tanto el nombre de usuario como la contraseña.
if (!gt("username") || !gt("password"))
    goback("Necesitas ingresar tanto el nombre de usuario como la contraseña para iniciar sesión.");

// Obtener los valores del nombre de usuario y contraseña ingresados por el usuario.
$username = gt("username");
$password = gt("password");

// Conectar a Redis.
$r = redisLink();

// Usar el comando HGET para obtener el ID del usuario a partir del nombre de usuario.
// "users" es una tabla hash en Redis que mapea los nombres de usuario a sus respectivos IDs.
$userid = $r->hget("users", $username);

// Si el nombre de usuario no está en la tabla hash "users", el inicio de sesión es inválido.
if (!$userid)
    goback("Nombre de usuario o contraseña incorrectos");

// Usar el comando HGET para obtener la contraseña real asociada al ID del usuario.
$realpassword = $r->hget("user:$userid", "password");

// Comparar la contraseña ingresada con la contraseña almacenada en Redis.
if ($realpassword != $password)
    goback("Nombre de usuario o contraseña incorrectos");

// Si la contraseña es correcta, obtener el secreto de autenticación para el usuario y establecer una cookie.
$authsecret = $r->hget("user:$userid", "auth");
setcookie("auth", $authsecret, time() + 3600 * 24 * 365);

// Redirigir al usuario a la página principal después de un inicio de sesión exitoso.
header("Location: index.php");
?>
