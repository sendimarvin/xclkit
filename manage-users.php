<?php


//http://cresteddevelopers.com/AppFiles/SkulKitApp/manage-users.php?
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
date_default_timezone_set('Africa/Kampala');

require_once 'includes/connection.php';
require_once 'includes/user.class.php';


if (isset($_REQUEST['get_transactions'])) {

    $user_id = @$_REQUEST['user_id'];
    $phone = @$_REQUEST['phone'];

    die(json_encode($user->getTransactions(compact('user_id', 'phone'))));


} elseif (isset($_REQUEST['update_charge'])) {

    $user_id = $_REQUEST['user_id'];
    $charge = $_REQUEST['charge'];

    die(json_encode($user->saveCharge(compact('user_id', 'charge'))));


} elseif (isset($_REQUEST['save_payment_details'])) {

    $payment_username = $_REQUEST['payment_username'];
    $payment_password = $_REQUEST['payment_password'];
    $payment_currency = $_REQUEST['payment_currency'];
    $payment_message = $_REQUEST['payment_message'];

    die(json_encode($user->savePaymentSettings(compact('payment_username', 'payment_password', 'payment_currency', 'payment_message'))));
    
} elseif (isset($_REQUEST['save_company_details'])) {
    
    $email = $_REQUEST['email'];
    $phone = $_REQUEST['phone'];
    $website = $_REQUEST['website'];
    $address = $_REQUEST['address'];
    $policy = $_REQUEST['policy'];
    $about = $_REQUEST['about'];
    die(json_encode($user->saveSettings(compact('email', 'phone', 'website', 'address', 'policy', 'about'))));

} elseif (isset($_REQUEST['get_settings'])) {

    die(json_encode($user->getSettings()));

} elseif (isset($_REQUEST['add_subject'])) {
    $subject_id = @$_REQUEST['subject_id'];
    $subject_name = @$_REQUEST['subject_name'];
    $subject_level = (int) $_REQUEST['subject_level'];
    die(json_encode($user->saveSubject(compact('subject_id', 'subject_name', 'subject_level'))));

} elseif (isset($_REQUEST['reset_system'])) {
    $user_id = (int)  $_REQUEST['user_id'];
    $resetType = (int) $_REQUEST['type'];

    die(json_encode($user->resetSystem(compact('user_id', 'resetType'))));

}elseif (isset($_REQUEST['save_levels'])) {
    $level_id = @$_REQUEST['level_id'];
    $level_name = $_REQUEST['level_name'];
    die(json_encode($user->saveLevel(compact('level_id', 'level_name'))));

} elseif (isset($_REQUEST['delete_event'])) {

    $event_id = $_REQUEST['event_id'];
    die(json_encode($user->deleteEvent(compact('event_id'))));

} elseif (isset($_REQUEST['delete_group'])) {

    $group_id = $_REQUEST['group_id'];
    die(json_encode($user->deleteGroup(compact('group_id'))));

} elseif (isset($_REQUEST['callback'])) {

    $post = file_get_contents('php://input'); 
    $data = json_decode($post); 
    
    $reference_no = $data->reference; //This is your order id, mark this as paid<br 
    $reason = $data->reason; //reason you stated 
    $transaction_id = $data->transactionId; //Easypay transction Id 
    $amount = $data->amount; //amount deposited 
    $phone = $data->phone; //phone number that deposited


    die(json_encode($user->updateTransactionOnIPNResponse(compact('phone', 'reference_no', 'transaction_id', 'amount', 'reason'))));

} elseif (isset($_REQUEST['payment_status'])) {

    $success = @$_REQUEST['success'];
    $data = @$_REQUEST['data'];
    $results = array(
        'success' => $success,
        'data' => $data
    );
    mail("sendimarvin1@gmail.com", "API Response", json_encode($results));

} elseif (isset($_REQUEST['make_payment'])) {

    set_time_limit(0);

    $phone_no = @$_REQUEST['phone_no'];
    $amount = @$_REQUEST['amount'];
    $user_id = @$_REQUEST['user_id'];
    die(json_encode($user->InitiatePayment(compact('phone_no', 'amount', 'user_id'))));


} elseif (isset($_REQUEST['update_group_icon'])) {

    $group_id = (int) @$_REQUEST['group_id'];

    die(json_encode($user->updateGroupIcon(compact('group_id'))));

} elseif (isset($_REQUEST['user_signup'])) {

    $user_id = (int) @$_REQUEST['user_id'];
    $first_name = $_REQUEST['first_name'];
    $last_name = $_REQUEST['last_name'];
    $phone = $_REQUEST['phone'];
    $email = $_REQUEST['email'];
    $password = @$_REQUEST['password'];
    $level = $_REQUEST['level'];
    $token = @$_REQUEST['token'];
    $picture = '';

    die(json_encode($user->signUpUser(compact('user_id', 'first_name', 'last_name', 'phone', 
        'phone', 'email', 'password', 'level', 'token', 'picture' ))));

} elseif (isset($_REQUEST['user_login'])) {
    
    $email = $_REQUEST['email'];
    $password = $_REQUEST['password'];

    die(json_encode($user->loginUser(compact('email', 'password'))));

} elseif (isset($_REQUEST['get_levels'])) {

    die(json_encode($user->getLevels()));

} elseif (isset($_REQUEST['get_groups'])) {

    $subject_id = (int) @$_REQUEST['subject_id'];
    die(json_encode($user->getGroups(compact('subject_id'))));

} elseif (isset($_REQUEST['get_subjects'])) {

    if (isset($_REQUEST['level_id'])) {
        $level_id = $_REQUEST['level_id'];
    } else {
        $level_id = 0;
    }

    die(json_encode($user->getSubjects($level_id)));

}  elseif (isset($_REQUEST['get_subjects_in_level'])) {
    $level_id = $_REQUEST['level_id'];
    die(json_encode($user->getSubjects($level_id)));

} elseif (isset($_REQUEST['create_group'])) {

    $group_id = (int) @$_REQUEST['group_id'];
    $name = $_REQUEST['name'];
    $description = $_REQUEST['description'];
    $level_id = $_REQUEST['level_id'];
    $subject_id = $_REQUEST['subject_id'];
    $group_status = $_REQUEST['group_status'];
    $user_id = $_REQUEST['user_id'];

    die(json_encode($user->createGroup(compact('group_id', 'name', 'description'
        , 'level_id', 'subject_id', 'group_status', 'user_id'))));

} elseif (isset($_REQUEST['add_user_to_groups'])) {
    
    $user_id = $_REQUEST['user_id'];
    $groups = $_REQUEST['groups'];

    die(json_encode($user->addUserToGroups(compact('user_id', 'groups'))));

}  elseif (isset($_REQUEST['get_user_groups'])) {

    $user_id = $_REQUEST['user_id'];
    die(json_encode($user->getUserGroups($user_id)));

}  elseif (isset($_REQUEST['get_group_events'])) {
    
    $group_id = (int) @$_REQUEST['group_id'];
    $upcomming_events = (int) @$_REQUEST['upcomming_events'];
    $ongoing_events = (int) @$_REQUEST['ongoing_events'];
    $expired_events = (int) @$_REQUEST['expired_events'];

    die(json_encode($user->getGroupEvents($group_id, $upcomming_events, $ongoing_events, $expired_events)));

} elseif (isset($_REQUEST['create_event_in_group'])) {
    
    $event_id = (int) @$_REQUEST['event_id']; // BOT Discussion
    $name = $_REQUEST['name']; // BOT Discussion
    $description = $_REQUEST['description']; // Beggining of term discussion
    $group_id = $_REQUEST['group_id']; // 1
    $start_date_time = $_REQUEST['start_date_time']; // 2020-01-01 09:09:09
    $end_date_time = $_REQUEST['end_date_time']; // 2020-02-01 09:09:09
    $user_id = $_REQUEST['user_id']; // 1

    $event_data = compact('event_id', 'name', 'description', 'group_id', 'start_date_time', 'end_date_time', 'user_id');
    
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

    $subject_id = (int) @$_REQUEST['subject_id'];
    die(json_encode($user->getNotesInSubject($subject_id)));

} elseif (isset($_REQUEST['add_notes_to_subject'])) {
    
    $title = $_REQUEST['title'];
    $subject_id = @$_REQUEST['subject_id'];

    $data = compact('title', 'subject_id');
    die(json_encode($user->addNotesInSubject($data)));

} elseif (isset($_REQUEST['add_notes_to_a_group'])) {
    
    $name = $_REQUEST['name'];
    $description = @$_REQUEST['description'];
    $notes = @$_REQUEST['notes'];
    $group_id = @$_REQUEST['group_id'];
    $created_by = @$_REQUEST['created_by'];

    $data = compact('name', 'notes', 'group_id', 'created_by', 'description');
    die(json_encode($user->addNotesInGroup($data)));

} elseif (isset($_REQUEST['get_chats_in_event'])) {

    $event_id = $_REQUEST['event_id'];
    $user_id = $_REQUEST['user_id']; // User 
    $from_id = $_REQUEST['from_id']; // Starting from, default is 0
    die(json_encode($user->getGetChats(compact('event_id', 'user_id', 'from_id'))));

} elseif (isset($_REQUEST['add_chats_in_event'])) {

    $event_id = $_REQUEST['event_id']; // Event Id
    $user_id = $_REQUEST['user_id']; // user id
    $message = $_REQUEST['message']; // Hello Every one
    $resp_id = $_REQUEST['resp_id']; // default is 0 625955655
    die(json_encode($user->addChatInEvent(compact('event_id', 'user_id', 'message', 'resp_id'))));

}  elseif (isset($_REQUEST['get_event_discassants'])) {
    
    $event_id = $_REQUEST['event_id'];
    die(json_encode($user->getEventDiscassants($event_id)));

} elseif (isset($_REQUEST['delete_event_discassant'])) {
    
    $event_id = $_REQUEST['event_id'];
    $user_id = $_REQUEST['user_id'];
    die(json_encode($user->deleteEventDiscassant($event_id, $user_id)));

} elseif (isset($_REQUEST['add_event_discassant'])) {
    
    $event_id = $_REQUEST['event_id'];
    $user_id = $_REQUEST['user_id'];
    die(json_encode($user->addEventDiscassant($event_id, $user_id)));

} elseif (isset($_REQUEST['exit_group'])) {
    
    $group_id = $_REQUEST['group_id'];
    $user_id = $_REQUEST['user_id'];
    die(json_encode($user->exitGroup($group_id, $user_id)));

} elseif (isset($_REQUEST['update_user_permissions_in_group'])) {
    
    $group_id = $_REQUEST['group_id'];
    $user_id = $_REQUEST['user_id'];
    $role = $_REQUEST['role'];
    die(json_encode($user->updateUserPermStatusInGroup($group_id, $user_id, $role)));

} elseif (isset($_REQUEST['set_user_token'])) {
    
    $user_id = $_REQUEST['user_id'];
    $token = $_REQUEST['token'];
    die(json_encode($user->setUserToken($token, $user_id)));

}  elseif (isset($_REQUEST['get_group_users'])) {

    $group_id = $_REQUEST['group_id'];
    die(json_encode($user->getGroupUsers($group_id)));

}  elseif (isset($_REQUEST['upload_user_profile_picture'])) {

    $user_id = $_REQUEST['user_id'];
    die(json_encode($user->uploadProfilePicture($user_id)));

} elseif (isset($_REQUEST['test_image_compression'])) {

    $path = 'C:\xampp\htdocs\SkulKitApp\includes\UserImages\pic_20200221201558.jpg';
    $new_path = 'C:\xampp\htdocs\SkulKitApp\includes\UserImagesCompressed\pic_20200221201558.jpg';
    resizePicture($path, $new_path, $new_width = 400, $new_height = 400, $proportion = false);

}  elseif (isset($_REQUEST['upload_group_picture'])) {

    $group_id = $_REQUEST['group_id'];
    die(json_encode($user->uploadGroupPicture($group_id)));

}  elseif (isset($_REQUEST['get_all_users'])) {

    $not_in_group_id = @$_REQUEST['not_in_group_id'];
    $group_id = @$_REQUEST['group_id'];
    $level_id = @$_REQUEST['level_id'];

    $users_data = compact('not_in_group_id', 'level_id', 'group_id');
    die(json_encode($user->getUsers($users_data)));

} 








  