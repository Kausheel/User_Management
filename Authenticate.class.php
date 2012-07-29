<?php

	require('Encrypt.class.php');
	
	class Authenticate 
	{
		
		//Get the table structure from DB_Config
		private $db_host = YOUR_HOSTNAME;
        private $db_username = DB_USERNAME;
        private $db_password = DB_PASSWORD;
        private $db_name = DB_NAME;
		private $user_table = TABLE_OF_USERS;
		private $email_col = COLUMN_OF_EMAILS;
		private $password_col = COLUMN_OF_PASSWORDS;
		
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
                if($stmt = $this->mysqli->prepare("SELECT $this->passwordcol FROM $this->usertable WHERE $this->emailcol = ?"))
                {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $stmt->bind_result($hash);
                    $stmt->fetch();
                }
                else
                {
                    return FALSE;
                }
                
                //Check if the password hashes match
                $encrypt = new Encrypt(12, FALSE);            
                if($encrypt->checkpassword($password, $hash))
                {
                    return TRUE;
                }            
            }
        }	
		
		function createUser($email, $password, $confirmpassword)
		{
		   	if($email && $password && $confirmpassword)
            {
                //Add user to database
                if($stmt = $this->mysqli->prepare("INSERT INTO $this->usertable($this->emailcol, $this->passwordcol) VALUES(?, ?)"))
                {    
                    $stmt->bind_param('ss', $email, $password);
                    $stmt->execute();
                    $stmt->fetch();
                }
                
                if(!$this->mysqli->error)
                {
                    return TRUE;
                }                                      
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