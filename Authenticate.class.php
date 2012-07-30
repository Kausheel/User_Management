<?php

	require('Encrypt.class.php');
	
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
                if($stmt = $this->mysqli->prepare("SELECT $this->password_col FROM $this->user_table WHERE $this->email_col = ?"))
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
                    if($stmt = $this->mysqli->prepare("INSERT INTO $this->user_table($this->email_col, $this->password_col) VALUES(?, ?)"))
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