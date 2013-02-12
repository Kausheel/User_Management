<?php

    include('includes/encrypt.class.php');
    include('includes/swift/lib/swift_required.php');
    include('configuration.php');
    include('includes/klogger.class.php');

    class Authenticate
    {
        //The database settings and table structures are inherited from the Configuration.php file.
        private $db_host = DB_HOST;
        private $db_username = DB_USERNAME;
        private $db_password = DB_PASSWORD;
        private $db_name = DB_NAME;
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

            $this->mysqli = new mysqli($this->db_host, $this->db_username, $this->db_password, $this->db_name);

            if($this->mysqli->connect_error)
            {
                $this->log->logFatal('The database connection failed', $this->mysqli->connect_error);
            }
        }

        public function create_user($email, $password)
        {
            if(!($email && $password))
            {
                return FALSE;
            }

            if($this->is_registered($email))
            {
                return FALSE;
            }

            $password = $this->encrypt_password($password);

            //To be sent in the email confirmation link.
            $random_hash = $this->generate_random_hash();

            //Add the email, password, and random hash to the database. The $activated_col should be 0 by default, and 1 once the emailed link is clicked.
            $stmt = $this->mysqli->prepare("INSERT INTO `$this->user_table`(`$this->email_col`, `$this->password_col`, `$this->emailed_hash_col`, `$this->activated_col`) VALUES(?, ?, ?, 0)");
            $stmt->bind_param('sss', $email, $password, $random_hash);
            $stmt->execute();

            if($this->mysqli->error)
            {
                $this->log->logFatal('Failed to create user', $this->mysqli->error);
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
                $this->delete_user($email, $password);

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
                $this->log->logFatal('Failed to check if the user is_registered', $this->mysqli->error);
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
                $this->log->logFatal('Failed to check if the user is_activated', $this->mysqli->error);
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

            //Fetch the password and emailed_hash from database by matching the email. If there is no result, the $email does not exist.
            $stmt = $this->mysqli->prepare("SELECT `$this->password_col`, `$this->emailed_hash_col`, `$this->activated_col` FROM `$this->user_table` WHERE `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($stored_password, $emailed_hash, $activated);
            $stmt->fetch();

            if($this->mysqli->error)
            {
                $this->log->logFatal('Login failed', $this->mysqli->error);
                return FALSE;
            }

            //Check if the password hashes match
            $encrypt = new Encrypt(12, FALSE);
            if(!$encrypt->check_password($password, $stored_password))
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
                $this->log->logFatal('Failed to change password', $this->mysqli->error);
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

            $random_hash = $this->generate_random_hash();

            //Insert the $random_hash into the database.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->emailed_hash_col` = ? WHERE `$this->email_col` = ?");
            $stmt->bind_param('ss', $random_hash, $email);
            $stmt->execute();

            if($this->mysqli->error)
            {
                $this->log->logFatal('Error inserting $random_hash', $this->mysqli->error);
                return FALSE;
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

        //Check if a user has requested a password reset, but not yet chosen a new password.
        public function is_reset($email)
        {
            if(!$email)
            {
                return FALSE;
            }

            //We need to check if the user has activated their account, because if they haven't, the emailed_hash_col will hold an account activation hash,
            //not a reset password hash, therefore the user is NOT trying to reset their password.
            $stmt = $this->mysqli->prepare("SELECT `$this->emailed_hash_col` FROM `$this->user_table` WHERE `$this->activated_col` = 1 AND `$this->email_col` = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($emailed_hash);
            $stmt->fetch();

            if($this->mysqli->error)
            {
                $this->log->logFatal('Failed to check if the user is_reset', $this->mysqli->error);
                return FALSE;
            }

            //If the $emailed_hash exists in the database, the user has reset their password.
            if($emailed_hash)
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }

        public function hash_exists($hash)
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
                $this->log->logFatal('Failed to check if hash exists', $this->mysqli->error);
                return FALSE;
            }

            if($hash == $result)
            {
                return TRUE;
            }
        }

        public function delete_user($email, $password)
        {
            if(!($email && $password))
            {
                return FALSE;
            }

            $stmt = $this->mysqli->prepare("DELETE FROM `$this->user_table` WHERE `$this->email_col` = ? AND `$this->password_col` = ?");
            $stmt->bind_param('ss', $email, $password);
            $stmt->execute();

            return TRUE;
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

            $new_password = $this->encrypt_password($new_password);

            //Insert the password, and delete the emailed_hash column since we want the link containing the hash to be dead after 1 use.
            $stmt = $this->mysqli->prepare("UPDATE `$this->user_table` SET `$this->password_col` = ?, `$this->emailed_hash_col` = '' WHERE `$this->emailed_hash_col` = ?");
            $stmt->bind_param('ss', $new_password, $emailed_hash);
            $stmt->execute();

            if($this->mysqli->error)
            {
                $this->log->logFatal('Error setting new password', $this->mysqli->error);
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
                $this->log->logFatal('Error setting Account Activated to true', $this->mysqli->error);
                return FALSE;
            }
        }

        private function encrypt_password($password)
        {
            $encrypt = new Encrypt($this->encryption_rounds, FALSE);
            $password = $encrypt->hash_password($password);

            return $password;
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
                    ->setBody(REGISTRATION_BODY);
                }
                elseif($type == 'reset')
                {
                    $message = Swift_Message::newInstance()
                    ->setFrom(SENDER_ADDRESS)
                    ->setTo($email)
                    ->setSubject(RESET_SUBJECT)
                    ->setBody(RESET_BODY);
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
                $this->log->logFatal('The $random_hash variable from the URL in the email bodies was modified/removed');
            }

            if(!$swift->send($message))
            {
                if($type == 'reset')
                {
                    $this->log->logFatal('A password reset email failed to send');
                    return FALSE;
                }
                elseif($type == 'registration')
                {
                    $this->log->logFatal('An account activation email failed to send');
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
