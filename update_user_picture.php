<?php
        header('Content-Type: application/json');
        date_default_timezone_set('Africa/Kampala');
        
        require "connection.php";

        $picture = "";
        $status = 1;
        
        $user_id = $_POST['user_id'];

        if (@$_FILES["picture"]["size"]>0){

            $temp_name = "pic_".date('YmdHis').'.'.
                strtolower(pathinfo(basename($_FILES["picture"]["name"]),PATHINFO_EXTENSION));

            $target_file = "UserImages/". $temp_name;

            if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)){
                $picture = $temp_name;
                $status = 1;
            }else{
                $status = 0;
            }
        }else{
            $status = 0;
        }

			
        $obj = new stdClass();
        if ($status==1) {
            //save in db;

            $sql = "UPDATE users SET picture = '$picture' WHERE id = '$user_id'";
          

			if(mysqli_query($conn,$sql) ){
	        	$obj->code = 1;
	        	$obj->message = "Successful";
			}else{
	        	$obj->code = 0;
	        	$obj->message = "Could not Save Data".mysqli_error($conn);
                $obj->error = mysqli_error($conn);
			}
        }else{
        	//show error
        	$obj->code = 0;
        	$obj->message = "Image Error Upload Error";
        }

        echo json_encode($obj);


?>
