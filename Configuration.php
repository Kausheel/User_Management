<?php
    //Define the database/table/column structure here, to be inherited by the Authenticate class.
    //Column for PASSWORD_HASHES must be 60 bytes, ACCOUNT_ACTIVATION 1 byte, and EMAILED_HASHES 42 bytes.
    define('DB_HOST', 'localhost');
    define('DB_USERNAME', 'my_username');
    define('DB_PASSWORD', 'my_password');
    define('DEFAULT_DB', 'database_name');
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
?>