
<?php
include("functions/db.php");
include("functions/init.php");
require("PHPMailer\src\PHPMailer.php");
require("PHPMailer\src\SMTP.php");
require("PHPMailer\src\Exception.php");

/***************************HELPER FUNCTIONS *******************/
function clean($string)
{
return htmlentities($string);
}

function redirect($location)
{
	 return header("LOCATION:{$location}");
}

function set_message($mesage){
	if(!empty($mesage))
	{      
		$_SESSION['message']=$mesage;
	} else
	{
		$mesage="";
	}
}

function display_message()
{
	if(isset($_SESSION['message']))
	{
		echo $_SESSION['message'];
		unset($_SESSION['message']);
	}
}

function token_generator(){
	$token=$_SESSION['token']=md5(uniqid(mt_rand(),true));
	return $token;
}	

function validation_error_disp_msg($error_messages){
	$error_messages=<<<DELIMETER
	           <div class="alert alert-warning alert-dismissible" role="alert">
  	            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  	            <strong>Warning!</strong> $error_messages
	            </div> 
DELIMETER;
return $error_messages;
}

function email_exist($email)
{
	$sql="SELECT id FROM user WHERE email='$email'";
	$result= query($sql);
	if(row_count($result)==1)
	{
	  return true;
	}
	else
	{
	  return false;
	}
}

function user_exist($username)
{
	$sql="SELECT id FROM user WHERE username='$username'";
	$result= query($sql);
	if(row_count($result)==1)
	{
		return true;
	}
	else
	{
	   return false;
	}
}
 /*function send_email($email,$subject,$msg,$header)
 {
  return mail($email,$subject,$msg,$header);
 }*/
function send_email($email,$subject,$msg,$header)
{
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->IsSMTP(); // enable SMTP

    //$mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
    $mail->SMTPAuth = true; // authentication enabled
    $mail->SMTPSecure = 'tls'; // secure transfer enabled REQUIRED for Gmail
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587; // or 587
    $mail->IsHTML(true);
    $mail->Username = "phpdmntest@gmail.com";
    $mail->Password = "Myphpadmin";
    $mail->SetFrom("phpdmntest@gmail.com");
    $mail->Subject = $subject;
    $mail->Body = $msg;
    $mail->AddAddress("biprock.999@gmail.com");

     if(!$mail->Send()) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
     } else {
        echo "Message has been sent";
        return true;
     }
}

/***************************FORM VALIDATION*******************/
function validate_user_registration()
{
    	$min=3;
		$max=8;
   	    $errors=[];
   	    
	if($_SERVER['REQUEST_METHOD']=="POST")
	{

		$first_name=clean($_POST['first_name']);
		$last_name=clean($_POST['last_name']);
		$username=clean($_POST['username']);
		$email=clean($_POST['email']);
		$password=clean($_POST['password']);	
		$confirm_password=clean($_POST['confirm_password']);

		if((strlen($first_name) < $min))
		{
        $errors[]="The Input First Name should not be less than {$min} characters";
		}

		if((strlen($first_name) > $max))
		{
        $errors[]="The Input First Name should not be greater than {$max} characters";
		}

		if((strlen($last_name) < $min))
		{
        $errors[]="The Input Last Name should not be not be less than {$min} characters";
		}

		if((strlen($last_name) > $max))
		{
        $errors[]="The Input Last Name should not be greater than {$max} characters";
		}

		if((strlen($username) < $min))
		{
        $errors[]="The Input Username should be of min {$min} characters";
		}

		if(user_exist($username))
		{
        $errors[]="The Input Username '$username' is been taken,please use differnt Username";
		}

		if(email_exist($email))
		{
		$errors[]="The Input email address '$email' has already been registered.";
		}

		if((strlen($email) < $min))
		{
        $errors[]="The Email should not be less than {$min} characters";
		}

		if($password!==$confirm_password)
		{ 
		$errors[]="The Input Password and Confirm Password doesn't match";
		}

		if(!empty($errors))
    	{
         foreach ($errors as $error)
          {
            echo validation_error_disp_msg($error);
		  }
		}
		else
		{
			if(register_user($username,$first_name,$last_name,$email,$password))
			{
				display_message("<p class= bg-sucess text-center> Please check your Email or Spam folder for Activation link</p>");
				redirect("index.php");
			}
			else{
				display_message("<p class= bg-danger text-center> Sorry the user could not be registered </p>");
				redirect("index.php");
			}
		}
    }/********POST METHOD END*******/

}/**********VALIDATE USER REGISTRATION FUNTION END************************/


