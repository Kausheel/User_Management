<?php
	
	class Database{
		
		function __construct() {
			
			define('HOST', 'localhost');
			define('DB_USERNAME', 'my_username');
			define('DB_PASSWORD', 'my_password');
			define('DATABASE_NAME', 'my_database');
			
		}
		
		function connect() {
			
			$mysqli = new mysqli(HOST, DB_USERNAME, DB_PASSWORD, DATABASE_NAME);
			return $mysqli;
			
		}

	}

?>