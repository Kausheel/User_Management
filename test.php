<?php
    
    //The purpose of this code is to run tests on the Authenticate class
	require('Authenticate.class.php');
	
	$object = new Authenticate();
	
	$object -> createUser($username, $password, $confirmpassword, $email);
	$object -> login($username, $password);
	$object -> changePassword($username, $password, $newpassword, $confirmnewpassword);
	$object -> resetPassword($username, $email);
	$object -> logout();
    
?>