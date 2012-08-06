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


