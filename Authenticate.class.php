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
		    //Fetch username and password from database
		    $query ="SELECT {$this -> usercol}, {$this -> passwordcol} FROM {$this -> usertable} WHERE {$this -> usercol} = '$username'";
			$result = $this -> mysqli -> query($query); 
            $row = $result -> fetch_assoc();
            $hash = $row['password'];
            
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
		
		
		function isUserActive($active)
		{
						
			
			
		}
		
		function changePassword($username, $password, $newpassword, $confirmnewpassword)
		{
			
			
			
		}		
				
		
		
		
		
	}

?>