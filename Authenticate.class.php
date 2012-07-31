<?php

	include('Encrypt.class.php');
    include('class.phpmailer.php');
	
    class Authenticate 
	{
	    //Define database login details
		private $db_host = YOUR_HOSTNAME;
        private $db_username = DB_USERNAME;
        private $db_password = DB_PASSWORD;
        private $db_name = DB_NAME;
        
        //Define database structure
		private $user_table = TABLE_OF_USERS;
		private $email_col = COLUMN_OF_EMAILS;
		private $password_col = COLUMN_OF_PASSWORDS;
        private $email_validation_hash = COLUMN_OF_EMAIL_VALIDATION_HASHES;
        private $activated = COLUMN_CONFIRMING_ACCOUNT_ACTIVATION;
		
        private $mysqli;
        
	 	function __construct()
		{
			$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);	
			$this -> mysqli = $mysqli;			
		}


        function login($email, $password) 
		{
		    if($email && $password)
            {
                //Fetch password from database
                $stmt = $this->mysqli->prepare("SELECT `$this->password_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->bind_result($hash);
                $stmt->fetch();
                                
                //Check if the password hashes match
                $encrypt = new Encrypt(12, FALSE);            
                if($encrypt->check_password($password, $hash))
                {
                    return TRUE;
                }            
            }
        }	
		
		function create_user($email, $password, $confirm_password)
		{
		   	if($email && $password && $confirm_password)
            {
                if($password == $confirm_password)
                {
                    $encrypt = new Encrypt(12, FALSE);
                    $password = $encrypt->hash_password($password);
                    
                    //Add user to database
                    $stmt = $this->mysqli->prepare("INSERT INTO `$this->user_table`(`$this->email_col`, `$this->password_col`) VALUES(?, ?)");   
                    $stmt->bind_param('ss', $email, $password);
                    $stmt->execute();
                    $stmt->fetch();
                    
                    validate_email($username);
                                        
                    if(!$this->mysqli->error)
                    {
                        return TRUE;
                    }      
                }                                
            }
			
			
		}
        
        function validate_email($username)
        {
            //Check if the $random_hash has been used before, and if yes, then generate another one until a unique hash is found 
            $stmt = $this->mysqli->prepare("SELECT `$this->email_validation_hash` FROM `$this->user_table` WHERE `$this->email_validation_hash` = ? LIMIT 1");
            do
            {
                $random_hash = md5(uniqid(rand(), true));    
                $stmt->bind_param('s', $random_hash);
                $stmt->execute();
                $stmt->bind_result($email_validation_hash);
                $stmt->fetch();
            }
            while($email_validation_hash == $random_hash);                                     
            
            //If the hash was correctly generated, insert it into the database
            if(strlen($random_hash) == 32)
            {
                $stmt = $this->mysqli->prepare("INSERT INTO `$this->user_table`(`$this->email_validation_hash`) VALUES(?) WHERE `$this->email_col` = ?");
                $stmt->bind_param('ss', $random_hash, $username);
                $stmt->execute();
            }
            
            $mail = new PHPMailer();
            
            $mail->IsSMTP();                                        //Set mailer to use SMTP
            $mail->Host = "smtp1.example.com;smtp2.example.com";    //Specify main and backup server
            $mail->SMTPAuth = true;                                 //Turn on SMTP authentication
            $mail->Username = "myusername";                         //SMTP username
            $mail->Password = "secretpassword";                     //SMTP password
            
            $mail->From = "from@example.com";                       //Sender
            $mail->FromName = "Mailer";
            $mail->AddAddress("josh@example.net", "Josh Adams");    //Recipient    
            $mail->AddReplyTo("info@example.com", "Information");   //Optional reply to address
            $mail->WordWrap = 50;                                   //Set word wrap to X amount of characters
            $mail->IsHTML(true);                                    //Set email format to HTML
            
            $mail->Subject = "Account registration";
            $mail->Body    = "You have received this email because this address was used to register at our website. 
                              If this was you, please click the link below:  
                              http://localhost/Authentication/test.php?hash=$random_hash"; //The website URL for receiving the hash as a GET variable
            $mail->AltBody = "This is the body in plain text for non-HTML mail clients";
            
            if(!$mail->Send())
            {
                return FALSE;
            }
            else
            {
                return TRUE;
            }
            
            
        }
		
        //Mark the account as activated
        function account_activated($hash)
        {
            //Find the hash in the database, and mark the corresponding 'Activated" field to TRUE
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->activated` = 'TRUE' WHERE `$this->email_validation_hash` = ?");
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            
            if($this->mysqli->error)
            {
                return FALSE;
            }
            else
            {
                return TRUE;
            }   
        }
		function logout() 
		{
			
			session_destroy();
			
		}
		
		function resetPassword($username)
		{
			
			
			
		}
		
		function changePassword($username, $password, $newpassword, $confirmnewpassword)
		{
			
			
			
		}		
				
		
		
		
		
	}

?>