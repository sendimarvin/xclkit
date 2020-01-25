<?php


$user  = new User($conn);

class User {
    private $conn = null;

    function __contruct ($conn)
    {
        $this->conn = $conn;
    }

    function isEmailInUse ($email = '', $user_id = 0)
    {
        return $this->conn
    }


    public static function signUpUser ($data)
    {
        extract($data);

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
}