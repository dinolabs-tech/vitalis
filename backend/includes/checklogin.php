<?php
function check_login() {
    if (strlen($_SESSION['id']) == 0) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $extra = "login.php";
        $_SESSION["login_time"] = time(); // Set login time
        header("location:http://$host$uri/$extra");
        exit();
    }
}
