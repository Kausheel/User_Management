<?php

	require('DB_Config.php');
	require('Encrypt.class.php');
	
	class Authenticate {
		
		private $mysqli;
		
		//Get the table structure from DB_Config
		private $usertable = TABLE_OF_USERS;
		private $usercol = COLUMN_OF_USERS;
		private $passwordcol = COLUMN_OF_PASSWORDS;
		
	 	function __construct()
		{
			$mysqli = new mysqli(HOST, DB_USERNAME, DB_PASSWORD, DATABASE_NAME);	
			$this -> mysqli = $mysqli;			
		}
		
		
		function login($username, $password) 
		{
		    //Check variables exist
		    if(empty($username) || empty($password))
            {
                return FALSE;
            }
            
		    //Fetch password from database
			$stmt = $this -> mysqli -> prepare("SELECT {$this -> passwordcol} FROM {$this -> usertable} WHERE {$this -> usercol} = ?");
            $stmt -> bind_param('s', $username);
            $stmt -> execute();
            $stmt -> bind_result($hash);
            $stmt -> fetch();
            
            //Check if the password hashes match
            $encrypt = new Encrypt(12, FALSE);            
            if($encrypt -> checkpassword($password, $hash))
            {
                return TRUE;
            }            
        }	
		
		function createUser($username, $password, $confirmpassword, $email)
		{
			
			
			
		}
		
		function logout() 
		{
			
			session_destroy();
			
		}
		
		function resetPassword($username, $email)
		{
			
			
			
		}
		
		function changePassword($username, $password, $newpassword, $confirmnewpassword)
		{
			
			
			
		}		
				
		
		
		
		
	}

?>