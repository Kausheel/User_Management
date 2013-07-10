#PHP User Management

This is a PHP class giving you all of the necessary functions for authenticating and managing users.

###API Summary:

- create_user($email, $password)
- login($email, $password)
- change_password($email, $old_password, $new_password)
- reset_password($email)
- delete_user($email, $password)
- logout()
- is_registered($email)
- is_activated($email)
- activate_account($hash)
- set_password($new_password, $hash)

###Features:

- Passwords are hashed with Bcrypt using the [PHPass](http://www.openwall.com/phpass/) library.
- Database settings, table structure, email/SMTP settings, log file location, and password encryption strength are kept separate from the code. Just define them in the Configuration file.
- The class generates emails using the [SwiftMailer](http://swiftmailer.org/) library, and sends an email for account activation and forgotten passwords.
- MySQL Prepared Statements are used to protect from injection.
- Internal errors are logged using [Klogger](https://github.com/katzgrau/KLogger).

###Todolist:
- Switch from MySQLi to PDO for compatibility across different databases.
- Create Unit Tests.
- Allow password reset emails to expire.
- Let account activation through email be disabled.
- Keep track of the number of login attempts, and block after a specified amount.
- Create a function to allow the user to change their email address.
- Allow a reply-to address to be added when sending emails.

###Notes:

The functions here can return FALSE for 2 reasons:
    1. The return result was actually bad eg. a wrong username/password combination would make login() return FALSE.
    2. A internal error occured while executing the function, like a database access error.
If anything goes wrong internally, it will be logged. You should regularly check your log files to track and eliminate internal errors. The log file location can be set in the Configuration file.

###Usage:

The create_user() function inserts the user into the database after checking for duplicate entries, and sends an email with an account activation link.
It is the developer's responsibility to have another password field, called something like confirm_password, and check that it matches the 1st password field before create_user() is called.
It is also the developer's responsibility to make sure the password is strong enough e.g. minimum number of characters. However this isn't a huge necessity because the password hashes produced using Bcrypt are 60 characters anyway.
The create_user() function will return FALSE when the email address is invalid and the email fails to send, or when the email address has already been used to create an account.

    $user->create_user($email, $password)

When the user attempts to create an account, it's good practice to run the is_registered() function to check if the email address is already in use. If it returns TRUE, you should display a message telling the user they've already used that address.
Only when is_registered() returns FALSE, should you proceed to call create_user(). create_user() calls is_registered() by itself, and so will return FALSE if the email address exists anyway,
but you can't know for user WHY create_user() returned FALSE, since there could've been some internal error that occurred. So just to be sure, use is_registered() before calling create_user().

    $user->is_registered($email)

The activate_account() function changes the value of the boolean table column 'activated' to 1. The $_GET['hash'] variable is attached to a user's account, so it is used as an identifier for the activate_account() function to know which account to activate.
Once this function has been executed, the user is now allowed to login(). If this function isn't executed before using login(), then login() will always return FALSE for that user. You cannot login() without activating your account.
Running this function also deletes the $_GET['hash'] from the database, so the emailed link is now dead.

    //This is the page which the user is linked to in their account activation email.
    include('user_management.class.php');
    $user = new User_Management();

    if($user->activate_account($_GET['hash']))
    {
        echo 'Your account has been activated!';
    }

The login() function will return TRUE if the $email/$password combination is correct, and FALSE if the account isn't activated, or if the $email/$password combination is wrong.

    $user->login($email, $password)

If login() returns FALSE, one of the reasons could be that the user hasn't activated their account. You should check for this event so that you can display a "Please check your email" message.
Use is_activated() to check if the supplied email address has been activated or not. This function is necessary if login() returns FALSE, because otherwise the user might not know why their email/password combination
keeps failing. This function will help you decide if login() returned FALSE because some internal error occurred in login(), or the user hasn't activated their account.

    $user->is_activated($email)

Traditionally the current password is required from the user before a password change is allowed. Therefore the following function takes on that password as a parameter.
The existing email/password combination is checked before the password is changed, so an error is returned if the user's email/old_password combination is invalid.

    $user->change_password($email, $old_password, $new_password)

When the user needs a forgotten password to be reset, ask for their email address. If the supplied email is matched in the database, we send that email a link. The link will contain a unique hash which is stored with their email address in the database.
This will not work if the user tries to reset their password to an email that does not exist in the database. They MUST use the email address they supplied when creating their account, else this function will return FALSE.
When the reset_password() function is called, the existing password IS STILL VALID. This is allowed because sometimes a user asks for a password reset, but then remembers their old password, and attempts to login with that. If this function is called
multiple times for the same user, for example if the user repeatedly clicks a "Reset" button, then this function will keep sending more emails. However, each email will contain a valid link, so it's not a problem. The hash will only be generated once,
and then copied into subsequent emails, so it's not an issue if a user repeatedly clicks a "Reset" button when they get impatient, which they will if the email is taking too long to arrive and they get impatient.

    $user->reset_password($email);

When the password reset link has been sent, the URL will contain a variable called 'hash.' Example your_website.com/set_new_password.php?hash=5893465gfiuqirekgheo5928re5try5y.
You should link the user to a page where there is a form to type in a new password. The set_password() function takes on 2 parameters, the new password, and the hash in the URL. This hash is used as an identifier, so that the function knows whose
password to change. If the hash doesn't exist, then set_password() will return FALSE. Password reset hashes are only generated and stored for users who have requested a password reset. The form should contain a hidden variable whose value is $_GET['hash'].
The reason is that when the user clicks "Submit", we'll need to send $_GET['hash'] as a parameter to set_password(). If we don't include $_GET['hash'] as part of the variables in the form, then it will disappear by the time the page reloads to
call set_password(). This is because $_GET variables are stored in the URL as shown in the example above, so as soon as the user navigates away, the URL changes, and so $_GET variables disappear. To keep the $_GET['hash'] variable,
use a hidden variable like this:

    //set_new_password.php
    <form action="process_new_password.php" method="post">
        <input type="password" name="password">
        <input type="password" name="confirm_password">
        <input type="hidden" name="hash" value="$_GET['hash']">
        <input type="submit">
    </form>

    //process_new_password.php
    include('user_management.class.php');
    $user = new User_Management();

    if($_POST['password'] == $_POST['confirm_password'])
    {
        if($user->set_password($_POST['password'], $_POST['hash']))
        {
            echo 'Password successfully changed!';
        }
    }

Use this to destroy all session variables. It will run session_destroy(), which removes all of the user's session variables.

    $user->logout();

Call delete_user() to delete the user's row/account from the database. The correct $email/$password combination for the user must be given for the function to return TRUE, so provide them with a form to input these.

    $user->delete_user($email, $password);
