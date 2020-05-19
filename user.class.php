<?php


$user  = new User($conn);

class User {
    private $conn = null;

    const url = 'https://www.easypay.co.ug/api/';
    const username = '939da26087ba386f';
    const password = 'f9d7fc7564f19b6a';
    const action = 'mmdeposit';
    const amount = '500';
    const currency = 'UGX';
    const reference = 2;
    const reason = 'Testing MM DEPOSIT';

    function __construct ($conn)
    {
        $this->conn = $conn;
    }

    function getTransactions ($data)
    {
        extract($data);
        $where = "";
        if ($user_id) {
            $where .= " AND `user_id` = '{$user_id}' ";
        }
        if ($phone) {
            $where .= " AND `phone` LIKE '{$phone}' ";
        }
        return $this->conn->query("SELECT payment_transactions.*, 
            (SELECT first_name FROM users WHERE users.id = payment_transactions.user_id) AS username
            FROM payment_transactions WHERE 1=1 {$where}")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    function updateTransactionOnIPNResponse ($data) {

        try {
            $this->conn->exec("UPDATE payment_transactions 
                SET transaction_id = '{$transaction_id}', return_message = '{$reason}'
                WHERE reference_no = '{$reference_no}' ");

            return [
                'success' => true
            ];
        } catch (Exception $error) {
            return [
                'success' => false, 
                'msg' => $error->getMessage()
            ];
        }

    }
 
    function InitiatePayment ($details)
    {
        set_time_limit(0);
        extract($details);

        $settingsinfo = $this->getSettings();
        $action = SELF::action;


        // Save payment request
        $this->conn->exec("INSERT INTO payment_transactions 
            SET reference_no = NULL, phone = '{$phone_no}', `user_id` = '{$user_id}', debit_amount = '{$amount}',
            credit_amount = 0, balance = 0, reason = '{$settingsinfo->payment_message}', action = '{$action}',
            currency = '{$settingsinfo->payment_currency}', created_at = NOW(), payment_status = 'pending'");

        $reference_no = $this->conn->lastinsertId();

        $payload = array(
            'username' => $settingsinfo->payment_username, 
            'password' => $settingsinfo->payment_password, 
            'action' => $action, 
            'amount' => $amount, 
            'phone'=> $phone_no, 
            'currency'=> $settingsinfo->payment_currency, 
            'reference'=> $reference_no, 
            'reason'=> $settingsinfo->payment_message
        );

        // return $payload;
         
        //open connection 
        $ch = curl_init(); 
         
        //set the url, number of POST vars, POST data 
        curl_setopt($ch,CURLOPT_URL, SELF::url); 
        curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($payload)); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,15); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 400); //timeout in seconds 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        //execute post 
        $result = curl_exec($ch);

        //close connection 
        curl_close($ch); 


        try {
            $result2 = json_decode($result);
            if ($result2->success) {
                $this->conn->exec("UPDATE payment_transactions 
                    SET payment_status = 'success' 
                    WHERE reference_no = '{$reference_no}'");
            } else {
                $this->conn->exec("UPDATE payment_transactions 
                    SET payment_status = 'failed', return_message = '{$result2->errormsg}' 
                    WHERE reference_no = '{$reference_no}'");
            }
            return $result2;
        } catch (Exception $error) {
            $this->conn->exec("UPDATE payment_transactions 
                SET payment_status = 'failed' 
                WHERE reference_no = '{$reference_no}'");
            return [
                'success' => false, 
                'msg' => $error->getMessage()
            ];
        }
        return $result;
    }


    function saveSettings ($data)
    {
        extract($data);

        $policy = base64_encode($policy); 
        $about = base64_encode($about);
        // {email, phone, website, address, policy, about};
        $this->conn->exec("UPDATE settings SET email = '{$email}', phone = '{$phone}', 
            website = '{$website}', `address` = '{$address}', policy = '{$policy}', about = '{$about}'");

        return ['success' => true];
    }

    function savePaymentSettings ($data)
    {
        extract($data);
        
        $this->conn->exec("UPDATE settings SET payment_username = '{$payment_username}', payment_password = '{$payment_password}', 
            payment_currency = '{$payment_currency}', `payment_message` = '{$payment_message}'");

        return ['success' => true];
    }

    function saveCharge ($data)
    {
        extract($data);
        
        $this->conn->exec("UPDATE settings SET charge = '{$charge}'");

        return ['success' => true];
    }

    function getSettings ()
    {
        $data = $this->conn->query("SELECT * FROM settings;")->fetchObject();
        try {
            $data->policy = base64_decode($data->policy);
        } catch (Exception $e) {

        }
        try {
            $data->about = base64_decode($data->about); 
        } catch (Exception $e) {
            
        }
       
        
        return $data;
    }

    function isEmailInUse ($email = '', $user_id = 0)
    {
        if (!$email) {
            return 0;
        }
        return $this->conn
            ->query("SELECT COUNT(*) AS counts FROM users WHERE email = '{$email}' AND id != '{$user_id}'")
            ->fetchObject()
            ->counts;
    }

    function isPhoneInUse ($phone = '', $user_id = 0)
    {
        if (!$phone) {
            return 0;
        }
        return $this->conn
            ->query("SELECT COUNT(*) AS counts FROM users WHERE phone = '{$phone}' AND id != '{$user_id}'")
            ->fetchObject()
            ->counts;
    }

    public function signUpUser ($data)
    {
        extract($data);


        $is_email_in_use = $this->isEmailInUse($email, $user_id);
        $is_phone_in_use = $this->isPhoneInUse($phone, $user_id);

        if ($is_email_in_use) {
            return['success' => false, 'msg' => "Email is already in use"];
        }

        if ($is_phone_in_use) {
            return['success' => false, 'msg' => "Phone is already in use"];
        }

        try {

            if (!$user_id) {
                $stmt = $this->conn->prepare("INSERT INTO `users`(`first_name`, `last_name`, `phone`, `email`, `password`, `picture`, `level`, `token`) 
                    VALUES ('{$first_name}','{$last_name}','{$phone}','{$email}','{$password}','{$picture}','{$level}','{$token}')");
                $stmt->execute();
                $user_id = $this->conn->lastInsertId();
            } else {

                $set = "";


                $update_fields = [
                    "first_name", "last_name", "phone", "email",
                    "level", "password"
                ];

                $fields_to_set = "";
                foreach ($update_fields as $field) {
                    if (isset($$field) && $$field) {
                        $fields_to_set = ", $field = '{$$field}' "; 
                    }
                }
                $fields_to_set = trim($fields_to_set, ',');

                $stmt = $this->conn->exec("UPDATE `users` SET {$fields_to_set}
                    WHERE id = '{$user_id}'");
            }
            

            // Get user records from id
            $stmt2 = $this->conn->query("SELECT * FROM users WhERE id = {$user_id}");
            $user_details = $stmt2->fetchObject();
            $user_details->success = true;

            return $user_details;
        } catch (PDOException $e) {
            return array('success' => false, 
                'msg' => $e->getMessage());
        }
    }
    

    public function loginUser ($data)
    {
        extract($data);

        $email = $_POST['email'];
        $password = $_POST['password'];
    
        try {
    
            $stmt = $this->conn->query("SELECT * FROM users WHERE email = '{$email}'");
            $user_found = false;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $key => $user_data) {
                $user_found = true;
                // Check for password
                if ($user_data['password'] == $password) {
                    $user_data['success'] = true;
                    die(json_encode($user_data));
                }
            }
            if ($user_found) {
                die(json_encode(array('success' => false, 
                'msg' => "Invalid password")));
            } else {
                die(json_encode(array('success' => false, 
                'msg' => "User not found")));
            }
    
        } catch (PDOException $e) {
            die(json_encode(array('success' => false, 
                'msg' => $e->getMessage())));
        }
    
    }

    public function getLevels ()
    {
        $stmt = $this->conn->query("SELECT * FROM levels");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function saveLevel ($data)
    {
        extract($data);
        if ($level_id) {
            $stmt = $this->conn->query("UPDATE levels SET `name` = '{$level_name}' WHERE id = '{$level_id}'");
        } else {
            $stmt = $this->conn->query("INSERT INTO levels SET  `name` = '{$level_name}'");
        }
        return ['success' => true];
    }

    public function saveSubject ($data)
    {
        extract($data);

        if ($subject_id) {
            $stmt = $this->conn->query("UPDATE subjects SET `name` = '{$subject_name}', 
                `level_id` = '{$subject_level}' 
                WHERE id = '{$subject_id}'");
        } else {
            $stmt = $this->conn->query("INSERT INTO subjects SET `name` = '{$subject_name}', `level_id` = '{$subject_level}' ");
        }
        return ['success' => true];
    }

    public function resetSystem ($data)
    {
        extract($data);

        if ($user_id) {

            $stmt = $this->conn->query("DELETE FROM users WHERE id != 1 ");

            $systemTables = ['event_chats', 'event_users', 'groups', 'group_events', 'group_notes', 
                'group_questions', 'group_question_answers', 'group_question_answer_likes', 'user_groups' ];

            if ($resetType == 1) {
                $systemTables = array_merge($systemTables, ['subjects', 'subject_notes', 'subject_questions'] );
            }

            foreach ($systemTables as $systemTable) {
                $stmt = $this->conn->query("TRUNCATE TABLE `{$systemTable}`");
            }
            
        } else {
            return ['success' => false, 'msg' => 'Can not reset system'];
        }
        return ['success' => true];
    }

    public function getGroups ($data)
    {
        extract($data);
        $where = "";
        if (isset($subject_id) && $subject_id) {
            $where = " AND subject_id = '{$subject_id}' "; 
        }

        $stmt = $this->conn->query("SELECT groups.*,  
            (SELECT `name` FROM subjects WHERE subjects.id = groups.subject_id  ) AS sublect_name,
            (SELECT `name` FROM levels WHERE levels.id = groups.level_id  ) AS level_name,
            (SELECT COUNT(*) FROM group_events WHERE group_events.group_id = groups.id ) AS events_count,
            (SELECT COUNT(*) FROM user_groups WHERE user_groups.group_id = groups.id ) AS members_count,
            (SELECT `first_name` FROM users WHERE users.id = groups.created_by  ) AS first_name
        FROM groups 
        WHERE 1=1 {$where}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubjects ($level_id = 0)
    {
        $where = "";
        if ($level_id) {
            $where .= " AND level_id = '{$level_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT subjects.*,
                (SELECT COUNT(*) AS counts FROM subject_notes WHERE subject_notes.subject_id = subjects.id ) AS subject_notes_counts,
                (SELECT `name` FROM levels WHERE levels.id = subjects.level_id ) AS level_name,
                (SELECT COUNT(*) AS counts FROM subject_questions WHERE subject_questions.subject_id = subjects.id ) AS subject_questions_counts
                FROM subjects 
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }

    public function createGroup ($data)
    {
        extract($data);

        if (!$group_id) {
            $stmt = $this->conn->prepare("INSERT INTO `groups`(`name`, `subject_id`, `level_id`, `group_status`, `description`, `created_by`, `created_at`) 
                VALUES ('{$name}','{$subject_id}','{$level_id}','{$group_status}','{$description}','{$user_id}', NOW())");
            $stmt->execute();
            $group_id = $this->conn->lastInsertId();

            // Add user as admin in groups
            if ($group_id) {
                $this->conn->exec("INSERT INTO user_groups SET user_id = '{$user_id}', group_id = '{$group_id}', role = 'admin'");
            }
        } else {
            $this->conn->exec("UPDATE `groups` SET `name` = '{$name}', `subject_id` = '{$subject_id}', `level_id` = '{$level_id}', 
                `group_status` = '{$group_status}', `description` = '{$description}' WHERE id = {$group_id} ");
        }

        $upload_folder = "includes/group-images/";
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder);
            echo json_encode("Directory doesnot exist"); die;
        }
        $file_name = "picture";
        $random_file_name = mt_rand(0, 99999999);
        if (isset($_FILES[$file_name]) && isset($_FILES[$file_name]['name']) && $_FILES[$file_name]['name']) {
            $upload_status = uploadPicture($upload_folder, $file_name, $random_file_name);
            if ($upload_status['success']) {
                $this->conn->exec("UPDATE groups SET picture = '{$upload_status['target_file']}' WHERE id ='{$group_id}' ");
            }
        }
       
        

        return array(
            'success' => true,
            'id' => $group_id,
            'name' => $name,
            'subject_id' => $subject_id,
            'level_id' => $level_id,
            'group_status' => $group_status,
            'description' => $description,
            'created_by' => $user_id,
            'created_at' => date('Y-m-d'),
        );
    }

    public function updateGroupIcon ($data)
    {
        extract($data);

        $upload_folder = "includes/group-images/";
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder);
        }
        $file_name = "picture";
        $random_file_name = mt_rand(0, 99999999);
        $upload_status = uploadPicture($upload_folder, $file_name, $random_file_name);
        if ($upload_status['success']) {
            $this->conn->exec("UPDATE groups SET picture = '{$upload_status['target_file']}' WHERE id ='{$group_id}' ");
        }

        $picture = @$this->conn->query("SELECT picture 
            FROM groups 
            WHERE id ='{$group_id}' ")
            ->fetchObject()
            ->picture;
        

        return array(
            'success' => true,
            'picture' => $picture,
        );
    }

    function addUserToGroups ($data)
    {
        extract($data);
        $groups = explode(',', $groups);
        foreach ($groups as $group_id) {
            $group_id = trim($group_id);
            $this->conn->exec("DELETE FROM user_groups WHERE group_id = '{$group_id}' AND user_id = '{$user_id}'");
            $this->conn->exec("INSERT INTO `user_groups`(`user_id`, `group_id`, `role`) 
                VALUES ('{$user_id}','{$group_id}', 'member')");
        }
        return array('success' => true, 'msg' => "Update successfull");
    }

    public function getUserGroups ($user_id = 0)
    {
        $where = "";
        $user_parameters = "";
        if ($user_id) {
            $where .= " AND id IN (SELECT group_id FROM user_groups WHERE user_id = '{$user_id}') ";
            $user_parameters .= " ,(SELECT role FROM user_groups 
                WHERE user_groups.group_id = groups.id AND user_id = '{$user_id}' LIMIT 1) AS role, '{$user_id}' AS user_id ";
        }

        try {
            $stmt = $this->conn->query("SELECT groups.*,
                (SELECT groups.id) AS group_id,
                (SELECT COUNT(*) AS counts 
                    FROM group_events 
                    WHERE group_events.group_id = groups.id 
                    AND group_events.`end_date_time` < NOW() ) AS expired_events ,
                (SELECT COUNT(*) AS counts 
                    FROM group_events 
                    WHERE group_events.group_id = groups.id 
                    AND group_events.`start_date_time` <= NOW()
                    AND group_events.`end_date_time` >= NOW() ) AS ongoing_events ,
                (SELECT COUNT(*) AS counts 
                    FROM group_events 
                    WHERE group_events.group_id = groups.id 
                    AND group_events.`start_date_time` <= NOW()
                    AND group_events.`end_date_time` >= NOW() ) AS pending_events 
                {$user_parameters}
                FROM groups 
                WHERE 1=1 {$where} ORDER BY groups.name ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }

    public function getGroupEvents ($group_id = 0, $upcomming_events = 0, $ongoing_events = 0, $expired_events = 0)
    {
        $where = "";
        if ($group_id) {
            // echo "group_id";
            $where .= " AND group_events.`group_id` = '{$group_id}' ";
        }
        
        if ($upcomming_events) {
            // echo "upcomming_events";
            $where .= " AND group_events.`start_date_time` > NOW() ";
        }

        if ($ongoing_events) {
            // echo "ongoing_events";
            $where .= " AND group_events.`start_date_time` <= NOW() AND group_events.`end_date_time` > NOW() ";
        }

        if ($expired_events) {
            // echo "expired_events";
            $where .= " AND group_events.`end_date_time` < NOW() ";
        }

        try {

            $stmt = $this->conn->query("SELECT group_events.*, 
                groups.name AS group_name, users.first_name,
                (SELECT COUNT(*) FROM event_users WHERE event_users.event_id = group_events.id) AS no_of_discussants,
                (CASE
                    WHEN group_events.end_date_time < NOW()
                        THEN  'expired'
                    WHEN group_events.start_date_time > NOW()
                        THEN  'upcomming'
                    ELSE
                        'ongoing'
                END) AS groups_run_status
                FROM group_events 
                LEFT JOIN groups ON groups.id = group_events.group_id
                LEFT JOIN users ON users.id = group_events.created_by
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }

    public function createEvent ($data)
    {
        extract($data);
        try {
            if ($event_id) {
                $stmt = $this->conn->exec("UPDATE `group_events` SET `name`='{$name}', `description`='{$description}', 
                `group_id`='{$group_id}', `start_date_time` = '{$start_date_time}', 
                `end_date_time`='{$end_date_time}' WHERE id = '{$event_id}' ");
            } else {
                $stmt = $this->conn->prepare("INSERT INTO `group_events`(`name`, `description`, `group_id`, `create_date`, `start_date_time`, `end_date_time`, `created_by`) 
                    VALUES ('{$name}','{$description}','{$group_id}', NOW(),'{$start_date_time}','{$end_date_time}','{$user_id}')");
            }
            
            $stmt->execute();

            

            if ($event_id) {
                $insert_id = $event_id;
            } else {
                $insert_id = $this->conn->lastInsertId();
                if ($insert_id) {
                    $this->addEventDiscassant($insert_id, $user_id);
                }
                $event_id = $insert_id;
            }
            


            die(json_encode(array(
                'success' => true,
                'id' => $insert_id,
                'name' => $name,
                'description' => $description,
                'group_id' => $group_id,
                'create_date' => date('Y-m-d'),
                'start_date_time' => $start_date_time,
                'end_date_time' => $end_date_time,
                'user_id' => $user_id,
            )));

           

        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }

    public function getGroupQuestions ($group_id = 0)
    {
        $where = "";
        if ($group_id) {
            $where .= " AND group_questions.`group_id` = '{$group_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT group_questions.*, 
                groups.name AS group_name, users.first_name,
                (
                    SELECT COUNT(*) AS counts 
                    FROM group_question_answers
                    WHERE group_question_answers.question_id = group_questions.id
                ) AS answer_counts
                FROM group_questions 
                LEFT JOIN groups ON groups.id = group_questions.group_id
                LEFT JOIN users ON users.id = group_questions.created_by
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }

    
    public function getQuestionsAnswers ($question_id = 0)
    {
        $where = "";
        if ($question_id) {
            $where .= " AND group_question_answers.`question_id` = '{$question_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT group_question_answers.*, users.first_name,
                (
                    SELECT COUNT(*) AS counts 
                    FROM group_question_answer_likes
                    WHERE group_question_answer_likes.answer_id = group_question_answers.id
                ) AS like_counts
                FROM group_question_answers 
                LEFT JOIN users ON users.id = group_question_answers.created_by
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }


    
    public function createGroupQuestion ($data)
    {
        extract($data);
        try {
            $stmt = $this->conn->prepare("INSERT INTO `group_questions`( `question`, `group_id`, `created_by`) 
                VALUES ('{$question}','{$group_id}','{$created_by}')");

            $stmt->execute();

            $group_id = $this->conn->lastInsertId();
            die(json_encode(array(
                'success' => true,
                'question' => $question,
                'group_id' => $group_id,
                'create_date' => date('Y-m-d'),
                'user_id' => $created_by,
            )));
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }

    
    
    public function createAnswerOnQuestion ($data)
    {
        extract($data);
        try {
            $stmt = $this->conn->prepare("INSERT INTO `group_question_answers`(`answer`, `question_id`, `created_by`) 
            VALUES ('{$answer}','{$question_id}','{$created_by}')");

            $stmt->execute();

            $group_id = $this->conn->lastInsertId();
            die(json_encode(array(
                'success' => true,
                'id' => $group_id,
                'answer' => $answer,
                'question_id' => $question_id,
                'created_by' => $created_by,
                'create_date' => date('Y-m-d')
            )));
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
    }
        
    public function getNotesInGroup ($group_id = 0)
    {
        $where = "";
        if ($group_id) {
            $where .= " AND group_notes.`group_id` = '{$group_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT group_notes.* 
                FROM group_notes 
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
    }


    public function getQuestionsInSubject ($subject_id = 0)
    {
        $where = "";
        if ($subject_id) {
            $where .= " AND subject_questions.`subject_id` = '{$subject_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT subject_questions.*,
                (SELECT `name` FROM subjects WHERE subjects.id  = subject_questions.subject_id) AS subject_name
                FROM subject_questions 
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
    }
    
    public function getNotesInSubject ($subject_id = 0)
    {
        $where = "";
        if ($subject_id) {
            $where .= " AND  subject_notes.`subject_id` = '{$subject_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT subject_notes.*,
                (SELECT `name` FROM subjects WHERE subjects.id = subject_notes.subject_id ) AS subject_name,
                (SELECT `name` FROM levels WHERE levels.id = (SELECT level_id FROM subjects WHERE subjects.id = subject_notes.subject_id) ) AS level_name
                FROM subject_notes 
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
    }


    public function addNotesInGroup ($data)
    {
        extract($data);
        try {
            $stmt = $this->conn->prepare("INSERT INTO `group_notes`(`name`, `notes`, `group_id`, `created_by`, `description`) 
                VALUES ('{$name}', '{$notes}', '{$group_id}', '{$created_by}', '{$description}')");
            
            $stmt->execute();
            $id = $this->conn->lastInsertId();

            $file_name = "file";
            $upload_folder = "includes/group-notes/";
            $random_file_name = mt_rand(0, 99999999);
            if (isset($_FILES[$file_name]) && isset($_FILES[$file_name]['name']) && $_FILES[$file_name]['name']) {
                $upload_status = uploadFile($upload_folder, $file_name, $random_file_name);
                if ($upload_status['success']) {
                    $this->conn->exec("UPDATE group_notes 
                        SET notes = '{$upload_status['target_file']}' 
                        WHERE id ='{$id}' ");
                }
            }

            die(json_encode(array(
                'success' => true,
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'notes' => $notes,
                'group_id' => $group_id,
                'created_by' => $created_by,
                'create_date' => date('Y-m-d')
            )));
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
    }


    function deleteGroup ($data)
    {
        extract($data);

        $this->conn->exec("DELETE FROM groups WHERE id = '{$group_id}'");
        $this->conn->exec("DELETE FROM user_groups WHERE group_id = '{$group_id}'");
        $this->conn->exec("DELETE FROM group_notes WHERE group_id = '{$group_id}'");
        $this->conn->exec("DELETE FROM group_events WHERE group_id = '{$group_id}'");
        return array('success' => true);

    }

    function deleteEvent ($data)
    {
        extract($data);

        $this->conn->exec("DELETE FROM group_events WHERE id = '{$event_id}'");
        $this->conn->exec("DELETE FROM event_users WHERE event_id = '{$event_id}'");
        $this->conn->exec("DELETE FROM event_chats WHERE event_id = '{$event_id}'");
        return array('success' => true);

    }

    function deleteQuestionsInSubject ($id)
    {
        $this->conn->exec("DELETE FROM subject_questions WHERE id = '{$id}'");
        return ['success' => true];
    }

    function deleteNotes ($notes_id)
    {
        $this->conn->exec("DELETE FROM subject_notes WHERE id = '{$notes_id}'");
        return array('success' => true);
    }


    function deletelevel ($level_id)
    {
        $this->conn->exec("DELETE FROM levels WHERE id = '{$level_id}'");
        return array('success' => true);
    }

    function addlevel ($level_id, $level_name)
    {
        if ($level_id) {
            $stmt = $this->conn->exec("UPDATE `levels` SET `name` = '{$level_name}'
                WHERE id = '{$level_id}'  ");
            $id = $level_id;
        } else {
            $stmt = $this->conn->prepare("INSERT INTO `levels`(`name`) 
                VALUES ('{$level_name}')");
            $stmt->execute();
            $id = $this->conn->lastInsertId();
        }
        return ['success' => true];
    }

    public function addQuestionsInSubject ($data)
    {
        extract($data);

        if ($question_id) {
            $stmt = $this->conn->exec("UPDATE `subject_questions` SET `question` = '{$question_title}', 
                `subject_id` = '{$question_subject}' WHERE id = '{$question_id}' ");
            $id = $question_id;
        } else {
            $stmt = $this->conn->prepare("INSERT INTO `subject_questions`(`question`, `subject_id`, `question_file`, `created_by`) 
                VALUES ('{$question_title}', '{$question_subject}', '', '1' )");
            $stmt->execute();
            $id = $this->conn->lastInsertId();
        }

        $file_name = "question-file";
        $upload_folder = "includes/subject-questions/";
        $random_file_name = mt_rand(0, 99999999);
        if (isset($_FILES[$file_name]) && isset($_FILES[$file_name]['name']) && $_FILES[$file_name]['name']) {
            $upload_status = uploadFile($upload_folder, $file_name, $random_file_name);
            if ($upload_status['success']) {
                $this->conn->exec("UPDATE subject_questions 
                    SET question_file = '{$upload_status['target_file']}' 
                    WHERE id ='{$id}' ");
            }
        }

        die(json_encode(array(
            'success' => true,
            'id' => $id,
            'question_file' => $question_title,
            'subject_id' => $question_subject,
            'created_by' => 1,
            'create_date' => date('Y-m-d')
        )));

    }

    public function addNotesInSubject ($data)
    {
        extract($data);
        try {

            if ($notes_id) {
                $stmt = $this->conn->exec("UPDATE `subject_notes` SET `name` = '{$title}', 
                    `subject_id` = '{$subject_id}' WHERE id = '{$notes_id}'");
                $id = $notes_id;
            } else {
                $stmt = $this->conn->prepare("INSERT INTO `subject_notes`(`name`, `notes`, `subject_id`, `created_by`, `created_at`) 
                    VALUES ('{$title}', '', '{$subject_id}', '1', CURDATE() )");
                $stmt->execute();
                $id = $this->conn->lastInsertId();
            }
            

            $file_name = "file";
            $upload_folder = "includes/subject-notes/";
            $random_file_name = mt_rand(0, 99999999);
            if (isset($_FILES[$file_name]) && isset($_FILES[$file_name]['name']) && $_FILES[$file_name]['name']) {
                $upload_status = uploadFile($upload_folder, $file_name, $random_file_name);
                if ($upload_status['success']) {
                    $this->conn->exec("UPDATE subject_notes 
                        SET notes = '{$upload_status['target_file']}' 
                        WHERE id ='{$id}' ");
                }
            }

            die(json_encode(array(
                'success' => true,
                'id' => $id,
                'name' => $title,
                'subject_id' => $subject_id,
                'created_by' => 1,
                'create_date' => date('Y-m-d')
            )));
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
    }

    public function getUserPermsInEvent ($event_id, $user_id)
    {
        $where = "";
        if ($event_id) {
            $where .= " AND  event_users.`user_id` = '{$user_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT * 
                FROM event_users 
                WHERE 1=1 {$where} ");
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($results)) {
                    return [
                        'admin' => 1
                    ];
                } else {
                    return [
                        'admin' => 0
                    ];
                }
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
    }

    public function getGetChats ($data)
    {
        extract($data);

        // Check for user permissions in events
        $perm_status = $this->getUserPermsInEvent($event_id, $user_id);

        $where = "";
        if ($event_id) {
            $where .= " AND event_chats.`event_id` = '{$event_id}' ";
        }

        if ($from_id) {
            $where .= " AND event_chats.`id` > '{$from_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT event_chats.* 
                FROM event_chats 
                WHERE 1=1 {$where} ");
            $event_chats =  $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($event_chats as $key => $event_chat) {
                // echo "SELECT * 
                // FROM users 
                // WHERE users.id = '{$users['user_id']}' "; die;
                $event_chats[$key]['userDetails'] = $this->conn->query("SELECT * 
                    FROM users 
                    WHERE users.id = '{$event_chat['user_id']}' ")->fetchObject();
            }

        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
        return [
            'perms' => $perm_status,
            'chats' => $event_chats
        ];
    }

    public function addChatInEvent ($data)
    {
        extract($data);
        $stmt = $this->conn->prepare("INSERT INTO `event_chats`(`event_id`, `user_id`, `message`, `resp_id`) 
            VALUES ('{$event_id}','{$user_id}','{$message}', '{$resp_id}')");
        $stmt->execute();
        $insert_id = $this->conn->lastInsertId();
        die(json_encode(array(
            'success' => true,
            'id' => $insert_id,
            'event_id' => $event_id,
            'user_id' => $user_id,
            'message' => $message,
            'resp_id' => $resp_id,
            'created_at' => date('Y-m-d'),
        )));
    }

 

    public function getEventDiscassants ($event_id)
    {
        $where = "";
        if ($event_id) {
            $where .= " AND `event_id` = '{$event_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT * 
                FROM event_users 
            WHERE 1=1 {$where} ");

            $results = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $key => $event_user) {

                $stmt2 = $this->conn->query("SELECT * FROM users WHERE id = {$event_user['user_id']}");                
                $event_user['user_details'] = $stmt2->fetchObject();
                $event_user['user_details'] = !($event_user['user_details']) ? [] : $event_user['user_details'];
                $results[] = $event_user;
            }

        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        return $results;
    }

    public function exitGroup ($group_id, $user_id)
    {
        
        try {
            $this->conn->exec("DELETE FROM `user_groups` WHERE user_id = '{$user_id}' AND group_id = '{$group_id}'");
            return array(
                'success' => true
            );
        } catch (PDOException $e) {
            return array(
                'success' => false
            );
        }
    }


    
    public function updateUserPermStatusInGroup ($group_id, $user_id, $role)
    {
        
        try {
            $this->conn->exec("DELETE FROM `user_groups` WHERE user_id = '{$user_id}' AND group_id = '{$group_id}'");
            $this->conn->exec("INSERT INTO `user_groups` SET `user_id` = '{$user_id}', `group_id` = '{$group_id}', `role` = '{$role}'");
            return array(
                'success' => true
            );
        } catch (PDOException $e) {
            return array(
                'success' => false
            );
        }
    }

    public function setUserToken ($token, $user_id)
    {
        
        try {
            $this->conn->exec("UPDATE `users` SET `token` = '{$token}' WHERE id = '{$user_id}'");
            return array(
                'success' => true
            );
        } catch (PDOException $e) {
            return array(
                'success' => false
            );
        }
    }



    public function getGroupUsers ($group_id = 0)
    {
        $where = "";
        if ($group_id) {
            $where .= " AND user_groups.`group_id` = '{$group_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT user_groups.*, users.first_name, users.last_name
                FROM user_groups 
                JOIN users ON users.id = user_groups.user_id
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }
    
    public function addEventDiscassant($event_id, $user_id){

        $this->conn->exec("DELETE FROM event_users WHERE event_id = '{$event_id}' AND user_id = '{$user_id}'");
        $this->conn->exec("INSERT INTO `event_users`(`event_id`, `user_id`) VALUES ('{$event_id}','{$user_id}' )");
        return array('success' => true, 'msg' => "Dicussant Added successfull");

    }

    public function deleteEventDiscassant($event_id, $user_id){

        $this->conn->exec("DELETE FROM event_users WHERE event_id = '{$event_id}' AND user_id = '{$user_id}'");
        // $this->conn->exec("INSERT INTO `event_users`(`event_id`, `user_id`) VALUES ('{$event_id}','{$user_id}' )");
        return array('success' => true, 'msg' => "Dicussant Added successfull");

    }

    public function uploadProfilePicture ($user_id)
    {
        //http://www.cresteddevelopers.com/AppFiles/SkulKitApp/manage-users.php?upload_user_profile_picture


        $upload_folder = "includes/UserImages/";
        // $upload_folder = "includes/group-images/";
        $file_name = "picture";
        $random_file_name = mt_rand(0, 99999999);
        $upload_status = uploadPicture($upload_folder, $file_name, $random_file_name);
        if ($upload_status['success']) {
            $this->conn->exec("UPDATE users SET picture = '{$upload_status['target_file']}' WHERE id ='{$user_id}' ");
        }
        return $upload_status;

    }

    public function uploadGroupPicture ($group_id)
    {
        
        $upload_folder = "includes/group-images/";
        $file_name = "picture";
        $random_file_name = mt_rand(0, 99999999);
        $upload_status = uploadPicture($upload_folder, $file_name, $random_file_name);
        if ($upload_status['success']) {
            $this->conn->exec("UPDATE groups SET picture = '{$upload_status['target_file']}' WHERE id ='{$group_id}' ");
        }
        return $upload_status;
    }


    public function getUsers ($data)
    {
        extract($data);

        $where = "";
        if ($group_id) {
            $where .= " AND users.id IN (SELECT `user_id` FROM user_groups WHERE user_groups.group_id = '{$group_id}') ";
        }

        if ($not_in_group_id) {
            $where .= " AND users.id NOT IN (SELECT `user_id` FROM user_groups WHERE user_groups.group_id != '{$group_id}') ";
        }

        if ($level_id) {
            $where .= " AND `level` = '{$level_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT users.*,
                (SELECT `name` FROM levels WHERE levels.id = users.level ) AS level_name
                FROM users 
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }

}


function uploadPicture ($target_dir, $file_name, $unique_name ) {

    $imageFileType = strtolower(pathinfo($_FILES[$file_name]["name"], PATHINFO_EXTENSION));

    $target_file = $target_dir . $unique_name . ".$imageFileType";
    $uploadOk = 1;
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES[$file_name]["tmp_name"]);
        if($check !== false) {
        } else {
            return ['success' => false, 'msg' => "File is not an image."];
        }
    }

    // Check file size
    if ($_FILES[$file_name]["size"] > 500000) {
        return ['success' => false, 'msg' => "Sorry, your file is too large."];
    }
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
        return ['success' => false, 'msg' => "Sorry, only JPG, JPEG, PNG & GIF files are allowed."];
    }
    if ($uploadOk == 0) {
        return ['success' => false, 'msg' => "Sorry, your file was not uploaded."];
        
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES[$file_name]["tmp_name"], $target_file)) {
            return ['success' => true, 'target_file' => $unique_name . ".$imageFileType"];
        } else {
            return ['success' => false, 'msg' => "Sorry, there was an error uploading your file."];
        }
    }
}

function uploadFile ($target_dir, $file_name, $unique_name ) {

    $imageFileType = strtolower(pathinfo($_FILES[$file_name]["name"], PATHINFO_EXTENSION));

    $target_file = $target_dir . $unique_name . ".$imageFileType";
    $uploadOk = 1;

    // Check file size
    if ($_FILES[$file_name]["size"] > 500000) {
        return ['success' => false, 'msg' => "Sorry, your file is too large."];
    }
    // Allow certain file formats
    // if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    //     && $imageFileType != "gif" ) {
    //     return ['success' => false, 'msg' => "Sorry, only JPG, JPEG, PNG & GIF files are allowed."];
    // }
    
    if ($uploadOk == 0) {
        return ['success' => false, 'msg' => "Sorry, your file was not uploaded."];
        
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES[$file_name]["tmp_name"], $target_file)) {
            return ['success' => true, 'target_file' => $unique_name . ".$imageFileType"];
        } else {
            return ['success' => false, 'msg' => "Sorry, there was an error uploading your file."];
        }
    }
}

function resizePicture($path, $new_path, $new_width, $new_height, $proportion = false) {
    $size = getimagesize($path);
    $x = 0;
    $y = 0;

    switch($size['mime']) {
        case 'image/jpeg':
            $picture = imagecreatefromjpeg($path);
        break;
        case 'image/png':
            $picture = imagecreatefrompng($path);
        break;
        case 'image/gif':
            $picture = imagecreatefromgif($path);
        break;
        default:
            return false;
        break;
    }

    $width = $size[0];
    $height = $size[1];

    $frame = imagecreatetruecolor($new_width, $new_height);
    if($size['mime'] == 'image/jpeg') {
        $bg = imagecolorallocate($frame, 255, 255, 255);
        imagefill($frame, 0, 0, $bg);
    } else if($size['mime'] == 'image/gif' or $size['mime'] == 'image/png') {
        imagealphablending($picture, false);
        imagesavealpha($picture, true);
        imagealphablending($frame, false);
        imagesavealpha($frame, true);
    }

    if($width < $new_width and $height < $new_height) {
        $x = ($new_width - $width) / 2;
        $y = ($new_height - $height) / 2;
        imagecopy($frame, $picture, $x, $y, 0, 0, $width, $height);
    } else {
        if($proportion and $width != $height) {
            if($width > $height) {
                $old_height = $new_height;
                $new_height = $height * $new_width / $width;
                $y = abs($old_height - $new_height) / 2;
            } else {
                $old_width = $new_width;
                $new_width = $width * $new_height / $height;
                $x = abs($old_width - $new_width) / 2;
            }
        }
        imagecopyresampled($frame, $picture, $x, $y, 0, 0, $new_width, $new_height, $width, $height);
    }

    switch($size['mime']) {
        case 'image/jpeg':
            imagejpeg($frame, $new_path, 85);
        break;
        case 'image/png':
            imagepng($frame, $new_path, 8);
        break;
        case 'image/gif':
            imagegif($frame, $new_path);
        break;
        default:
            return false;
        break;
    }
}

//C:\xampp\htdocs\SkulKitApp\includes\user.class.php
