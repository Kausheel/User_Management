#Authentication

This is a PHP class giving you all the necessary functions for authenticating and managing users.

###API Summary:

- create_user($email, $password)
- login($email, $password)
- change_password($email, $old_password, $new_password)
- reset_password($email)
- delete_user($email, $password)
- logout()
- hash_exists($hash)
- activate_account($hash)
- set_password($new_password, $hash)

###Features:

- Database settings, table structure, and email/SMTP settings are independant from the code. Just define them in the Configuration file.
- Passwords are hashed with Bluefish/bcrypt using the PHPass library.
- The class can generate emails using the SwiftMailer library, and sends an email for account activation and forgotten passwords.
- MySQL Prepared Statements are used to protect from injection.
- Internal errors are logged using the Klogger class.

###Todolist:
- Switch from MySQLi to PDO for compatibility across different databases.
- Create Unit Tests.
- Add expiries to the links sent in password reset emails.
- Allow a reply-to address to be added when sending emails.

###Notes:

The functions here can return FALSE for 2 reasons:
    1. The return result was actually negative eg. a wrong username/password combination would make login() return FALSE.
    2. A internal error occured while executing the function, like a database access error.
To differentiate between what actually happened in the application, you should regularly check your log files.  If anything went wrong internally, it will be logged there.
The log file location can be set in the Configuration file.

###Usage:

The create_user() function inserts the user into the database after checking for duplicate entries, and sends an email with an account activation link.
It is the developer's responsibility to have another password field, probably called confirm_password, and check that it matches the 1st password field before create_user() is called.
It is also the developer's responsibility to make sure the password is strong enough e.g. minimum number of characters. However this isn't a huge necessity because the password hashes (using bcrypt) are 60 characters anyway.
The create_user() function will return FALSE when the email address is invalid and the email fails to send, or when the email address has already been used to create an account.

    $auth->create_user($email, $password)

The login() function will return TRUE if the $email/$password combination is correct, and FALSE on failure.

    $auth->login($email, $password)

The existing email/password combination is checked before the password is changed, so an error is returned if the user's email/old_password combination is invalid.

    $auth->change_password($email, $old_password, $new_password)

When the user needs a forgotten password to be reset, ask for their email address. If the supplied email is matched in the database, we send that email a link. The link will contain a unique hash which corresponds to their email address in the database.
This will not work if the user tries to reset their password to an email that does not exist in the database. They MUST use the email address they supplied when creating their account, else this function will return FALSE.
When the reset_password() function is called, the existing password IS STILL VALID. I allowed this because sometimes a user asks for a password reset, but then remembers their old password, and tries to login with that.

    $auth->reset_password($email);

When the account activation link OR password reset link has been sent, the URL will contain a variable called 'hash.' Example your_website.com/page.php?hash=5893465gfiuqirekgheo5928re5try5y.
You should check that the $_GET['hash'] variable exists in the database using hash_exists() on the same page that you linked to the user (the link sent in the email is defined in the Configuration file).
hash_exists() returns FALSE when the hash doesn't exist in the database, either because the URL was modified from the original emailed one, or because the link was already clicked and a new password already chosen, so the link is now invalid. We have to include the $_GET['hash'] as a variable in this form because when the form is submitted, we will need to include it as a parameter for the set_password() function.

    //This is the page that the user is linked from their email after a password reset.
    include('authentication.class.php');
    $auth = new Authenticate();

    if($auth->hash_exists($_GET['hash']))
    {
        echo "<form method='post'>
                <input type='password' name='password'>
                <input type='password' name='confirm_password'>
                <input type='hidden' name='hash' value=$_GET['hash']>
                <input type='submit'>
            </form>";
    }

And now when the user clicks submit we check the passwords match, and then call set_password(). We need to pass it the $hash so the function knows which account's password to update.

    if($_POST['password'] == $_POST['confirm_password'])
    {
        if($auth->set_password($_POST['password'], $_POST['hash']))
        {
            echo 'Password successfully reset!';
        }
    }

The activate_account() function changes the value of the boolean database column 'activated' to 1. It also deletes the emailed_hash column, so the emailed link is now dead.

    //This is the page which the user is linked to in their account activation email.
    include('authentication.class.php');
    $auth = new Authenticate();

    if($auth->hash_exists($_GET['hash']))
    {
        if($auth->activate_account($_GET['hash']))
        {
            echo 'Your account has been activated!';
        }
    }

To destroy all session variables.

    $auth->logout();

Call delete_user() to delete the user's row from the database. The correct $email/$password combination for the user must be given for the function to return TRUE.

    $auth->delete_user($email, $password);
