<?php
session_start();
session_destroy();
header("Location: ../login%20form/login.php");
exit();
?>
