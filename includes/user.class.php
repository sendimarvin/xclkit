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


    public static function signUpUser ($data)
    {
        extract($data);

        $is_email_in_use = $this->isEmailInUse($email);

        try {
            $stmt = $this->conn->prepare("INSERT INTO `users`(`first_name`, `last_name`, `phone`, `email`, `password`, `picture`, `level`, `token`) 
            VALUES ('{$first_name}','{$last_name}','{$phone}','{$email}','{$password}','{$picture}','{$level}','{$token}')");

            $stmt->execute();
            $user_id = $conn->lastInsertId();
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
    

    public static function loginUser ($data)
    {
        extract($data);

        $email = $_POST['email'];
        $password = $_POST['password'];
    
        try {
    
            $stmt = $this->conn->query("SELECT * FROM user WHERE email = '{$email}'");
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
        $stmt = $this->conn->query("SELECT *
        FROM subjects WHERE 1=1 {$where} ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

}