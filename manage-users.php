<?php


require_once 'includes\connection.php';
require_once 'includes\user.class.php';


if (isset($_GET['user_signup'])) {

    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $level = $_POST['level'];
    $token = $_POST['token'];
    $picture = '';

    die(json_encode($user->signUpUser(compact('first_name', 'last_name', 'phone', 
        'phone', 'email', 'password', 'level', 'token', 'picture' ))));

} elseif (isset($_GET['user_login'])) {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    die(json_encode($user->loginUser(compact('email', 'password'))));

}




