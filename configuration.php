<?php
	//Define the location to store the log files.
	define('LOG_DIRECTORY', 'logs');
	
    //Define the database/table/column structure here, to be inherited by the Authenticate class.
    //Column for EMAILS must be 254 characters wide, PASSWORD_HASHES must be 60 characters wide, ACCOUNT_ACTIVATION 1 character, and EMAILED_HASHES 32 characters.
    //NOTE: If you do not set these values correctly, you'll receive ambiguous errors like 'Call to a member function bind_param on a non-object.'  
    define('DB_HOST', 'localhost');
    define('DB_USERNAME', 'my_username');
    define('DB_PASSWORD', 'my_password');
    define('DB_NAME', 'database_name');
    define('TABLE_WITH_USERS', 'table_name');
    define('COLUMN_WITH_EMAILS', 'column_name');
    define('COLUMN_WITH_PASSWORD_HASHES', 'column_name');
    define('COLUMN_CONFIRMING_ACCOUNT_ACTIVATION', 'column_name');
    define('COLUMN_WITH_EMAILED_HASHES', 'column_name');

    //Define email settings. Emails are sent when the user registers, and when a password is reset.
    define('SMTP_SERVER', 'smtp.example.com');
    define('SECURITY', 'tsl');
    define('SMTP_USERNAME', 'my_username');
    define('SMTP_PASSWORD', 'my_password');
    define('PORT', 1000);
    define('SENDER_ADDRESS', 'me@example.com');
    define('FROM_NAME', 'my_name');
    
    //Define the subject and body when an email is sent to confirm registration. The $random_hash embedded in the URL will be str_replaced in the create_user() function.
    //Do NOT modify/remove the $random_hash variable, or else an error will be logged.
    define('REGISTRATION_SUBJECT', 'Thank you for registering!'); 
    define('REGISTRATION_BODY', 'Please click the link below to activate your account: <br> http://name_of_your_website.com/test.php?hash=$random_hash');
    
    //Define the subject and body when a user resets their password. The $random_hash embedded in the URL will be str_replaced in the reset_password() function. 
    //Do NOT modify/remove the $random_hash variable, or else an error will be logged.
    define('RESET_SUBJECT', 'You have reset your password');
    define('RESET_BODY', 'To reset your password, please click the link below, and follow the steps to create a new password: <br> http://name_of_your_website.com/test.php?hash=$random_hash');
?>
