<?php
    
    //The purpose of this code is to run tests on the Authenticate class
	require('Authenticate.class.php');
    
	$object = new Authenticate();    
    
    //$object -> createUser($username, $password, $confirmpassword, $email);
	if($object -> login('kausheel', 'a_password'))
	{
	    print 'You have logged in successfully!';
    }
    else
    {
        print 'Wrong password';
    }
        
	$object -> changePassword($username, $password, $newpassword, $confirmnewpassword);
	$object -> resetPassword($username, $email);
	//$object -> logout();
    
?>