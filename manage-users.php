<?php


require_once 'includes\connection.php';
require_once 'includes\user.class.php';


if (isset($_REQUEST['user_signup'])) {

    $first_name = $_REQUEST['first_name'];
    $last_name = $_REQUEST['last_name'];
    $phone = $_REQUEST['phone'];
    $email = $_REQUEST['email'];
    $password = $_REQUEST['password'];
    $level = $_REQUEST['level'];
    $token = $_REQUEST['token'];
    $picture = '';

    die(json_encode($user->signUpUser(compact('first_name', 'last_name', 'phone', 
        'phone', 'email', 'password', 'level', 'token', 'picture' ))));

} elseif (isset($_REQUEST['user_login'])) {
    
    $email = $_REQUEST['email'];
    $password = $_REQUEST['password'];

    die(json_encode($user->loginUser(compact('email', 'password'))));

} elseif (isset($_REQUEST['get_levels'])) {

    die(json_encode($user->getLevels()));

} elseif (isset($_REQUEST['get_groups'])) {

    die(json_encode($user->getGroups()));

} elseif (isset($_REQUEST['get_subjects'])) {

    die(json_encode($user->getSubjects()));

}  elseif (isset($_REQUEST['get_subjects_in_level'])) {
    $level_id = $_REQUEST['level_id'];
    die(json_encode($user->getSubjects($level_id)));

} elseif (isset($_REQUEST['create_group'])) {
    
    $name = $_REQUEST['name'];
    $description = $_REQUEST['description'];
    $level = $_REQUEST['level'];
    $subject = $_REQUEST['subject'];
    $group_status = $_REQUEST['group_status'];
    $user  = $_REQUEST['user'];

    die(json_encode($user->createGroup(compact('name', 'description'
        , 'level', 'subject', 'group_status', 'user'))));

} elseif (isset($_REQUEST['add_user_to_groups'])) {
    
    $user_id = $_REQUEST['user_id'];
    $groups = $_REQUEST['groups'];

    die(json_encode($user->addUserToGroups(compact('user_id', 'groups'))));

}






