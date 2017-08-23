<?php 



//***************************** Helper Functions *****************************
 
function clean($string) {
    return htmlentities($string);
}

function redirect($location) {
    return header("Location: $location");
}

function set_message($message) {
    if(isset($message)) {
        $_SESSION['message'] = $message;
    } else {
        $message = "";
    }
}

function display_message() {
    if(isset($_SESSION['message'])) {
        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }
}


function token_generator() {
    $token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));
    return $token;
}

function validation_errors($error_message) {
    $error_message = <<<DELIMITER
    <div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span           aria-hidden="true">&times;</span>
    </button><strong>Warning!</strong>  $error_message  </div>
DELIMITER;
    
    return $error_message;
}


function email_exists($email) {
    $sql = "SELECT id FROM users WHERE email = '$email' ";
    $result = query($sql);
    if(row_count($result) == 1) {
        return true;
    } else {
        return false;
    }
}


function username_exists($username) {
    $sql = "SELECT id FROM users WHERE username = '$username' ";
    $result = query($sql);
    if(row_count($result) == 1) {
        return true;
    } else {
        return false;
    }
}



function send_email($email, $subject, $msg, $header) {
    
   return mail($email, $subject, $msg, $header);
    
}


//***************************** Register User Functions *****************************



function validate_user_registration() {
    
    $errors = [];
    
    $min = 2;
    $max = 20;
    
    if($_SERVER['REQUEST_METHOD'] == "POST") {
        $first_name = clean($_POST['first_name']);
        $last_name  = clean($_POST['last_name']);
        $username   = clean($_POST['username']);
        $email      = clean($_POST['email']);
        $password   = clean($_POST['password']);
        $confirm_password   = clean($_POST['confirm_password']);
        
        if(strlen($first_name) <= $min) {
            $errors[] = "Your first name cannot be less than $min characters";
        } else if (strlen($first_name) > $max) {
            $errors[] = "Your first name cannot be more than $max characters";
        }
        
        
        if(strlen($last_name) <= $min) {
            $errors[] = "Your last name cannot be less than $min characters";
        } else if (strlen($last_name) > $max) {
            $errors[] = "Your last name cannot be more than $max characters";
        }
        
        if(strlen($username) <= $min) {
            $errors[] = "Your username cannot be less than $min characters";
        } else if (strlen($username) > $max) {
            $errors[] = "Your username cannot be more than $max characters";
        }
        
        if(username_exists($username)) {
            $errors[] = "Sorry, that username is already registered";
        }
        
        if(email_exists($email)) {
            $errors[] = "Sorry, that email already exists";
        }
        
        if(strlen($email) < $min) {
            $errors[] = "Your email cannot be less than $min characters";
        }  
        
        if($password !== $confirm_password) {
            $errors[] = "Your password fields do not match";
        }
           
        if(!empty($errors)) {
            foreach($errors as $error) {
                 echo validation_errors($error);
            }
        } else {
            if(register_user($first_name, $last_name, $username, $email, $password)) {
                
                set_message("<p class='bg-success text-center'> Please check your email or spam folder for activation link </p>");
                
                redirect("index.php");
                
                echo "USER REGISTERED";
                
            }  else {
                
            set_message("<p class='bg-success text-center'> Unable to Register </p>");
            }
        }
    }  // $_POST REQUEST()   
}   // validate_user_registration()
        





function register_user($first_name, $last_name, $username, $email, $password) {
    
    $first_name = escape($first_name);
    $last_name  = escape($last_name);
    $username   = escape($username);
    $email      = escape($email);
    $password   = escape($password);
    
    
    if(email_exists($email)) {
        return false;
    } else if (username_exists($username)){
        return false;
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT); 
        
        $validation_code = md5( $username . microtime() );
        
        $sql = "INSERT INTO users(first_name, last_name, username, email, password, validation_code, active)";
        $sql .= " VALUES('$first_name','$last_name','$username','$email','$password','$validation_code', 0 )";
        $result = query($sql);
        confirm($result);
        
        // send email activation code
        $subject = "Activate Account";
        $msg = "Please click the link below to activate your account
         http://localhost/loginphp/activate.php?email=$email&code=$validation_code
        ";
        $header = "From: noreply@yourwebsite.com";
        
        send_email($email, $subject, $msg, $header);
        
        return true;
    }
    
} 



//***************************** Activate User Functions *****************************


function activate_user() {
    
    if($_SERVER['REQUEST_METHOD'] == "GET") {
        
        if(isset($_GET['email'])) {
            $email = clean($_GET['email']);
            $validation_code = clean($_GET['code']); 
            
            $sql = "SELECT id FROM users WHERE email = '".escape($_GET['email'])."' AND validation_code = '".escape($_GET['code'])."' ";
            
            $result = query($sql);
            confirm($result);
            
        if(row_count($result) == 1) {
            $sql2 = "UPDATE users SET active = 1, validation_code = 0 WHERE email = '".escape($_GET['email'])."' AND validation_code = '".escape($_GET['code'])."' ";    

            $result2 = query($sql2);
            confirm($result2);

            set_message("<p class='bg-success'> Your Account Has Been Activated!</p>");
            redirect("login.php");
                
            } else {
            
            set_message("<p class='bg-danger'> Sorry, Your account could not be activated!</p>");
            redirect("login.php");
            
            }
        } 
    }
}




