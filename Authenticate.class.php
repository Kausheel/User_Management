<?php

	require('Database.class.php');
	require('Encrypt.class.php');
	
	class Authenticate {
		
		function __construct()
		{
			
			$mysqli = new Database();
			$mysqli = $mysqli -> connect();
				
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