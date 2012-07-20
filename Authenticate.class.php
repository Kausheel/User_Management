<?php

	require('DB_Config.php');
	require('Encrypt.class.php');
	
	class Authenticate {
		
		function __construct()
		{
			$mysqli = new mysqli(HOST, DB_USERNAME, DB_PASSWORD, DATABASE_NAME);				
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
		
		function resetPassword($username)
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