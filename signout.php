<?php
// signout.php: Destroy session and redirect to home
session_start();
session_unset();
session_destroy();
header('Location: index.html');
exit();
?>
