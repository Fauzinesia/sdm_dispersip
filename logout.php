<?php
session_start();

// Destroy session
session_destroy();

// Redirect ke login
header("Location: login.php");
exit();
?>
