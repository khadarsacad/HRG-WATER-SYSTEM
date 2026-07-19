<?php
// 1. Bilow session-ka
session_start();

// 2. Nadiifi dhammaan xogta session-ka
$_SESSION = array();

// 3. Burburi session-ka
session_destroy();

// 4. Si toos ah dib ugu celi bogga login-ka (Ma jiro pop-up)
header("Location: login.php");
exit();
?>