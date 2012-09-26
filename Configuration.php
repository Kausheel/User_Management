<?php
    //Define the database/table/column structure here, to be inherited by the Authenticate class.
    //Column for PASSWORD_HASHES must be 60 characters wide, ACCOUNT_ACTIVATION 1 character, and EMAILED_HASHES 42 characters.
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
    define('IS_SMTP', 'TRUE');
    define('SMTP_SERVERS', 'smtp1.example.com;smtp2.example.com');
    define('SMTP_AUTH', 'TRUE');
    define('SMTP_USERNAME', 'my_username');
    define('SMTP_PASSWORD', 'my_password');
    define('SMTP_SECURE', 'ssl');
    define('PORT', 1000);
    define('SENDER_ADDRESS', 'me@example.com');
    define('FROM_NAME', 'my_name');
    define('REPLY_TO', 'support@example.com, Support Department'); 
    define('WORD_WRAP', 50);
    define('IS_HTML', 'TRUE');
    
    //Define the subject and body when an email is sent to confirm registration. The $random_hash embedded in the URL will be str_replaced in the create_user() function.
    define('REGISTRATION_SUBJECT', 'Thank you for registering!'); 
    define('REGISTRATION_BODY', 'Please click the link below to activate your account: <br> http://name_of_your_website.com/test.php?hash=$random_hash');
    define('REGISTRATION_ALT_BODY', 'This body will be shown when the email client does not support HTML');
    
    //Define the subject and body when a user resets their password. THe $random_hash embedded in the URL will be str_replaced in the reset_password() function.
    define('RESET_SUBJECT', 'You have reset your password');
    define('RESET_BODY', 'To reset your password, please click the link below, and follow the steps to create a new password: <br> http://name_of_your_website.com/test.php?hash=$random_hash');
    define('RESET_ALT_BODY', 'This body will be shown when the email client does not support HTML');
    
    //Define the error messages echoed to the browser for each circumstance. We define them here so that the wording of error messages can be easily modified, without having to edit the actual code.
    //This is an effort to separate Logic from Presentation. 
    //If you don't want an error message to be echoed out at all, just delete the 2nd parameter of the function call to make it an empty string, but don't delete the function or constant itself. 
    //Example define('CONSTANT_NAME', ''); Don't delete the whole line, otherwise an E_NOTICE error will be thrown for attempting to call an undefined constant. 
    define('DATABASE_CONNECTION_ERROR', 'Please check your MySQL connection settings in the Configuration file, and try again.');
    define('CREATE_USER_MALFORMED_EMAIL', 'The email address you entered is invalid. Please go back and try again.');
    define('CREATE_USER_MISSING_PARAMETER', 'You cannot leave the email or password fields blank');
    define('CREATE_USER_DATABASE_ERROR', 'There was an error inserting the data into the database. Ensure your Configuration file suits your database schema.');
    
?>