//***************************** Validate Login User Functions *****************************


function validate_user_login() {
    
    $errors = [];
    
    if($_SERVER['REQUEST_METHOD'] == "POST") {

        $email      = clean($_POST['email']);
        $password   = clean($_POST['password']);
        $remember   = isset($_POST['remember']);

    if(empty($email)) {
        $errors[] = "Email field cannot be empty";
    }
        
    if(empty($password)) {
        $errors[] = "Password field cannot be empty";
    }    
        
    if(!empty($errors)) {
        
        foreach($errors as $error) {
        echo validation_errors($error);

            }
        
        } else {    
            
            if(login_user($email, $password, $remember)) {
                
                redirect("admin.php");
                
            } else {
                
                echo validation_errors("Error validating credentials");
                
            }
        }
    }
}




//***************************** Login User Functions *****************************



function login_user($email, $password, $remember) {
    
    $sql = "SELECT password, id FROM users WHERE email = '".escape($email)."'";
    $result = query($sql);
    
    if(row_count($result) == 1) {
        
        $row = fetch_array($result);
        
        $db_password = $row['password'];
        
//        if(password_hash($password, PASSWORD_DEFAULT) === $db_password) {
            If(password_verify($password, $db_password)) {
            
            if($remember == "on") {
                
                setcookie('email', $email, time() + 86400);
                
            }
            
            
            
            $_SESSION['email'] = $email;
            
            return true;
        
        } 
    } else {
            
            return false;
        
        }
} //end function



//***************************** Logged-In Functions *****************************


function logged_in() {
    if(isset($_SESSION['email']) || isset($_COOKIE['email'])) {
        return true;
    } else 
        return false;
}




//***************************** Recover password Functions *****************************


function recover_password() {
    
        if($_SERVER['REQUEST_METHOD'] == "POST") {
            
            if(isset($_SESSION['token']) && $_POST['token'] == $_SESSION['token']) {
                
                $email = escape($_POST['email']);
                
                if(email_exists($email)) {
                    
                    $validation_code = md5($email . microtime());
                    
                    setcookie('temp_access_code', $validation_code, time() + 900);
                    
                    $sql = "UPDATE users SET validation_code = '".escape($validation_code)."' WHERE email = '".escape($email)."'  ";
                    $result = query($sql);
                    confirm($result);                                                                                                 
                
                    $subject = "Please reset your password";
                    $message = "Please click the link below to reset your password
                    
                    'http://localhost/loginphp/code.php?email=$email&code=$validation_code'
                    
                    ";
                    $header = "From : noreply@yoursite.com";
                        
                if(!send_email($email, $subject, $message, $header)) {
                        
                    echo validation_errors("Email could not be sent");
                    
                    }  
                    
                    set_message("<p class='bg-success text-center'>Please check email/spam folder for a password reset code</p>");
                    
                    redirect("index.php");
                    
                    
                }  else {
                    
                    echo validation_errors("This email does not exist");
                    
                    }
                
                
            } else {
                
                redirect("index.php");    
                
            }
            
            if(isset($_POST['cancel_submit'])) {
                
                redirect("login.php");
                
            }
                
        }
}



//***************************** Code Validation *****************************



function validate_code() {
    
    if(isset($_COOKIE['temp_access_code'])) {
        
            if(!isset($_GET['email']) && !isset($_GET['code'])) {
                
                redirect("index.php");  
                
            } else if (empty($_GET['email']) || empty($_GET['code'])) {
                
                redirect("index.php");  
            
            } else {

                    if(isset($_POST['code'])) {

                    $email = clean($_GET['email']);    
                    $validation_code =  clean($_POST['code']);
                        
                    $sql = "SELECT id FROM users WHERE validation_code = '".escape($validation_code)."' AND email = '".escape($email)."' ";   

                    $result = query($sql);
                    confirm($result);

                    if(row_count($result) == 1) {
                        
                            setcookie('temp_access_code', $validation_code, time() + 300);

                            redirect("reset.php?email=$email&code=$validation_code");

                        } else {

                            validation_errors("Sorry, wrong validation code");
                        }

                    } 
            }
    
    }  else  {
        
        set_message("<p class='bg-danger'> Sorry, your validation code has expired </p>");
        redirect("recover.php");
        
    }
        
}


//***************************** Password Reset Function  *****************************


function password_reset() {
    
    if(isset($_COOKIE['temp_access_code'])) {
        
        if(isset($_GET['email']) && isset($_GET['code'])) {

            if(isset($_SESSION['token']) && isset($_POST['token'])) {
               
               if($_SESSION['token'] === $_POST['token']) {
                   
                   if($_POST['password'] === $_POST['confirm_password']){
                       
                       
                       $updated_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                 
                    $sql = "UPDATE users SET password = '".escape($updated_password)."' WHERE email = '".escape($_GET['email'])."' ";
                       
                    $result = query($sql);
                    confirm($result);   
                       
                    set_message("<p class='bg-success'> Your password has been updated, please login </p>");

                       
                    redirect("login.php");   
            
                   } else {
                       
                       echo "Passwords DO NOT match!";
                   }
               }
            }    
        }
    } else {
        
        set_message("<p class='bg-danger text-center'> Page expired </p>");
        redirect("recover.php");
            
    }
}



























?>