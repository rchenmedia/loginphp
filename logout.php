<?php 

include("functions/init.php");   //for session_start() and access to functions

session_destroy();

redirect("login.php");




?>