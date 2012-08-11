WARNING! This class is incomplete, various features are still being developed.

Authentication
==================

A PHP class giving you all the necessary functions for authentication: Login, Logout, Register, Email Confirmation, Forgot Password, and Change Password.

- Database settings are independant from the code. Just set once in Configuration file.
- Passwords are hashed with Bcrypt using the PHPass framework.
- Authentication code is independent from table structure, meaning you don't have to fiddle with the code according to what you've named your tables/columns. 
- These parameters just have to be set once in the Configuration file.
- Email settings and content are independant from code, so you can easily alter them in the Configuration file 
- The class can generate emails using the PHPMailer library, and sends an email for account activation and forgotten passwords.
- Users login with their email address.

Usage is as simple as:
- $user -> create_user($email, $password, $confirm_password);

//The return value of login must be checked. If it's TRUE, login() was successful, FALSE means wrong password, and integer 2 means the user still needs to click the registration confirmation link.
- $user -> login($email, $password);

- $user -> change_password($email, $password, $new_password, $confirm_new_password);

//Ask the user where they want the reset link emailed to. 
- $user -> reset_password($email);

//Call set_password when the user has clicked on the link, and sees a form to type in a new password (one that they will remember this time!).
- $user -> set_password($email, $password);

//To unset all session variables.
- $user -> logout();

When the account activation link has been sent after using create_user(), the URL will contain a variable called 'hash.' You should check the contents of $_GET['hash'] on the same page that you linked to the user (check the Configuration file).
At the top of this page, you would have a script with something like:
	if(isset($_GET['hash']) //Or for better verification that the URL hasn't been tampered with, if(strlen($_GET['hash']) == 32) ... because the size of the hash should ALWAYS be 32.
	{
		$user->account_activated($_GET['hash'];
	}
This marks the account as activated, and they can then proceed to use the website.

