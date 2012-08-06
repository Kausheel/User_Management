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
			if(isset($_POST['email'], $_POST['password'], $_POST['confirm_password'])
			{
				//Insert code here for validating data, like checking if usernames have valid characters, and passwords are long enough.
				if(!create_user($_POST['email'], $_POST['password'], $_POST['confirm_password']))
				{
					echo 'Sorry, the passwords did not match';
				}
				else
				{
					if(!validate_email($_POST['email']))
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