/***************************REGISTERING NEW USER & Inserting Data into db********************************/
function register_user($username,$first_name,$last_name,$email,$password)
{
    	$first_name=escape($first_name);
		$last_name=escape($last_name);
		$username=escape($username);
		$email=escape($email);
		$password=escape($password);

	  if(email_exist($email)){
	  	return false;
	  }

	  elseif(user_exist($username)) {
	  	return false;
	  }

	  else
	  {
	  	$password=md5($password);
	  	$validation_code=md5($username.microtime());
	  	$sql="INSERT INTO user(first_name,last_name,email,password,username,validation_code,active)";
        $sql.=" VALUES('$first_name','$last_name','$email','$password','$username','$validation_code',0)";
        $results=query($sql);
        confirm($results);
        $subject="Activate account";
        $msg="Plase click the below link to activate your account
        http://localhost/login/activate.php?email=$email&code=$validation_code 
        ";
        $header="From: noreply@yourwebsite";

        send_email($email,$subject,$msg,$hader);{

        }
        return true;
	  }
} //function

/****************************************ACTIVATING USER fUNCTIONS******************************************/

function activate_user()
{
	if($_SERVER['REQUEST_METHOD']=="GET")
	{
		if(isset($_GET['email'])){
			$email=clean($_GET['email']);
			$validation_code=clean($_GET['code']);
            $sql="SELECT id FROM user WHERE email='".escape($_GET['email'])."' AND validation_code='".escape($_GET['code'])."'";
            $result=query($sql);
            confirm($result);
            if(row_count($result)==1)
            {
            $sql2="UPDATE user SET active=1, validation_code=0 WHERE email='".escape($_GET['email'])."' AND validation_code='".escape($_GET['code'])."'";
            $result2=query($sql2);
            confirm($result2);
            display_message("<p class=bg-sucess>Your Account has been activated Sucessfully.Please Login!</p>");
            redirect("login.php");
            }
		}
	}
}//Function


/*****************************VALIDATING USER LOGIN**********************************************/
function validate_user_login()
{

    	$min=3;
   	    $errors=[];  
	if($_SERVER['REQUEST_METHOD']=="POST")
	{
		$email=clean($_POST['email']);
		$password=clean($_POST['password']);
		$remember=isset($_POST['remember']);

		if((strlen($email) < $min))
		{
         $errors[]="The Email should not be less than {$min} characters";
		}
		if(empty($email))
		{
         $errors[]="The Email field can not be empty";
		}
		if(empty($password))
		{
         $errors[]="The Pasword field can not be empty";
		}

       if(!empty($errors))
    	{
         foreach ($errors as $error)
          {
            echo validation_error_disp_msg($error);
		  }
		}
		else
		{
          if(login_user($email,$password,$remember)){
          	redirect("admin.php");
          }
          else{
          	echo validation_error_disp_msg("Invalid Login Credentials");
          }
		}
    }
}//function

/**************************************USER LOGIN FUNCTION*************************************************/

function login_user($email,$password,$remember)
{
	$sql="SELECT password, id FROM user WHERE email='".escape($email)."'AND active=1";
	$results=query($sql);
	    confirm($results);
	if(row_count($results)==1)
	{
        $row=fetch_array($results);
        $db_password=$row['password'];  
		if(md5($password)===$db_password)
		{
			if($remember=="on")
			{
				setcookie('email',$email,time() +60); /*This cookie will expire in 60secs we can provide or own time, if we give only time() it doesn't have any expiry time*/
			}
			$_SESSION['email']=$email;
         	return true;
			
		}
		else{
			return false;
		}
		return true;
	}
	else{
		return false;
	}
}//function end

