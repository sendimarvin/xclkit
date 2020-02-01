<?php


require_once 'includes/connection.php';
require_once 'includes/user.class.php';


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

}  elseif (isset($_REQUEST['get_user_groups'])) {

    $user_id = $_REQUEST['user_id'];
    die(json_encode($user->getUserGroups($user_id)));

}  elseif (isset($_REQUEST['get_group_events'])) {
    
    $group_id = $_REQUEST['group_id'];
    $upcomming_events = $_REQUEST['upcomming_events'];
    die(json_encode($user->getGroupEvents($group_id, $upcomming_events)));

} elseif (isset($_REQUEST['create_event_in_group'])) {
    
    $name = $_REQUEST['name'];
    $description = $_REQUEST['description'];
    $group_id = $_REQUEST['group_id'];
    $start_date_time = $_REQUEST['start_date_time'];
    $end_date_time = $_REQUEST['end_date_time'];
    $user_id = $_REQUEST['user_id'];

    $event_data = compact('name', 'description', 'group_id', 'start_date_time', 'end_date_time', 'user_id');
    
    die(json_encode($user->createEvent($event_data)));

} elseif (isset($_REQUEST['get_group_questions'])) {

    $group_id = $_REQUEST['group_id'];
    die(json_encode($user->getGroupQuestions($group_id)));

} elseif (isset($_REQUEST['get_question_answers'])) {

    $question_id = $_REQUEST['question_id'];
    die(json_encode($user->getQuestionsAnswers($question_id)));

} elseif (isset($_REQUEST['create_question_in_group'])) {
    
    $question = $_REQUEST['question'];
    $group_id = $_REQUEST['group_id'];
    $created_by = $_REQUEST['user_id'];

    $event_data = compact('question', 'group_id', 'created_by');
    
    die(json_encode($user->createGroupQuestion($event_data)));

} elseif (isset($_REQUEST['create_answer_on_question'])) {
    
    $answer = $_REQUEST['answer'];
    $question_id = $_REQUEST['question_id'];
    $created_by = $_REQUEST['user_id'];

    $data = compact('answer', 'question_id', 'created_by');
    die(json_encode($user->createAnswerOnQuestion($data)));

} elseif (isset($_REQUEST['get_notes_in_group'])) {

    $group_id = $_REQUEST['group_id'];
    die(json_encode($user->getNotesInGroup($group_id)));

} elseif (isset($_REQUEST['get_notes_in_subject'])) {

    $subject_id = $_REQUEST['subject_id'];
    die(json_encode($user->getNotesInSubject($subject_id)));

} elseif (isset($_REQUEST['add_notes_to_a_group'])) {
    
    $name = $_REQUEST['name'];
    $notes = $_REQUEST['notes'];
    $group_id = $_REQUEST['group_id'];
    $created_by = $_REQUEST['created_by'];

    $data = compact('name', 'notes', 'group_id', 'created_by');
    die(json_encode($user->addNotesInGroup($data)));

} elseif (isset($_REQUEST['get_charts_in_event'])) {

    $event_id = $_REQUEST['event_id'];
    $user_id = $_REQUEST['user_id'];
    $from_id = $_REQUEST['from_id'];
    die(json_encode($user->getGetCharts(compact('event_id', 'user_id', 'from_id'))));

}






