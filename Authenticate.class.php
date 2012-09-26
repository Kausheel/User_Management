<?php

    include('includes/Encrypt.class.php');
    include('includes/PHP_mailer.class.php');
    include('Configuration.php');
    
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
        
        //NOTE, there are many more CONSTANTS inherited from the Configuration file, containing various error messages to be outputted from this class. 
        //We store them externally so they are easily editable without having to go through this code, separating Logic from Presentation.
        
        //Start a database connection.
        function __construct()
        {
            $this->mysqli = new mysqli($this->db_host, $this->db_username, $this->db_password, $this->db_name);
            
            if($this->mysqli->connect_error)
            {
                echo DATABASE_CONNECTION_ERROR;   
            }            
        }
        
        function create_user($email, $password)
        {
            if(!($email && $password))
            {
                echo CREATE_USER_MISSING_PARAMETER;
                return FALSE;
            }
            
            $password = $this->encrypt_password($password);
              
            //Generate the random hash to be sent in the email confirmation link.
            $random_hash = $this->generate_random_hash();      
            $random_hash = 'unverified'.$random_hash;
                    
            //Generate the email.
            $mail = $this->generate_email($email, 'registration', $random_hash);
                
            //Send the email.
            if(!$mail->Send())
            {
                echo CREATE_USER_MALFORMED_EMAIL;
                return FALSE;
            }
                
            //Add the email, password, and random hash to the database.
            $stmt = $this->mysqli->prepare("INSERT INTO `$this->user_table`(`$this->email_col`, `$this->password_col`, `$this->emailed_hash_col`) VALUES(?, ?, ?)");   
            $stmt->bind_param('sss', $email, $password, $random_hash);
            $stmt->execute();
            
            if(!$this->mysqli->error)
            {
                return TRUE;
            }
            else
            {
                echo CREATE_USER_DATABASE_ERROR;
                return FALSE;   
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
        
        function change_password($email, $old_password, $new_password)
        {
            //Test the user's credentials first. 
            if($this->login($email, $old_password))
            {
                return $this->set_password($email, $new_password);
            }
        }       
        
        //Email a password reset link embedded with a unique hash.
        function reset_password($email)
        {
            $random_hash = $this->generate_random_hash();
            
            //The 'reset' flag will be checked when the password reset link is clicked, to make sure the user did actually request a password reset.
            $random_hash = 'reset'.$random_hash;
                            
            //Insert the $random_hash into the database.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->emailed_hash_col` = ? WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $random_hash, $email);
            $stmt->execute();    

            if($this->mysqli->error)
            {
                return FALSE;
            }
            
            //Generate email.
            $mail = $this->generate_email($email, 'reset', $random_hash);
                
            if(!$mail->Send())
            {
                return FALSE;
            }              
            
            return TRUE;
        }
        
        //When the GET variable is found in the URL, a user has either clicked a password reset link, OR an email validation link. This function will check the hash and return the type. 
        function check_hash($hash)
        {
            //Attempt to find the URL hash in the database. If it exists, bind_result($result) should contain exactly what we searched for. If not, the hash doesn't exist.
            //A non-existent hash means the user must've malformed the hash in the URL manually.
            $stmt = $this->mysqli->prepare("SELECT `$this->emailed_hash_col` FROM `$this->user_table` WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            $stmt->bind_result($result);
            $stmt->fetch();
            
            if($hash === $result)
            {
                //The suffix of the hash is now checked. 'reset' means the user asked for a password reset, and 'unverified' means the account was just created.
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
            $password = $this->encrypt_password($password);
               
            //Insert the password, and delete the emailed_hash column since we want it to be empty when we are not awaiting an email link to be clicked.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->password_col` = ?, `$this->emailed_hash_col` = '' WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $password, $email);
            $stmt->execute();
                    
            return empty($this->mysqli->error);
        }       
        
        //Mark the account as activated.
        function account_activated($hash)
        {
            //We replace the emailed_hash_col with an empty value.
            $blank = '';          
            
            //Update the 'Activated' field to TRUE, and delete the emailed_hash_column.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->activated_col` = '1', `$this->emailed_hash_col` = ? WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('ss', $blank, $hash);
            $stmt->execute();
              
            return empty($this->mysqli->error);
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
