Authentication <-- This module is in PUBLIC DOMAIN, so you can do whatever you want with it
==================

A PHP class giving you all the necessary functions for authentication.

API Summary:

- create_user($email, $password, $confirm_password)
- login($email, $password)
- change_password($email, $password, $new_password, $confirm_new_password)
- reset_password($email)
- logout()
- check_hash($hash)
- account_activated($hash)
- set_password($email, $password)

Features:

- Database settings, table structure, and email/SMTP settings are independant from the code. Just set once in Configuration file.
- Passwords are hashed with Bcrypt using the PHPass framework. 
- The class can generate emails using the PHPMailer library, and sends an email for account activation and forgotten passwords.
- Users login with their email address.
- MySQL Prepared Statements are used to protect from injection.

Usage:

The create_user() function inserts the user into the database, and sends an email with a confirmation link.
- $auth -> create_user($email, $password, $confirm_password);

IMPORTANT: The return value of login MUST be checked. If it's TRUE, login() was successful, FALSE means wrong password, and integer 2 means the user still needs to click the registration confirmation link.
- $auth -> login($email, $password);

The existing email/password confirmation is checked before a new password is set, so an error is returned if the user gets their old password, or the email address is not valid.
- $auth -> change_password($email, $password, $new_password, $confirm_new_password);

Ask the user where they want the reset link emailed to. The link will contain a unique hash which corresponds to their email address in the database.  The email they type in MUST be the same as the one they used when registering. 
Important note: when the reset_password() function is called, the existing password IS STILL VALID. I allowed this because sometimes a user asks for a password reset, but then suddenly remembers their old password, and tries to login with that.  
- $auth -> reset_password($email);

When the account activation link OR password reset link has been sent, the URL will contain a variable called 'hash.' You should check the contents of $_GET['hash'] on the same page that you linked to the user (check the Configuration file).
The check_hash() function compares the hash in the URL with the database, and if the hash exists, it returns the type of hash i.e email validation OR a password reset.
We check the type of hash to decide what we do next, either show a form for the user to type in a new password after a reset, OR call the account_activated() function.

    include('Authenticate.class.php');
    $auth = new Authenticate();
	    
    if(strlen($_GET['hash']) == 42)
    {
        $hash_type = $auth->check_hash($_GET['hash']);
        if($hash_type == 'unverified')
        {
            if(!$auth->account_activated($_GET['hash']))
            {
                echo 'Error: The hash does not exist. <br> REDIRECT TO LOGIN PAGE.';
            }
            else
            {
                echo 'Your account has been activated. REDIRECT TO LOGIN PAGE';
            }
        }
        elseif($hash_type == 'reset')
        {
            echo 'Link to "Set New Password" page.';
        }
    }
The account_activated() function changes the value of a boolean database column to 1, meaning the account has been activated. It also deletes the emailed_hash, so the emailed link is now dead.

Call set_password when the user has clicked on the link, and sees a form to type in a new password (one that they will remember this time!). This will return FALSE if the 2 passwords do not match or the $email is not in the database.
- $auth -> set_password($email, $password);

To destroy all session variables.
- $auth -> logout();
