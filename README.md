WARNING! This class is incomplete, various features are still being developed.

Authentication
==================

A PHP class giving you all the necessary functions for authentication: Login, Logout, Register, Email Confirmation, Forgot Password, and Change Password.

- Database settings are independant from the code, to simplify making changes.
- Passwords are hashed with Bcrypt using the PHPass framework.
- Authentication code is independent from table structure, meaning you don't have to fiddle with the code according to what you've named your tables/columns. 
- These parameters just have to be set once at the top of the Authenticate file.
- Email settings and content are independant from code, so you can easily alter them in the Configuration file 
- The class can generate emails using the PHPMailer library, and sends an email for account activation. 
- Users login with their email address, but this can be changed if you prefer they use an actual username.

Usage is as simple as:
- $user -> create_user($email, $password, $confirmpassword);
- $user -> login($email, $password);
- $user -> change_password($email, $password, $newpassword, $confirm_new_password);
- $user -> reset_password($email);
- $user -> logout();

!!!Be sure to thoroughly test all functionality before using in production, because this project is still new and not fully tested.

USAGE:

To create a new user, just POST the contents of the form and call the create_user() function:

	<body>
		<?php
		include('Authentication.class.php');
		$user = new Authenticate;
			if(isset($_POST['email'], $_POST['password'], $_POST['confirm_password'])
			{
				//Insert code here for validating data, like checking if usernames have valid characters, and passwords are long enough.
				
				if(!$user->create_user($_POST['email'], $_POST['password'], $_POST['confirm_password']))
				{
					echo 'Sorry, the passwords did not match';
				}
				else
				{
					//Send an email with the activation link.
					if(!$user->validate_email($_POST['email']))
					{
						echo 'Failed to generate email';
					}
					else
					{
						echo 'An email has been sent to you with a link to activate your account';
					}
				}
			?>
		<form action='index.php' method='post'>
			Email: 				<input type='text' name='email'>
			Password: 			<input type='text' name='password'>
			Confirm Password: 	<input type='text' name='confirm_password'> 
		</form>
	</body>

When the account activation link has been sent, the URL will contain a variable called 'hash.' You should check the contents of $_GET['hash'] on the same page that you told the user to click on (check the Configuration file).
At the top of this page, you would have a script with something like:
	if(isset($_GET['hash']) //Or for better verification that the URL hasn't been tampered with, if(strlen($_GET['hash']) == 32) ... because the size of the hash should ALWAYS be 32.
	{
		$user->account_activated($_GET['hash'];
	}
This marks the account as activated, and they can then proceed to use the website.

In your login form, you just need a form setup with an email input box and a password input box. Then, with the POST variables, call:

$user->login($_POST['email'], $_POST['password']);

To send the user a link to create a new password after forgetting their old one, call:

$user->reset_password($_POST['email']);
		
and the email will be sent.

To logout the user, just call:
$user->logout();
to destroy the session.

The change_password() function is incomplete right now.


