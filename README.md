Authentication 
=====================

A PHP class giving you all the necessary functions for authenticating and managing users.

API Summary:

- create_user($email, $password)
- login($email, $password)
- change_password($email, $old_password, $new_password)
- reset_password($email)
- delete_user($email, $password)
- logout()
- check_hash_type($hash)
- activate_account($hash)
- set_password($email, $password)

Features:

- Database settings, table structure, and email/SMTP settings are independant from the code. Just set once in Configuration file.
- Passwords are hashed with Bluefish/bcrypt using the PHPass framework. 
- The class can generate emails using the PHPMailer library, and sends an email for account activation and forgotten passwords.
- Users login with their email address.
- MySQL Prepared Statements are used to protect from injection.
- Error messages are set in the Configuration file, so you can customize the wording of various errors without going through the code. 

Usage:

The create_user() function inserts the user into the database, and sends an email with a confirmation link.  
It is the developer's responsibility to have another password field, called confirm_password field, and check it with the 1st password field before create_user() is called.  
It is also the developer's responsibility to make sure the password is strong enough e.g minimum characters.   

    $auth->create_user($email, $password);  

The login() function will return TRUE if the $email/$password combination is correct, and FALSE on failure.  

    if($auth->login($email, $password) {echo 'Successful login.';}

The existing email/password combination is checked before the password is changed, so an error is returned if the user gets their old password wrong, or the email address is not valid.  

    $auth->change_password($email, $old_password, $new_password);

When the user needs a forgotten password to be reset, ask for their email address. If the supplied email is matched in the database we send that email a link. The link will contain a unique hash which corresponds to their email address in the database.   
This will not work if the user tries to reset their password to an email that does not exist in the database. They MUST use the email address they supplied when creating their account, else this function will return FALSE.    
When the reset_password() function is called, the existing password IS STILL VALID. I allowed this because sometimes a user asks for a password reset, but then suddenly remembers their old password, and tries to login with that.  

    $auth->reset_password($email);
    
Call delete_user() to delete the row from the database. The correct $email/$password combination for the user must be given for the function to return TRUE.

    $auth->delete_user($email, $password);

When the account activation link OR password reset link has been sent, the URL will contain a variable called 'hash.' Example your_website.com/page.php?hash=reset5893465gfiuqirekgheo5928re5try5
You should check the contents of $_GET['hash'] on the same page that you linked to the user (check the Configuration file).

The check_hash_type() function compares the hash in the URL with the database, and if the hash exists, it returns the type of hash i.e account activation OR a password reset.
We check the type of hash to decide what we do next, either show a form for the user to type in a new password after a reset, OR call activate_account().

    include('Authenticate.class.php');
    $auth = new Authenticate();
	    
    if($_GET['hash'])
    {
        $hash_type = $auth->check_hash_type($_GET['hash']);
        if($hash_type == 'unverified')
        {
            if(!$auth->activate_account($_GET['hash']))
            {
                echo 'Error: The hash does not exist. <br> REDIRECT TO LOGIN PAGE.';
            }
            else
            {
                echo 'The account has been activated. REDIRECT TO WELCOME PAGE';
            }
        }
        elseif($hash_type == 'reset')
        {
            echo 'Link to "Set New Password" page.';
        }
    }
The activate_account() function changes the value of the boolean database column 'activated' to 1. It also deletes the emailed_hash column, so the emailed link is now dead.

Call set_password() when the user has clicked on the emailed link from reset_password(), and check_hash_type() has been called to determine that the user has indeed requested a password reset, and they have now been redirected
 to a form to set their new password (one that they will remember this time!). From this form we call set_password(). 
This will return FALSE if the $email is not in the database.

    $auth->set_password($email, $new_password);

To destroy all session variables.

    $auth->logout();
