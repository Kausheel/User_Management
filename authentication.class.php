<?php

    include('includes/encrypt.class.php');
    include('includes/swift/lib/swift_required.php');
    include('configuration.php');
    include('includes/klogger.class.php');
    
    class Authenticate 
    {
        //The database settings and table structures are inherited from the Configuration.php file.
        private $db_host = DB_HOST;
        private $db_username = DB_USERNAME;
        private $db_password = DB_PASSWORD;
        private $db_name = DB_NAME;
        private $user_table = TABLE_WITH_USERS;
        private $email_col = COLUMN_WITH_EMAILS;
        private $password_col = COLUMN_WITH_PASSWORD_HASHES;
        private $activated_col = COLUMN_CONFIRMING_ACCOUNT_ACTIVATION;
        private $emailed_hash_col = COLUMN_WITH_EMAILED_HASHES;     
        private $mysqli;
        private $log;
        
        //NOTE, there are many more CONSTANTS inherited from the Configuration file, containing various error messages to be outputted from this class. 
        //We store them externally so they are easily editable without having to go through this code, separating Logic from Presentation.
        
        //Start a database connection.
        public function __construct()
        {
            $this->log = new Klogger(LOG_DIRECTORY, 7);
            
            $this->mysqli = new mysqli($this->db_host, $this->db_username, $this->db_password, $this->db_name);
            
            if($this->mysqli->connect_error)
            {
                $this->log->logFatal('The database connection failed', $this->mysqli->connect_error);   
            }            
        }
        
        public function create_user($email, $password)
        {
            if(!($email && $password))
            {
                return FALSE;
            }
            
            if(check_duplicate_user($email))
            {
                return FALSE;
            }
            
            $password = $this->encrypt_password($password);
              
            //To be sent in the email confirmation link.
            $random_hash = $this->generate_random_hash();      
                       
            //Add the email, password, and random hash to the database. The $activated_col should be 0 by default, and 1 once the emailed link is clicked.
            $stmt = $this->mysqli->prepare("INSERT INTO `$this->user_table`(`$this->email_col`, `$this->password_col`, `$this->emailed_hash_col`, `$this->activated_col`) VALUES(?, ?, ?, 0)");   
            $stmt->bind_param('sss', $email, $password, $random_hash);
            $stmt->execute();
            
            if($this->mysqli->error)
            {
                $this->log->logFatal('Failed to create user', $this->mysqli->error);
                return FALSE;   
            }
            
            //Generate the email.
            $mail = $this->send_email($email, 'registration', $random_hash);
                
            //Send the email.
            if($mail)
            {
                return TRUE;
            }
            else
            {                
                //Rollback the database insertion.
                $this->delete_user($email, $password);
                
                $this->log->logFatal('Failed to send account activation email');
                
                return FALSE;
            }        
        }
        
        public function login($email, $password) 
        {
            if(!($email && $password))
            {
                return FALSE;
            }
            
            //Fetch the password and emailed_hash from database by matching the email. If there is no result, the $email does not exist.
            $stmt = $this->mysqli->prepare("SELECT `$this->password_col`, `$this->emailed_hash_col`, `$this->activated_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($stored_password, $emailed_hash, $activated);
            $stmt->fetch();
              
            if($this->mysqli->error)
            {
                $this->log->logFatal('Login failed', $this->mysqli->error);
                return FALSE;
            }      
                              
            //Check if the password hashes match
            $encrypt = new Encrypt(12, FALSE);            
            if(!$encrypt->check_password($password, $stored_password))
            {
                return FALSE;
            }
              
            //Check that the account has been activated through the emailed link.
            if($activated === 0)
            {
                return FALSE;
            }
           
            return TRUE;             
        }   
        
        public function change_password($email, $old_password, $new_password)
        {
            if(!($email && $old_password && $new_password))
            {
                return FALSE;
            }
             
            //Validate the $email/$password combination provided. 
            if($this->login($email, $old_password))
            {
                return $this->set_password($email, $new_password);
            }
            else 
            {
                return FALSE; 
            }            
        }       
        
        //Email a password reset link embedded with a unique hash.
        public function reset_password($email)
        {
            if(!$email)
            {
                return FALSE;
            }
            
            $random_hash = $this->generate_random_hash();
            
            //The 'reset' flag will be checked when the password reset link is clicked, to make sure the user did actually request a password reset.
            $random_hash = 'reset'.$random_hash;
                            
            //Insert the $random_hash into the database.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->emailed_hash_col` = ? WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $random_hash, $email);
            $stmt->execute();    

            if($this->mysqli->error)
            {
                $this->log->logFatal('Error inserting $random_hash', $this->mysqli->error);
                return FALSE;
            }
            
            //Generate email.
            $mail = $this->send_email($email, 'reset', $random_hash);
                
            if(!$mail)
            {                
                //Rollback changes to database.
                $this->mysqli->query("UPDATE `$this->user_table` SET `$this->emailed_hash_col` = '' WHERE `$this->emailed_hash_col` = '$random_hash'");
                
                return FALSE;
            }              
            
            return TRUE;
        }
        
        public function delete_user($email, $password)
        {
            //Validate the $email/$password combination provided.
            if(!$this->login($email, $password))
            {
                return FALSE;
            }
            
            $stmt = $this->mysqli->prepare("DELETE FROM `$this->user_table` WHERE `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
                
            return TRUE;
        }
        
        //When the GET variable is found in the URL, a user has either clicked a password reset link, OR an email validation link. This function will check the hash and return the type. 
        public function check_hash_type($hash)
        {
            //Attempt to find the URL hash in the database. If it exists, bind_result($stored_hash) should be identical to the parameter $hash. If not, the hash doesn't exist, so return FALSE.
            //A non-existent hash means the user must've malformed the hash in the URL manually.
            $stmt = $this->mysqli->prepare("SELECT `$this->emailed_hash_col`, `$this->activated_col` FROM `$this->user_table` WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            $stmt->bind_result($stored_hash, $activated);
            $stmt->fetch();
                    
            if($hash != $stored_hash)
            {
                return FALSE;
            }
                
            //If the $activated_col === 0, then user must've clicked an account activation link.
            if($activated === 0)
            {
                return 'unverified';
            }
            elseif(strpos($hash, 'reset') !== FALSE) 
            {
                return 'reset';   
            }                 
        }
        
        public function logout() 
        {
            session_start();
            $_SESSION = array();
            session_destroy();
            
            return TRUE;  
        }
        
        public function set_password($email, $password)
        {
            if(!($email && $password))
            {
                return FALSE;
            }             
                
            $password = $this->encrypt_password($password);
               
            //Insert the password, and delete the emailed_hash column since we want it to be empty when we are not awaiting an email link to be clicked.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->password_col` = ?, `$this->emailed_hash_col` = '' WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $password, $email);
            $stmt->execute();
                    
            if(!$this->mysqli->error)
            {
                return TRUE;
            }
            else
            {
                $this->log->logFatal('Error setting new password', $this->mysqli->error);
                return FALSE;
            }
        }       
        
        public function activate_account($hash)
        {
            if(!$hash)
            {
                return FALSE;
            }
            
            //Update the 'Activated' field to TRUE, and delete the emailed_hash_column.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->activated_col` = '1', `$this->emailed_hash_col` = ? WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('ss', '', $hash);
            $stmt->execute();
              
            if(!$this->mysqli->error)
            {
                return TRUE;
            }
            else
            {
                $this->log->logFatal('Error setting Account Activated to true', $this->mysqli->error);
                return FALSE;
            }
        }
        
        private function check_duplicate_user($email)
        {
            $stmt = $this->mysqli->prepare("SELECT `$this->email_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($duplicate_user);
            $stmt->fetch();
            
            if($duplicate_user == $email)
            {
                return TRUE;
            }
        }
        
        private function encrypt_password($password)
        {
            $encrypt = new Encrypt(12, FALSE);
            $password = $encrypt->hash_password($password);
            
            return $password;
        }
        
        //Generate a unique 32 character long hash. Called by create_user() and reset_password().
        private function generate_random_hash()
        {
            //Create a $random_hash and check if it already exists in the database, and if yes, then generate another one until a unique hash is found.
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
        private function send_email($email, $type, $random_hash)
        {
            $transport = Swift_SmtpTransport::newInstance(SMTP_SERVER, PORT, SECURITY)
				->setUsername(SMTP_USERNAME)
				->setPassword(SMTP_PASSWORD);
					
			$swift = Swift_Mailer::newInstance($transport);
                        
            if($type == 'registration')
            {                                    
                $message = Swift_Message::newInstance()
				->setFrom(SENDER_ADDRESS)
				->setTo($email)
				->setSubject(REGISTRATION_SUBJECT)
				->setBody(REGISTRATION_BODY);
            }
            elseif($type == 'reset')
            {
                $message = Swift_Message::newInstance()
				->setFrom(SENDER_ADDRESS)
				->setTo($email)
				->setSubject(RESET_SUBJECT)
				->setBody(RESET_BODY);
            }
            else
            {
                return FALSE;
            }
            
            //Replace the $random_hash placeholder in the Body's URL with the actual hash.
            $message->setBody(str_replace('$random_hash', $random_hash, $message->getBody(), $counter));
            
            if(($counter) != 1)
            {
                $this->log->logFatal('The $random_hash variable from the URL in the email bodies was modified/removed');
            }
                        
            if(!$swift->send($message))
            {
                if($type == 'reset')
                {
                    $this->log->logFatal('A password reset email failed to send');
                    return FALSE;
                }
                elseif($type == 'registration')
                {
                    $this->log->logFatal('An account activation email failed to send');
                    return FALSE;
                }
            }
            else
            {
                return TRUE;
            }
        }
    }

?>