/**************************************LOGGED-IN FUNCTION*************************************************/

function logged_in()
{
	if(isset($_SESSION['email']) || isset($_COOKIE['email']))
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**************************************RECOVER PASSWORD*************************************************/

function recover_password()
{
	if($_SERVER['REQUEST_METHOD']=="POST")
	{
       if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token'])
       {
		
		$email= clean($_POST['email']);
		if(email_exist($email))
		{
			$validation_code = md5($email.microtime());
			setcookie('temp_access_code', $validation_code, time()+ 900);
			$sql="UPDATE user SET validation_code='".escape($validation_code)."' WHERE email='".escape($email)."'";
			$result=query($sql);
			confirm($result);
			$subject="Please reset your Password";
			$message="Here is your reset code {$validation_code}
                      click here to reset your password http://localhost/login/code.php?email=$email&code=$validation_code ";
            $header="From:bipin.singh4115@gmail.com";
            if(!send_email($email, $subject, $message, $header))
            {
            	echo validation_error_disp_msg("This email canot be sent!");
            }
            	else
            	{
            	set_message("<p class='bg-sucess text-center'>Please check your email for passowrd reset link sent.</p>");
            	redirect("index.php");
            	}
			}
		else
		{
			echo validation_error_disp_msg("This email id does not exist!");
		}
	   }//t0ken check
	   else
	   {
	   	redirect("index.php");
	   }
	}//post request
}//function


/**************************************VALIATE POST METHOD FOR RECOVERY PASSWORD*****************************************/

function validate_code()
{
	if(isset($_COOKIE['temp_access_code']))
	{
		if($_SERVER['REQUEST_METHOD']=="GET")
		{
			if(!isset($_GET['email']) && !isset($_GET['code']))
			{
     		redirect("index.php");
			}
			elseif (empty($_GET['email']) || empty($_GET['code'])) 
			{
				redirect("index.php");
			}
			else
			{
				if(isset($_POST['code']));
				{
				//echo "geeting post from form";
					$email= clean($_GET['email']);
					$validation_code= clean($_GET['code']);

					$sql="SELECT id FROM user WHERE validation_code='".escape($validation_code)."' AND email='".escape($email)."'";
					$result=query($sql);
					if(row_count($result)==1){
					  setcookie('temp_access_code', $validation_code, time()+ 300);
                      redirect("reset.php?email=$email&code=$validation_code");
					}
					else{
						echo validation_error_disp_msg("Sorry Wrong Validation Code!");
					}

				}
			}
		}
	}
	else
	{
	set_message("<p class='bg-danger text-center'>Sorry your validation cookie expired</p>");
	redirect("recover.php");
	}
}//function code_validate


/************************************** PASSWORD RESET FUNCTION *************************************************/

function password_reset()
{  
		if(isset($_COOKIE['temp_access_code']))
		{
			if(isset($_GET['email']) && isset($_GET['code']))
			{
			  if(isset($_SESSION['token']) && isset($_POST['token']))
			  {
			  		if($_POST['token']=== $_SESSION['token'])
                    {
                    	if($_POST['password'] === $_POST['confirm_password'])
                    	{
                    	  $updated_password = md5($_POST['password']);
                    	  echo  $updated_password;
                          $sql="UPDATE user SET password ='".escape($updated_password)."' WHERE email='".escape($_GET['email'])."'";
                          query($sql);
                    	  set_message("<p class='bg-sucess text-center'>Password Sucessfully Updated</p>");
                    	  redirect("login.php");
                    	}
	                }
			  }   
		    }
		}
		else{
			set_message("<p class='bg-danger text-center'>Sorry your time has expired!</p>");
			redirect("recover.php");
		}
	
}// func Password Reset










?>
