<?PHP
include("retwis.php");
include("header.php");
?>
<h2>Timeline</h2>
<i>Latest registered users (an example of sorted sets)</i><br>
<?PHP
showLastUsers();
?>
<i>Latest 50 messages from users aroud the world!</i><br>
<?PHP
showUserPosts(-1,0,50);
include("footer.php")
?>
