<?php


$user  = new User($conn);

class User {
    private $conn = null;

    function __construct ($conn)
    {
        $this->conn = $conn;
    }

    function isEmailInUse ($email = '', $user_id = 0)
    {
        return $this->conn
            ->query("SELECT COUNT(*) AS counts FROM users WHERE email = '{$email}' AND id != '{$user_id}'")
            ->fetchObject()
            ->counts;
    }


    public function signUpUser ($data)
    {
        extract($data);

        $is_email_in_use = $this->isEmailInUse($email);

        try {
            $stmt = $this->conn->prepare("INSERT INTO `users`(`first_name`, `last_name`, `phone`, `email`, `password`, `picture`, `level`, `token`) 
            VALUES ('{$first_name}','{$last_name}','{$phone}','{$email}','{$password}','{$picture}','{$level}','{$token}')");

            $stmt->execute();
            $user_id = $this->conn->lastInsertId();
            return array('success' => true, 
                'user_id' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
                'email' => $email,
                'password' => $password,
                'picture' => $picture,
                'token' => $token,
            );
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
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $key => $value) {
                $user_found = true;
                // Check for password
                if ($value['password'] == $password) {
                    die(json_encode(array(
                        'success' => true, 
                        'user_id' => $value['id'],
                        'first_name' => $value['password'],
                        'last_name' => $value['first_name'],
                        'phone' => $value['phone'],
                        'email' => $value['email'],
                        'password' => $value['password'],
                        'picture' => $value['picture'],
                        'token' => $value['token'],
                    )));
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

    public function getGroups ()
    {
        $stmt = $this->conn->query("SELECT groups.*,  
        (SELECT `name` FROM subjects WHERE subjects.id = groups.subject_id  ) AS sublect_name,
        (SELECT `name` FROM levels WHERE levels.id = groups.level_id  ) AS level_name,
        (SELECT `first_name` FROM users WHERE users.id = groups.created_by  ) AS first_name
        FROM groups");
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
        $stmt = $this->conn->prepare("INSERT INTO `groups`(`name`, `subject_id`, `level_id`, `group_status`, `description`, `created_by`, `created_at`) 
            VALUES ('{$name}','{$subject_id}','{$level_id}','{$group_status}','{$description}','{$created_by}', NOW())");
        $stmt->execute();
        $group_id = $this->conn->lastInsertId();
        die(json_encode(array(
            'success' => true,
            'name' => $name,
            'subject_id' => $subject_id,
            'level_id' => $level_id,
            'group_status' => $group_status,
            'description' => $description,
            'created_by' => $created_by,
            'created_at' => date('Y-m-d'),
        )));
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
        if ($user_id) {
            $where .= " AND user_groups.`user_id` = '{$user_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT user_groups.*, groups.name
                FROM user_groups 
                JOIN groups ON groups.id = user_groups.group_id
                WHERE 1=1 {$where} ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
    }

    public function getGroupEvents ($group_id = 0, $upcomming_events = 0)
    {
        $where = "";
        if ($group_id) {
            $where .= " AND group_events.`group_id` = '{$group_id}' ";
        }
        if ($upcomming_events) {
            $where .= " AND group_events.`start_date_time` > NOW() ";
        }

        try {
            $stmt = $this->conn->query("SELECT group_events.*, 
                groups.name AS group_name, users.first_name
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
            $stmt = $this->conn->prepare("INSERT INTO `group_events`(`name`, `description`, `group_id`, `create_date`, `start_date_time`, `end_date_time`, `created_by`) 
                VALUES ('{$name}','{$description}','{$group_id}', NOW(),'{$start_date_time}','{$end_date_time}','{$user_id}')");

            $stmt->execute();

            $group_id = $this->conn->lastInsertId();
            die(json_encode(array(
                'success' => true,
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
    
    public function getNotesInSubject ($subject_id = 0)
    {
        $where = "";
        if ($subject_id) {
            $where .= " AND  subject_questions.`subject_id` = '{$subject_id}' ";
        }

        try {
            $stmt = $this->conn->query("SELECT subject_questions.* 
                FROM subject_questions 
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
            $stmt = $this->conn->prepare("INSERT INTO `group_notes`(`name`, `notes`, `group_id`, `created_by`) 
                VALUES ('{$name}', '{$notes}', '{$group_id}', '{$created_by}')");
            

            $stmt->execute();
            $id = $this->conn->lastInsertId();
            die(json_encode(array(
                'success' => true,
                'id' => $id,
                'name' => $name,
                'notes' => $notes,
                'group_id' => $group_id,
                'created_by' => $created_by,
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

    public function getGetCharts ($data)
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
            $event_charts =  $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array('success' => false, 'msg' => $e->getMessage());
        }
        
        return [
            'charts'
        ];
    }

 


    
}