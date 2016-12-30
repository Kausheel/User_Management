<?php

    include('includes/Swift/lib/swift_required.php');
    include('configuration.php');
    include('includes/klogger.class.php');

    class User_Management
    {
        //The database settings and table structures are inherited from the Configuration.php file.
        private $db_host = DB_HOST;
        private $db_username = DB_USERNAME;
        private $db_password = DB_PASSWORD;
        private $db_name = DB_NAME;
        private $db_port = DB_PORT;
        private $user_table = TABLE_WITH_USERS;
        private $email_col = COLUMN_WITH_EMAILS;
        private $password_col = COLUMN_WITH_PASSWORD_HASHES;
        private $activated_col = COLUMN_CONFIRMING_ACCOUNT_ACTIVATION;
        private $emailed_hash_col = COLUMN_WITH_EMAILED_HASHES;
        private $encryption_rounds = ENCRYPTION_ROUNDS;
        private $mysqli;
        private $log;

        //Start a database connection.
        public function __construct()
        {
            $this->log = new Klogger(LOG_DIRECTORY, 7);

            $this->mysqli = new mysqli($this->db_host, $this->db_username, $this->db_password, $this->db_name, $this->db_port);

            if($this->mysqli->connect_error)
            {
                $this->log->logCrit('The database connection failed', $this->mysqli->connect_error);
            }
        }

        public function create_user($email, $password)
        {
            if(!($email && $password))
            {
                return FALSE;
            }

            //If the user has already registered, and is trying again, it is possible that they lost the original email and is simply requesting a new one.
            //In this case, we need to make sure the $emailed_hash we email to them is the same as the first one.
            if($this->is_registered($email))
            {

                $stmt = $this->mysqli->prepare("SELECT `$this->emailed_hash_col`, `$this->activated_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->bind_result($random_hash, $activated);
                $stmt->fetch();

                //If the user has already managed to activate their account, why would they try registering again?
                if($activated)
                {
                    return FALSE;
                }
            }
            else
            {
                $password = $this->encrypt_password($password);

                //To be sent in the email confirmation link.
                $random_hash = $this->generate_random_hash();

                //Add the email, password, and random hash to the database. The $activated_col should be 0 by default, and 1 once the emailed link is clicked.
                $stmt = $this->mysqli->prepare("INSERT INTO `$this->user_table`(`$this->email_col`, `$this->password_col`, `$this->emailed_hash_col`, `$this->activated_col`) VALUES(?, ?, ?, 0)");
                $stmt->bind_param('sss', $email, $password, $random_hash);
                $stmt->execute();
            }

            if($this->mysqli->error)
            {
                $this->log->logCrit('Failed to create user', $this->mysqli->error);
                return FALSE;
            }

            //Generate the email.
            $mail = $this->send_email($email, 'registration', $random_hash);

            //Send the email.
            if($mail)
            {
                return TRUE;
            }
            else
            {
                //Rollback the database insertion.
                //The 3rd parameter indicates that the password is encrypted
                $this->delete_user($email, $password, TRUE);

                return FALSE;
            }
        }

        //This function will check if a user exists in the database.
        public function is_registered($email)
        {
            if(!$email)
            {
                return FALSE;
            }

            $stmt = $this->mysqli->prepare("SELECT `$this->email_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($email_in_database);
            $stmt->fetch();

            if($this->mysqli->error)
            {
                $this->log->logCrit('Failed to check if the user is_registered', $this->mysqli->error);
                return FALSE;
            }

            if($email_in_database == $email)
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }

        //This function will check if a registered user has activated their account.
        public function is_activated($email)
        {
            if(!$email)
            {
                return FALSE;
            }

            $stmt = $this->mysqli->prepare("SELECT `$this->activated_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($activated);
            $stmt->fetch();

            if($this->mysqli->error)
            {
                $this->log->logCrit('Failed to check if the user is_activated', $this->mysqli->error);
                return FALSE;
            }

            if($activated == 1)
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }

        public function login($email, $password)
        {
            if(!($email && $password))
            {
                return FALSE;
            }

            //Fetch the password from database by matching the email. If there is no result, the $email does not exist.
            //Also fetch the account activation flag, because we don't allow someone to login if they haven't activated their account.
            $stmt = $this->mysqli->prepare("SELECT `$this->password_col`, `$this->activated_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($stored_password, $activated);
            $stmt->fetch();

            if($this->mysqli->error)
            {
                $this->log->logCrit('Login failed', $this->mysqli->error);
                return FALSE;
            }

            //Check if the password hashes match
            if(!password_verify($password, $stored_password))
            {
                return FALSE;
            }

            //Check that the account has been activated through the emailed link.
            if($activated === 0)
            {
                return FALSE;
            }

            return TRUE;
        }

        public function change_password($email, $old_password, $new_password)
        {
            if(!($email && $old_password && $new_password))
            {
                return FALSE;
            }

            //Validate the $email/$password combination provided.
            if(!$this->login($email, $old_password))
            {
                return FALSE;
            }

            $new_password = $this->encrypt_password($new_password);

            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->password_col` = ? WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $new_password, $email);
            $stmt->execute();

            if($this->mysqli->error)
            {
                $this->log->logCrit('Failed to change password', $this->mysqli->error);
                return FALSE;
            }

            return TRUE;
        }

        //Email a password reset link embedded with a unique hash.
        public function reset_password($email)
        {
            if(!$email)
            {
                return FALSE;
            }

            //Why would the user try to reset their password before activating their account?
            if(!$this->is_activated($email))
            {
                return FALSE;
            }

            //If the user accidentally or impatiently clicks the Submit button in a password reset form thinking it might not have worked the
            //first time, because emails sometimes take a few minutes to send, they might trigger this function twice.
            //An issue with that is a new $random_hash will be generated, inserted into the database, and emailed. This renders the previous
            //$emailed_hash useless, so the user might click on the first email they received and wonder why it's not working. To prevent a new
            //$random_hash being calculated, we check for an existing one first.
            $stmt = $this->mysqli->prepare("SELECT `$this->emailed_hash_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($random_hash);
            $stmt->fetch();

            if($this->mysqli->error)
            {
                $this->log->logCrit('Error checking for existing $random_hash in reset_password', $this->mysqli->error);
                return FALSE;
            }

            if(!$random_hash)
            {
                $stmt->store_result();

                $random_hash = $this->generate_random_hash();

                //Insert the $random_hash into the database.
                $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->emailed_hash_col` = ? WHERE `$this->email_col` = ?");
                $stmt->bind_param('ss', $random_hash, $email);
                $stmt->execute();

                if($this->mysqli->error)
                {
                    $this->log->logCrit('Error inserting $random_hash', $this->mysqli->error);
                    return FALSE;
                }
            }

            //Generate and send email.
            $mail = $this->send_email($email, 'reset', $random_hash);

            if(!$mail)
            {
                //Rollback changes to database.
                $this->mysqli->query("UPDATE `$this->user_table` SET `$this->emailed_hash_col` = '' WHERE `$this->emailed_hash_col` = '$random_hash'");

                return FALSE;
            }

            return TRUE;
        }

        private function hash_exists($hash)
        {
            if(!$hash)
            {
                return FALSE;
            }

            $stmt = $this->mysqli->prepare("SELECT `$this->emailed_hash_col` FROM `$this->user_table` WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            $stmt->bind_result($result);
            $stmt->fetch();

            if($this->mysqli->error)
            {
                $this->log->logCrit('Failed to check if hash exists', $this->mysqli->error);
                return FALSE;
            }

            if($hash == $result)
            {
                return TRUE;
            }
        }

        //Delete a user from the database after verifying that the user is authorised to delete the account
        public function delete_user($email, $password, $is_password_encrypted = FALSE)
        {
            if(!($email && $password))
            {
                return FALSE;
            }

            //The login() function requires an unencrypted password to validate the user
            if($is_password_encrypted == FALSE)
            {
                //Check if the supplied credentials match
                if($this->login($email, $password))
                {
                    $stmt = $this->mysqli->prepare("DELETE FROM `$this->user_table` WHERE `$this->email_col` = ?");
                    $stmt->bind_param('s', $email);
                    $stmt->execute();

                    return TRUE;
                }
            }
            //If this function was called from inside this class (for example, to rollback user changes), then the supplied password may already be encrypted
            //If the password is already encrypted, then we don't need to use the login() function
            else
            {
                //Delete the user if the supplied credentials match
                $stmt = $this->mysqli->prepare("DELETE FROM `$this->user_table` WHERE `$this->email_col` = ? AND `$this->password_col` = ?");
                $stmt->bind_param('ss', $email, $password);
                $stmt->execute();

                return TRUE;
            }
        }

        public function logout()
        {
            session_start();
            $_SESSION = array();
            session_destroy();

            return TRUE;
        }

        public function set_password($new_password, $emailed_hash)
        {
            if(!($new_password && $emailed_hash))
            {
                return FALSE;
            }

            if(!$this->hash_exists($emailed_hash))
            {
                return FALSE;
            }

            $new_password = $this->encrypt_password($new_password);

            //Insert the password, and delete the emailed_hash column since we want the link containing the hash to be dead after 1 use.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->password_col` = ?, `$this->emailed_hash_col` = '' WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('ss', $new_password, $emailed_hash);
            $stmt->execute();

            if($this->mysqli->error)
            {
                $this->log->logCrit('Error setting new password', $this->mysqli->error);
                return FALSE;
            }

            return TRUE;
        }

        public function activate_account($hash)
        {
            if(!$hash)
            {
                return FALSE;
            }

            $empty = '';

            //Update the 'Activated' field to TRUE, and delete the emailed_hash_column.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->activated_col` = '1', `$this->emailed_hash_col` = ? WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('ss', $empty, $hash);
            $stmt->execute();

            if(!$this->mysqli->error)
            {
                return TRUE;
            }
            else
            {
                $this->log->logCrit('Error setting Account Activated to true', $this->mysqli->error);
                return FALSE;
            }
        }

        private function encrypt_password($password)
        {
            $encrypted_password = password_hash($password, PASSWORD_BCRYPT, ["cost" => $this->encryption_rounds]);

            return $encrypted_password;
        }

        //Generate a unique 32 character long hash. Called by create_user() and reset_password().
        private function generate_random_hash()
        {
            //Create a $random_hash and check if it already exists in the database, and if yes, then generate another one until a unique hash is found.
            $stmt = $this->mysqli->prepare("SELECT `$this->emailed_hash_col` FROM `$this->user_table` WHERE `$this->emailed_hash_col` = ? LIMIT 1");
            do
            {
                $random_hash = md5(uniqid(rand(), true));
                $stmt->bind_param('s', $random_hash);
                $stmt->execute();
                $stmt->bind_result($emailed_hash_col);
                $stmt->fetch();
            }
            while($emailed_hash_col == $random_hash);

            if(!$this->mysqli->error)
            {
                return $random_hash;
            }
        }

        //Generate email, inheriting the values of constants from Configuration.php. The $type of email generated is either a registration confirmation or a password reset.
        private function send_email($email, $type, $random_hash)
        {
            $transport = Swift_SmtpTransport::newInstance(SMTP_SERVER, PORT, SECURITY)
				->setUsername(SMTP_USERNAME)
				->setPassword(SMTP_PASSWORD);

			$swift = Swift_Mailer::newInstance($transport);

            //This try/catch block only exists because there is no way to turn off exception handling in SwiftMailer. Therefore we have to catch any exception SwiftMailer throws
            //to prevent the user seeing an Uncaught Exception error.
            try
            {
                if($type == 'registration')
                {
                    $message = Swift_Message::newInstance()
                    ->setFrom(SENDER_ADDRESS)
                    ->setTo($email)
                    ->setSubject(REGISTRATION_SUBJECT)
                    ->setBody(REGISTRATION_BODY, 'text/html')
                    ->setReplyTo(REPLY_TO);
                }
                elseif($type == 'reset')
                {
                    $message = Swift_Message::newInstance()
                    ->setFrom(SENDER_ADDRESS)
                    ->setTo($email)
                    ->setSubject(RESET_SUBJECT)
                    ->setBody(RESET_BODY, 'text/html')
                    ->setReplyTo(REPLY_TO);
                }
                else
                {
                    return FALSE;
                }
            }
            catch(Exception $e)
            {
                return FALSE;
            }

            //Replace the $random_hash placeholder in the Body's URL with the actual hash.
            $message->setBody(str_replace('$random_hash', $random_hash, $message->getBody(), $counter));

            if(($counter) != 1)
            {
                $this->log->logCrit('The $random_hash variable from the URL in the email bodies was modified/removed');
            }

            if(!$swift->send($message))
            {
                if($type == 'reset')
                {
                    $this->log->logCrit('A password reset email failed to send');
                    return FALSE;
                }
                elseif($type == 'registration')
                {
                    $this->log->logCrit('An account activation email failed to send');
                    return FALSE;
                }
            }
            else
            {
                return TRUE;
            }
        }
    }

?>
