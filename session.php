<?php
session_start();

if (empty($_SESSION['user_id'])) {
    $_SESSION['back_url'] = $_SERVER['REQUEST_URI'];
    header("Location: /login.php");
    die;
}
