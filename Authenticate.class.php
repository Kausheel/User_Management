<?php

	include('Encrypt.class.php');
    include('class.phpmailer.php');
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
				
			$this -> mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);			
        }
		
		function create_user($email, $password, $confirm_password)
		{
		   	if($email && $password && $confirm_password)
            {
                if($password == $confirm_password)
                {
                    $encrypt = new Encrypt(12, FALSE);
                    $password = $encrypt->hash_password($password);
                    
                    //This prefix is checked when logging in to see if the account has been verified through email. When it's been verified, this prefix is removed.
                    $password = 'unverified'.$password;
                    
                    //Add user to database
                    $stmt = $this->mysqli->prepare("INSERT INTO `$this->user_table`(`$this->email_col`, `$this->password_col`) VALUES(?, ?)");   
                    $stmt->bind_param('ss', $email, $password);
                    $stmt->execute();
                    
                    validate_email($email);
                                        
                    if(!$this->mysqli->error)
                    {
                        return TRUE;
                    }      
                }                                
            }
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
                
                //Check if the account has been verified.
                if(strstr($hash, 'unverified') != FALSE)
                {
                    return 2;
                }
                                               
                //Check if the password hashes match
                $encrypt = new Encrypt(12, FALSE);            
                if($encrypt->check_password($password, $hash))
                {
                    return TRUE;
                }            
            }
        }   
        
        //Send a link with an embedded unique hash as an email 
        function validate_email($email)
        {
            $random_hash = generate_random_hash();                   
            
            //If the hash was correctly generated, insert it into the database
            if(strlen($random_hash) == 32)
            {
                $stmt = $this->mysqli->prepare("INSERT INTO `$this->user_table`(`$this->emailed_hash_col`) VALUES(?) WHERE `$this->email_col` = ?");
                $stmt->bind_param('ss', $random_hash, $email);
                $stmt->execute();
            }
            
            $mail = generate_email($email, 'registration');
            
            if($mail->Send())
            {
                return TRUE;
            }        
        }
		
        //Mark the account as activated
        function account_activated($hash)
        {
            $stmt = $this->mysqli->prepare("SELECT `$this->password_col` FROM `$this->user_table` WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            $stmt->bind_result($password);
            $stmt->fetch();
            
            if($this->mysqli->error)
            {
                return FALSE;
            }
             
            //The account is now activated, so remove the 'unverified' flag.
            $password = str_replace('unverified', '', $password);
            
            //Update the 'Activated' field to TRUE, and set the modified password.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->activated_col` = 'TRUE', `$this->password_col` = ? WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('ss', $password, $hash);
            $stmt->execute();
            
            return empty($this->mysqli->error);
        }
        
		function logout() 
		{
			
			session_destroy();
			
		}
		
		function reset_password($email)
		{
		    $random_hash = generate_random_hash();
            
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->emailed_hash_col` = ? WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $random_hash, $email);
            $stmt->execute();
            
            if($this->mysqli->error)
            {
                 return FALSE;
            }
            
            $mail = generate_email($email, 'reset');
            
            if($mail->Send())
            {
                return TRUE;
            }           
        }
		
		function change_password($email, $password, $new_password, $confirm_new_password)
		{
						
		}		
		
		function set_password($email, $password)
        {
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->password_col` = ? WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $password, $email);
            $stmt->execute();
            
            return empty($this->mysqli->error);
        }		
        
        function generate_random_hash()
        {
            //Check if the $random_hash has been used before, and if yes, then generate another one until a unique hash is found 
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
            
            return $random_hash;             
        }           
        
        //Generate email, inheriting the values of constants from Configuration.php. The $type of email generated is either a registration confirmation or a password reset.
        function generate_email($email, $type)
        {
            $mail = new PHPMailer();
            
            if(IS_SMTP === 'TRUE')
            {
                $mail->IsSMTP();
            }                        
                           
            $mail->Host = SMTP_SERVERS;
            
            if(SMTP_AUTH === 'TRUE')
            {    
                $mail->SMTPAuth = true;
            }                              
               
            $mail->Username = SMTP_USERNAME;                         
            $mail->Password = SMTP_PASSWORD;                     
            $mail->From = SENDER_ADDRESS;                       
            $mail->FromName = FROM_NAME;
            $mail->AddAddress($email);        
            $mail->AddReplyTo(REPLY_TO);   
            $mail->WordWrap = WORD_WRAP;
            
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
            
            return $mail;
        }
    }

?>