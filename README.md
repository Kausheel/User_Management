Authentication
==================

A PHP class giving you the basics: Login, Logout, Register, Forgot Password, and Change Password.

- Passwords are hashed with Bcrypt using the PHPass framework.
- Authentication code is independent from table structure, meaning you don't have to fiddle with the code according to what you've named your tables/columns. 
- These parameters just have to be set once in the DB_Config file.
- The class will EVENTUALLY be able to generate and send emails, for when the user first Registers or Resets their password. 
- Users login with their email address, but this can be changed if you prefer they use an actual username.

Usage is as simple as:
	$user -> register($username, $password, $confirmpassword);
	$user -> login($username, $password);
	$user -> changepassword($password, $newpassword, $confirmnewpassword);
etc...

Code contributions are welcome!
