WARNING! This class is incomplete, various features are still being developed.

Authentication
==================

A PHP class giving you all the necessary functions for authentication: Login, Logout, Register, Email Confirmation, Reset Password, and Change Password.

- Database settings are independant from the code. Just set once in Configuration file.
- Passwords are hashed with Bcrypt using the PHPass framework.
- Authentication code is independent from table structure, meaning you don't have to fiddle with the code according to what you've named your tables/columns. 
- These parameters just have to be set once in the Configuration file.
- Email settings and content are independant from code, so you can easily alter them in the Configuration file 
- The class can generate emails using the PHPMailer library, and sends an email for account activation and forgotten passwords.
- Users login with their email address.

Usage is as simple as:
- $auth -> create_user($email, $password, $confirm_password);

The return value of login must be checked. If it's TRUE, login() was successful, FALSE means wrong password, and integer 2 means the user still needs to click the registration confirmation link.
- $auth -> login($email, $password);

The existing email/password confirmation is checked before a new password is set, so an error is returned if the user gets their old password wrong.
- $auth -> change_password($email, $password, $new_password, $confirm_new_password);

Ask the user where they want the reset link emailed to. The link will contain a unique hash which corresponds to their email address in the database.  The email they type in MUST be the same as the one they used when registering. 
- $auth -> reset_password($email);

When the account activation link OR password reset link has been sent, the URL will contain a variable called 'hash.' You should check the contents of $_GET['hash'] on the same page that you linked to the user (check the Configuration file).
The check_hash() function compares the hash in the URL with the database, and if the hash exists, it returns the type of hash i.e email validation OR a password reset.
We check the type of hash to decide what we do next, either show a form for the user to type in a new password after a reset, OR call the activate_account() function.

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
- 
Call set_password when the user has clicked on the link, and sees a form to type in a new password (one that they will remember this time!).
- $auth -> set_password($email, $new_password);

To destroy all session variables.
- $auth -> logout();