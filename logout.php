<?php
session_start();
unset($_SESSION["userid"]);
unset($_SESSION["name"]);
unset($_SESSION["email"]);
unset($_SESSION["phone"]);
unset($_SESSION["address"]);
unset($_SESSION["total_amt"]);
unset($_SESSION["id"]);
unset($_SESSION["role"]);

header("Location: index.php");
exit();

?>