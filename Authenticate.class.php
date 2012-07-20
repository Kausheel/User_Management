<?php

	require('DB_Config.php');
	require('Encrypt.class.php');
	
	class Authenticate {
		
		private $mysqli;
		
		//Get the table structure from DB_Config
		private $tablename = TABLE_OF_USERS;
		private $usercol = COLUMN_OF_USERS;
		private $emailcol = COLUMN_OF_EMAILS;
		private $passwordcol = COLUMN_OF_PASSWORDS;
		
	 	function __construct()
		{
			$mysqli = new mysqli(HOST, DB_USERNAME, DB_PASSWORD, DATABASE_NAME);	
			$this -> mysqli = $mysqli;			
		}
		
		
		function login($username, $password) 
		{
			
			
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