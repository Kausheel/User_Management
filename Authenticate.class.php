<?php

	include('Encrypt.class.php');
    include('PHP_mailer.class.php');
	include('Configuration.php');
    
    class Authenticate 
	{
	    //The database settings and table structures are inherited from the Configuration.php file.
		private $db_host = DB_HOST;
        private $db_username = DB_USERNAME;
        private $db_password = DB_PASSWORD;
        private $db_name = DEFAULT_DB;
        private $user_table = TABLE_WITH_USERS;
		private $email_col = COLUMN_WITH_EMAILS;
		private $password_col = COLUMN_WITH_PASSWORD_HASHES;
        private $activated_col = COLUMN_CONFIRMING_ACCOUNT_ACTIVATION;
        private $emailed_hash_col = COLUMN_WITH_EMAILED_HASHES;		
        private $mysqli;
        
	 	function __construct()
		{
			$this->mysqli = new mysqli($this->db_host, $this->db_username, $this->db_password, $this->db_name);
        }
		
		function create_user($email, $password, $confirm_password)
		{
            if($password == $confirm_password)
            {
                //Generate the password.
                $encrypt = new Encrypt(12, FALSE);
                $password = $encrypt->hash_password($password);
                
                //Generate the random hash.
                $random_hash = $this->generate_random_hash();      
                $random_hash = 'unverified'.$random_hash;
                    
                //Generate the email.
                $mail = $this->generate_email($email, 'registration', $random_hash);
                
                //Send the email.
                if(!$mail->Send())
                {
                    return FALSE;
                }
                
                //Add the email, password, and random hash to the database.
                $stmt = $this->mysqli->prepare("INSERT INTO `$this->user_table`(`$this->email_col`, `$this->password_col`, `$this->emailed_hash_col`) VALUES(?, ?, ?)");   
                $stmt->bind_param('sss', $email, $password, $random_hash);
                $stmt->execute();
                
                return empty($this->mysqli->error);
            }                                
        }
        
        function login($email, $password) 
        {
            //Fetch password and emailed_hash from database, to check if the account has been activated.
            $stmt = $this->mysqli->prepare("SELECT `$this->password_col`, `$this->emailed_hash_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($password_hash, $emailed_hash);
            $stmt->fetch();
              
            if(!$this->mysqli->error)
            {  
                //Check if the account has been verified.
                if(strpos($emailed_hash, 'unverified') !== FALSE)
                {
                    return 2;
                }
                                                
                //Check if the password hashes match
                $encrypt = new Encrypt(12, FALSE);            
                return $encrypt->check_password($password, $password_hash);      
            }    
        }   
        
        //Change user password.
        function change_password($email, $password, $new_password, $confirm_new_password)
        {
            if($new_password == $confirm_new_password)
            {
                if($this->login($email, $password))
                {
                    return $this->set_password($email, $new_password);
                }
            }
        }       
        
        //Email a password reset link embedded with a unique hash.
        function reset_password($email)
        {
            $random_hash = $this->generate_random_hash();
            
            //The 'reset' flag will be checked when the password reset link is clicked, to make sure the user did actually request a password reset.
            $random_hash = 'reset'.$random_hash;
            
            $mail = $this->generate_email($email, 'reset', $random_hash);
                
            if(!$mail->Send())
            {
                return FALSE;
            }                
                            
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->emailed_hash_col` = ? WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $random_hash, $email);
            $stmt->execute();    

            return empty($this->mysqli->error);
        }
        
        //When the GET variable is found in the URL, a user has either clicked a password reset link, OR an email validation link. This function will check the hash and return the type. 
        function check_hash($hash)
        {
            $stmt = $this->mysqli->prepare("SELECT `$this->emailed_hash_col` FROM `$this->user_table` WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            $stmt->bind_result($result);
            $stmt->fetch();
            
            if($hash === $result)
            {
                if(strpos($hash, 'reset') !== FALSE)
                {
                    return 'reset';
                }
                elseif(strpos($hash, 'unverified') !== FALSE)
                {
                    return 'unverified';
                }  
            }                 
        }
        
        function logout() 
        {
            session_start();
            $_SESSION = array();
            return session_destroy();  
        }
        
        function set_password($email, $password)
        {
            //Encrypt the password.
            $encrypt = new Encrypt(12, FALSE);
            $password = $encrypt->hash_password($password);
               
            //Insert the password, and delete the emailed_hash column just in case this function was called after a password reset.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->password_col` = ?, `$this->emailed_hash_col` = '' WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $password, $email);
            $stmt->execute();
                    
            return empty($this->mysqli->error);
        }       
		
        //Mark the account as activated.
        function account_activated($hash)
        {
            //Replace the emailed_hash_col with an empty value.
            $blank = '';          
            
            //Update the 'Activated' field to TRUE, and delete the $hash.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->activated_col` = '1', `$this->emailed_hash_col` = ? WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('ss', $blank, $hash);
            $stmt->execute();
              
            return empty($this->mysqli->error);
        }
        
        //Generate a unique 32 character long hash. Called by create_user() and reset_password().
        private function generate_random_hash()
        {
            //Check if the $random_hash has been used before, and if yes, then generate another one until a unique hash is found.
            $stmt = $this->mysqli->prepare("SELECT `$this->emailed_hash_col` FROM `$this->user_table` WHERE `$this->emailed_hash_col` = ? LIMIT 1");
            do
            {
                $random_hash = md5(uniqid(rand(), true));    
                $stmt->bind_param('s', $random_hash);
                $stmt->execute();
                $stmt->bind_result($emailed_hash_col);
                $stmt->fetch();
            }
            while($emailed_hash_col == $random_hash);   
            
            if(!$this->mysqli->error)
            {
                return $random_hash;
            }          
        }           
        
        //Generate email, inheriting the values of constants from Configuration.php. The $type of email generated is either a registration confirmation or a password reset.
        //Called by reset_password() and create_user().
        private function generate_email($email, $type, $random_hash)
        {
            $mail = new PHPMailer();
            $mail->Username = SMTP_USERNAME;                         
            $mail->Password = SMTP_PASSWORD;                     
            $mail->From = SENDER_ADDRESS;                       
            $mail->FromName = FROM_NAME;
            $mail->AddAddress($email);        
            $mail->AddReplyTo(REPLY_TO);   
            $mail->WordWrap = WORD_WRAP;
            $mail->Host = SMTP_SERVERS;
            $mail->Port = PORT;
            $mail->SMTPSecure = SMTP_SECURE;
            
            if(IS_SMTP === 'TRUE')
            {
                $mail->IsSMTP();
            }                                   
            
            if(SMTP_AUTH === 'TRUE')
            {    
                $mail->SMTPAuth = true;
            }                              
            
            if(IS_HTML === 'TRUE')
            {                                   
                $mail->IsHTML(true);
            }
            
            if($type == 'registration')
            {                                    
                $mail->Subject = REGISTRATION_SUBJECT;
                $mail->Body = REGISTRATION_BODY;
                $mail->AltBody = REGISTRATION_ALT_BODY;
            }
            elseif($type == 'reset')
            {
                $mail->Subject = RESET_SUBJECT;
                $mail->Body = RESET_BODY;
                $mail->AltBody = RESET_ALT_BODY;
            }
            else
            {
                return FALSE;
            }
            
            //Replace the $random_hash placeholder in the Body's URL with the actual hash.
            $mail->Body = str_replace('$random_hash', $random_hash, $mail->Body);
            $mail->AltBody = str_replace('$random_hash', $random_hash, $mail->AltBody);
            
            return $mail;
        }
    }

?>
