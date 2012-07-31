Authentication
==================

A PHP class giving you all the necessary functions for authentication: Login, Logout, Register, Email Confirmation, Forgot Password, and Change Password.

- Passwords are hashed with Bcrypt using the PHPass framework.
- Authentication code is independent from table structure, meaning you don't have to fiddle with the code according to what you've named your tables/columns. 
- These parameters just have to be set once at the top of the Authenticate file.
- The class can generate emails using the PHPMailer library, and sends an email for account activation. 
- Email parameters like SMTP servers and the message content/layout are all configurable in the validate_email() function.
- Users login with their email address, but this can be changed if you prefer they use an actual username.

Usage is as simple as:
- $user -> register($email, $password, $confirmpassword);
- $user -> login($email, $password);
- $user -> changepassword($password, $newpassword, $confirmnewpassword);
etc...

!!!Functions for Forgot Password and Change password are still being developed. 
!!!Be sure to thoroughly test all functionality before using in production, because this project is still new and not fully tested.  


