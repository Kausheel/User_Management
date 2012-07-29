<?php

	require('DB_Config.php');
	require('Encrypt.class.php');
	
	class Authenticate 
	{
		
		//Get the table structure from DB_Config
		private $usertable = TABLE_OF_USERS;
		private $emailcol = COLUMN_OF_EMAILS;
		private $passwordcol = COLUMN_OF_PASSWORDS;
		
        private $mysqli;
        
	 	function __construct()
		{
			$mysqli = new mysqli(HOST, DB_USERNAME, DB_PASSWORD, DATABASE_NAME);	
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