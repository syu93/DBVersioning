#!/usr/local/bin/php
<?php

class dbversioning {

	/**
	 * The DBVersioning version
	 */
	const VERSION = "0.0.1";

	/**
	 * Run and dispatches the DBVersioning program
	 * @param  Array $arguments this is just a representation of $argv (the parameters which were used to launch the program)
	 */
	public function run($arguments)
	{
		try {
			if (!isset($arguments[1]) || $arguments[1] == "-h") {
				$this->printHelp();
				exit;
			}
			if ($arguments[1] == "-v") {
				$this->printVersion();
				return;
			}
			switch ($arguments[1]) {
				// Extra case for version
				case 'about':
					$this->printAbout();
					break;
				case 'init':
					$this->initDataVersioning($arguments);
					break;
				case 'update':
					$this->updateDataVersioning($arguments);
					break;
				case 'diff':
					$this->diffDataVersioning($arguments);
					break;
				default:
					throw new Exception('command does not exist');
					break;
			}
		} catch (Exception $e) {
            $sMessage = $e->getMessage();
            $sCowsay = @file_get_contents('http://cowsay.morecode.org/say?message=' . urlencode("Error : ".$sMessage) . '&format=text', false, $rCtx);
            if ($sCowsay) {
                $this->printContent($sCowsay, 'yellow');
                $this->printHelp();
            } else {
                $error =  '-----' . PHP_EOL;
                $error .= 'An error occured:' . PHP_EOL;
                $error .= $sMessage . PHP_EOL;
                $error .= '-----';
            	$this->printContent($error);
            }
		}
	}

	public function getConnection($host = "localhost", $dbname = "", $user = "root", $pass = "", $port = "3306", $fPath = "dbv")
	{
		if (!defined('PDO::ATTR_DRIVER_NAME')) {
			throw new Exception("PDO driver unavailable", 1);
		}

		$configExist = file_exists($fPath . "/dbv.json");

		if ($configExist) {
			$config = json_decode(file_get_contents($fPath . "/dbv.json"), true);
			/**
			 * $host
			 * $dbname
			 * $user
			 * $pass
			 * $port
			 * $fPath
			 */
			extract($config, EXTR_OVERWRITE);
		}

		$dsn = "mysql:host=$host;dbname=$dbname;port=$port";
		$driverOptions = array(
		   PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
		   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		);
		$this->pdo = new PDO($dsn, $user, $pass, $driverOptions);

		$this->printContent("[init] Database connection established", "light_cyan");

		$this->host  	= $host;
		$this->dbname  	= $dbname;
		$this->user  	= $user;
		$this->pass  	= $pass;
		$this->port  	= $port;
		$this->fPath  	= $fPath;

		if (!$configExist) {
			$config = array(
				"host" 	=> $host,
				"dbname" => $dbname,
				"port" => $port,
				"user" => $user,
				"pass" => $pass,
				);
			// Require PHP =^5.4
			file_put_contents($fPath . "/dbv.json", json_encode($config, JSON_PRETTY_PRINT));
			$this->printContent("[init] Config file created", "light_cyan");
		}
	}

	public function initDataVersioning($arguments)
	{
		// Required dsn informations
		$host 	= "localhost";
		$dbname = "";
		$user 	= "root";
		$pass 	= "";
		$port 	= "3306";

		// Optionals database parameter
		$table 	= false;
		$fPath 	= "dbv";

		$H 		= array_search('-H', $arguments);
		
		$d 		= array_search('-d', $arguments);
		$h 		= array_search('-h', $arguments);
		$u 		= array_search('-u', $arguments);
		$p 		= array_search('-p', $arguments);

		$t 		= array_search('-t', $arguments);
		$T 		= array_search('-T', $arguments);
		$path 	= array_search('--path', $arguments);

		// handle the -H option : Help
		if ($H) {
			$this->printCommandHelp("init");
			return;
		}

		// Handle the -d dbname option
		if ($d) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$d +1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$d +1]);
			} else {
				throw new Exception("Syntax error with argument -d \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$dbname = trim($arguments[$d + 1]);
			} else {
				throw new Exception("Syntax error with argument -d \n Refer help -h for more details", 1);
			}
		}

		// Handle the -h option
		if ($h) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$h + 1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$h +1]);
			} else {
				throw new Exception("Syntax error with argument -h \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$host = trim($arguments[$h + 1]);
			} else {
				throw new Exception("Syntax error with argument -h \n Refer help -h for more details", 1);
			}
		}

		// handle the -u option
		if ($u) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$u + 1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$u +1]);
			} else {
				throw new Exception("Syntax error with argument -u \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$user = trim($arguments[$u + 1]);
			} else {
				throw new Exception("Syntax error with argument -u \n Refer help -h for more details", 1);
			}
		}

		// handle the -t option
		if ($t) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$t + 1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$t +1]);
			} else {
				throw new Exception("Syntax error with argument -t \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$table = trim($arguments[$t + 1]);
			} else {
				throw new Exception("Syntax error with argument -t \n Refer help -h for more details", 1);
			}
		}

		// handle the -T option
		// FIXME : Handle multiple table export
		if ($T) {
			$table = array();
			$len = count($arguments);
			$tTableId = $T+1;

			$y = 0;
			for ($i=$tTableId; $i < $len; $i++) {
				$nextIsOpt = 0;
				$isNotLast = isset($arguments[$T + 1]);
				if ($isNotLast) {
					$nextIsOpt = preg_match("/-\w/", $arguments[$tTableId + $y]);
				} else {
					throw new Exception("Syntax error with argument -T \n Refer help -h for more details", 1);
				}
				if ($nextIsOpt === 0) {
					$table[] = trim($arguments[$tTableId + $y]);
				}
				$y++;
			}
		}

		// handle the --path option
		if ($path) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$path + 1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$path +1]);
			} else {
				throw new Exception("Syntax error with argument --path \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$fPath = trim($arguments[$path + 1]);
			} else {
				throw new Exception("Syntax error with argument --path \n Refer help -h for more details", 1);
			}
		}

		// Handle the -p password option
		// Handle as last argument because of special behaviour
		if ($p) {
			// Check if the string following the -p option is not another option
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$p +1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$p +1]);
			}

			// var_dump($nextIsOpt, $isNotLast);

			// the following is not an option
			if ($nextIsOpt === 0 && !$isNotLast) {
				$pass = $this->waitForInput("Enter password :", true);
			} 
			// Check if -p is not the last but a password entered
			else if ($nextIsOpt === 0 && $isNotLast) {
				$pass = $arguments[$p + 1];
			} else if ($nextIsOpt === 1) {
				$pass = $this->waitForInput("Enter password :", true);
			}
		}

		$this->printContent("");

		$this->getConnection($host, $dbname, $user, $pass, $port, $fPath);

		$this->exportRecords($dbname, $table, $fPath);
	}

	/**
	 * Read the database and export records
	 * @param  array $arguments The arguments passed to the command
	 * @return void
	 */
	public function updateDataVersioning($arguments)
	{
		$table 	= false;
		$fPath 	= "dbv";

		$H 		= array_search('-H', $arguments);

		$t 		= array_search('-t', $arguments);
		$T 		= array_search('-T', $arguments);
		$path 	= array_search('--path', $arguments);

		// handle the -H option : Help
		if ($H) {
			$this->printCommandHelp("init");
			return;
		}


		// handle the -t option
		if ($t) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$t + 1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$t +1]);
			} else {
				throw new Exception("Syntax error with argument -t \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$table = trim($arguments[$t + 1]);
			} else {
				throw new Exception("Syntax error with argument -t \n Refer help -h for more details", 1);
			}
		}

		// handle the -T option
		if ($T) {
			$table = array();
			$len = count($arguments);
			$tTableId = $T+1;

			$y = 0;
			for ($i=$tTableId; $i < $len; $i++) {
				$nextIsOpt = 0;
				$isNotLast = isset($arguments[$T + 1]);
				if ($isNotLast) {
					$nextIsOpt = preg_match("/-\w/", $arguments[$tTableId + $y]);
				} else {
					throw new Exception("Syntax error with argument -T \n Refer help -h for more details", 1);
				}
				if ($nextIsOpt === 0) {
					$table[] = trim($arguments[$tTableId + $y]);
				}
				$y++;
			}
		}

		// handle the --path option
		if ($path) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$path + 1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$path +1]);
			} else {
				throw new Exception("Syntax error with argument --path \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$fPath = trim($arguments[$path + 1]);
			} else {
				throw new Exception("Syntax error with argument --path \n Refer help -h for more details", 1);
			}
		}

		// Connection must have already been initialized
		$this->getConnection();
		if (isset($this->dbname)) {
			$dbname = $this->dbname;
		}

		$this->exportRecords($dbname, $table, $fPath);
	}

	/**
	 * Read the database and export records of each table in a json file
	 * @param  string  $database   The database name to  be used
	 * @param  string  $table      Specify the table to export if not false
	 * @param  string  $folderPath The dbv installation path
	 * @return void
	 */
	public function exportRecords($database, $table = false, $folderPath = "dbv")
	{
		$pdo 			= $this->pdo;
		$queryStructure = "";
		$result			= array();

		if (!$table) {
			$queryStructure = "SHOW TABLES FROM $database;";

			$req = $pdo->prepare($queryStructure);
			$req->execute();
			$result = $req->fetchAll();
		}

		if (!file_exists($folderPath)) {
			$this->printContent("[init] Creating dbv folder", "light_cyan");
			mkdir($folderPath);
		}
		if (!file_exists($folderPath . "/data")) {
			$this->printContent("[init] Creating data folder", "light_cyan");
			mkdir($folderPath . "/data");
		}
		if (!file_exists($folderPath . "/data/records")) {
			$this->printContent("[init] Creating records folder", "light_cyan");
			mkdir("dbv/data/records");
		}

		if (file_exists($folderPath . "/data/records")) {
			$this->printContent("[init] Reading records in records folder", "light_cyan");


			if (!$table) {
				$countTalbe = count($result);
				$this->printContent("[init] $countTalbe tables found in $database", "light_cyan");

				$this->printContent("[init] Creating records files", "light_cyan");
				foreach ($result as $key => $value) {
					$tName = $value['Tables_in_prestashop'];

					$queryRecords = "SELECT * FROM $tName";

					$req = $pdo->prepare($queryRecords);
					$req->execute();
					$records = $req->fetchAll();

					file_put_contents($folderPath . "/data/records/$tName.json", json_encode($records));
				}
			} else if (!empty($table)) {
				$this->printContent("[init] Creating records files for table ", "light_cyan", null, false);
				$this->printContent($table, "light_green");

				$tName = $table;

				$queryRecords = "SELECT * FROM $tName";

				$req = $pdo->prepare($queryRecords);
				$req->execute();
				$records = $req->fetchAll();

				file_put_contents($folderPath . "/data/records/$tName.json", json_encode($records));
			}
			$this->printContent("[init] Successfully create records files", "light_cyan");
			
			$this->printContent("[Tip] Run \"diff\" command to generate revision files", "brown");
		}
	}

	/**
	 * Read the database and diff with the saved records
	 * @param  array  $arguments The arguments passed to the command
	 * @return void
	 */
	public function diffDataVersioning($arguments)
	{

		$table  = false;
		$length = 3;
		$fPath 	= "dbv";
		$tSkip  = array();

		$H 		= array_search('-H', $arguments);
		
		$t 		= array_search('-t', $arguments);
		$T 		= array_search('-T', $arguments);
		$l 		= array_search('-l', $arguments);

		$path 	= array_search('--path', $arguments);
		$skip 	= array_search('--skip', $arguments);


		// handle the -t option
		if ($t) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$t + 1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$t +1]);
			} else {
				throw new Exception("Syntax error with argument -t \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$table = trim($arguments[$t + 1]);
			} else {
				throw new Exception("Syntax error with argument -t \n Refer help -h for more details", 1);
			}
		}

		// handle the -T option
		if ($T) {
			$table = array();
			$len = count($arguments);
			$tTableId = $T+1;

			$y = 0;
			for ($i=$tTableId; $i < $len; $i++) {
				$nextIsOpt = 0;
				$isNotLast = isset($arguments[$T + 1]);
				if ($isNotLast) {
					$nextIsOpt = preg_match("/-\w/", $arguments[$tTableId + $y]);
				} else {
					throw new Exception("Syntax error with argument -T \n Refer help -h for more details", 1);
				}
				if ($nextIsOpt === 0) {
					$table[] = trim($arguments[$tTableId + $y]);
				}
				$y++;
			}
		}

		// handle the -l option
		if ($l) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$l + 1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$l +1]);
			} else {
				throw new Exception("Syntax error with argument -l \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$length = trim($arguments[$l + 1]);
			} else {
				throw new Exception("Syntax error with argument -l \n Refer help -h for more details", 1);
			}
		}

		// handle the --path option
		if ($path) {
			$nextIsOpt = 0;
			$isNotLast = isset($arguments[$path + 1]);
			if ($isNotLast) {
				$nextIsOpt = preg_match("/-\w/", $arguments[$path +1]);
			} else {
				throw new Exception("Syntax error with argument --path \n Refer help -h for more details", 1);
			}

			if ($nextIsOpt === 0) {
				$fPath = trim($arguments[$path + 1]);
			} else {
				throw new Exception("Syntax error with argument --path \n Refer help -h for more details", 1);
			}
		}

		// handle the --skip option
		if ($skip) {
			$len = count($arguments);
			$tSkipId = $skip+1;

			$y = 0;
			for ($i=$tSkipId; $i < $len; $i++) {
				$nextIsOpt = 0;
				$isNotLast = isset($arguments[$skip + 1]);
				if ($isNotLast) {
					$nextIsOpt = preg_match("/-\w/", $arguments[$tSkipId + $y]);
				} else {
					throw new Exception("Syntax error with argument --skip \n Refer help -h for more details", 1);
				}
				if ($nextIsOpt === 0) {
					$tSkip[] = trim($arguments[$tSkipId + $y]);
				}
				$y++;
			}
		}

		$noRecords = $this->_emptyDir($fPath . "/data/records");
		$configExist = file_exists($fPath . "/dbv.json");
		if ($noRecords) {
			throw new Exception("Run init first to create records", 1);
		} else if (!$configExist) {
			throw new Exception("Missing config file \n Run init to create it \n or use --path to indicate the right path", 1);
			
		}
		$this->getConnection();
		
		$dbname = $this->dbname;
		$fPath 	= $this->fPath;
		$this->diffRecords($dbname, $table, $fPath, $length, $tSkip);
	}

	/**
	 * Get all informations from the database to prepare the diff
	 * @param  string  $database   The database name
	 * @param  string  $table      [Optional] The table name if specify. Default: false
	 * @param  string  $folderPath [Optional] The dbv folder path name. Default: dbv
	 * @return void
	 */
	public function diffRecords($database, $table = false, $folderPath = "dbv", $length = 3, $tSkip)
	{
		$pdo 			= $this->pdo;
		$queryStructure = "";
		$result			= array();

		$last = true;

		// If no tables were specified
		// Get all tables from the database
		if (!$table) {
			$queryStructure = "SHOW TABLES FROM $database;";
			$req = $pdo->prepare($queryStructure);
			$req->execute();
			$result = $req->fetchAll();
		}

		// Initialize the diff couter
		$this->hasDiff = 0;

		// If specified skip tables during the loop
		$skiping = false;
		if (!$table) {
			$countTalbe = count($result);
			$this->printContent("[diff] $countTalbe tables found in : $database", "light_cyan");
			
			// If it's the last row
			$last = false;
			// Loop over all tables
			foreach ($result as $key => $value) {
				// If the last
				if ($key == count($result) -1) {
					$last = true;
				}
				// Get the table name
				$tName = $value['Tables_in_' . $database];

				// Do skip if table is in list
				if (in_array($tName, $tSkip)) {
					$skiping = true;
					continue;
				}

				// Get the path to the reecord file
				$filePath = $folderPath . "/data/records/" . $tName . ".json";

				if (!file_exists($filePath)) {
					// FIXME : Gracefull
					throw new Exception("Record not fund \n Try run init to export this record", 1);
				}

				// Get the current database records
				$queryRecords = "SELECT * FROM $tName";

				$req = $pdo->prepare($queryRecords);
				$req->execute();
				$records = $req->fetchAll();

				// Load registered content records
				$registeredRecord = json_decode(file_get_contents($filePath), true);

				// Call the method to do the diff
				$this->operateDiff($tName, $registeredRecord, $records, $length, $last);	
			}

			if ($skiping) {
				$this->printContent("[diff] Skiping : " . implode(", ", $tSkip), "light_cyan");
			}
		} else if (is_array($table)) {
			$last = false;
			foreach ($table as $key => $tName) {
				if ($key == count($table) -1) {
					$last = true;
				}
				$filePath = $folderPath . "/data/records/" . $tName . ".json";

				if (!file_exists($filePath)) {
					throw new Exception("Record not fund \n Try run init to export this record", 1);
				}

				$queryRecords = "SELECT * FROM $tName";

				$req = $pdo->prepare($queryRecords);
				$req->execute();
				$records = $req->fetchAll();

				$registeredRecord = json_decode(file_get_contents($filePath), true);

				$this->operateDiff($tName, $registeredRecord, $records, $length, $last);
			}
		} else {
			$tName = $table;

			$filePath = $folderPath . "/data/records/" . $tName . ".json";

			if (!file_exists($filePath)) {
				throw new Exception("Record not fund \n Try run init to export this record", 1);
			}

			$queryRecords = "SELECT * FROM $tName";

			$req = $pdo->prepare($queryRecords);
			$req->execute();
			$records = $req->fetchAll();

			$registeredRecord = json_decode(file_get_contents($filePath), true);

			$this->operateDiff($tName, $registeredRecord, $records, $length, $last);
		}
	}

	/**
	 * Operate the diffs and decide of the type
	 * @param  string $table            The table name
	 * @param  array  $registeredRecord The registered records
	 * @param  array  $records          The records from the database
	 * @param  string $length The length of revision number
	 * @return void
	 */
	public function operateDiff($table, $registeredRecord, $records, $length = 3, $last = true)
	{
		// Loop
		$pdo 					= $this->pdo;
		$countRecords 			= count($records);
		$countregisteredRecord 	= count($registeredRecord);
		$primary 				= false;

		// Get the table primary key
		$q = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'";
		$req = $pdo->query($q);
		$res = $req->fetch();
		if (!empty($res)) {
			$primary = $res["Column_name"];
		}

		// If there is any different in the length of the records
		if ($countRecords != $countregisteredRecord) {
			$this->hasDiff++;
			// Record added or removed
			if ($countRecords < $countregisteredRecord) {
				// Record added
				// var_dump("Record added");
				$diff = array(null);
			} else {
				// Record removed
				// var_dump("Record removed");
				$diff = array(null);
			}
		} else {
			// record number not changed
			for ($i=0; $i < $countRecords; $i++) {
				// Proceed the diff
				$diff = array_diff($records[$i], $registeredRecord[$i]);

				$pId = $records[$i][$primary];

				// If there a diff between records
				if (!empty($diff)) {
					$this->hasDiff++;
					// Record changed
					// var_dump("Record changed");
					$this->_createMigrationFile("update", $table, $primary, $pId, $diff, $length);
				}
			}
		}
		
		$migrationFilePath 	= $this->fPath . "/data/meta/migration";
		$migrationFile 		= 0;

		// Check the migration file
		if (!file_exists($this->fPath . "/data/meta")) {
			mkdir($this->fPath . "/data/meta");
		}
		
		if (file_exists($migrationFilePath)) {
			$migrationFile = file_get_contents($migrationFilePath);
		}

		if ($last && $this->hasDiff > 0) {
			$this->printContent("[diff] $this->hasDiff diffs were found between records and database", "light_cyan");
		}

		// Do not writh migration file untile the end
		if ($last && $this->hasDiff > 0) {
			$migrationNumber = str_pad($migrationFile + 1, $length, '0', STR_PAD_LEFT);
			file_put_contents($migrationFilePath, $migrationNumber);
			$this->printContent("[diff] Writing revison file", "light_cyan");
			$this->printContent("[diff] Revision number : $migrationNumber", "light_green");
		} else if ($last && $this->hasDiff == 0) {
			// Fixme : get the real empty case
			$this->printContent("[diff] No diffs found in your records", "yellow");
		}
	}

	/**
	 * Generate the migration sql file for revisions
	 * @param  string  $type   The type of migration
	 * @param  string  $table  The table to migrate
	 * @param  string  $pkey   the table primary key
	 * @param  string  $id     The record ID to
	 * @param  array   $diff   The array of differences
	 * @param  integer $length [Optional] The length of revision number. Default: 3
	 * @return void
	 */
	private function _createMigrationFile($type, $table, $pkey, $id, $diff, $length = 3)
	{
		$version = self::VERSION;

		switch ($type) {
			case 'update':
				$paramsPh = [];
				foreach ($diff as $key => $value) {
					$paramsPh[] = " $key = \"$value\"";
				}
				$implodedParams = implode(' AND', $paramsPh);

				$query = <<< EOH
UPDATE $table 
SET $implodedParams
WHERE $pkey = "$id"
EOH;
				break;
		}

		$migrationPath 		= $this->fPath . "/data/revisions/";
		$migrationFilePath 	= $this->fPath . "/data/meta/migration";
		$migrationFile 		= 0;

		// Check the migration file
		if (file_exists($migrationFilePath)) {
			$migrationFile = file_get_contents($migrationFilePath);
		}

		$migrationNumber = str_pad($migrationFile + 1, $length, '0', STR_PAD_LEFT);

		if (!file_exists($migrationPath)) {
			mkdir($migrationPath);
		}

		if (!file_exists($migrationPath . $migrationNumber)) {
			mkdir($migrationPath . $migrationNumber);
		}

		$migration = <<< EOH
-- =============================================
-- DBVersioning v$version
-- Table : $table
-- Migration script
-- =============================================

$query
EOH;
		// Create the resition file
		file_put_contents($migrationPath . $migrationNumber . "/" . $table . ".sql", $migration);
	}

	/**
	 * Interative shell wait for user input with or without prompt
	 * @param  String  $message The prompt message to the user
	 * @param  boolean $silent  If the user input should be hidden
	 * @return String           the user input
	 */
	public function waitForInput($message, $silent = false)
	{
		$this->printContent($message, 'green');

		if ($silent) {
			return $this->_pronSilent();
		}

		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		// Get the input
		$input = trim($line);
		fclose($handle);
		return $input;
	}

	/**
	 * Test if a folder is empty
	 * @param  string $dirPath path to folder to test
	 * @return boolean         If the folder is empty or not
	 */
	private function _emptyDir($dirPath = '')
	{
		if (!is_readable($dirPath)){
			return NULL; 
		}

		$handle = opendir($dirPath);

		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				return false;
			}
		}
		return true;
	}

	/**
	 * Interactively prompts for input without echoing to the terminal.
	 * Requires a bash shell or Windows and won't work with
	 * safe_mode settings (Uses `shell_exec`)
	 */
	private function _pronSilent() {
	    // Get current style
	    $oldStyle = shell_exec('stty -g');

        shell_exec('stty -echo');
        $password = rtrim(fgets(STDIN), "\n");

	    // Reset old style
	    shell_exec('stty ' . $oldStyle);

	    // Return the password
	    return $password;
	}

	public function printAbout()
	{
		# code...
	}

	/**
	 * Print the DBVersioning version
	 * @return void
	 */
	public function printVersion()
	{
		$this->printContent('DBVersioning ', 'green', null, false);
		$this->printContent('version ', null, null, false);
		$this->printContent(self::VERSION, 'yellow');
	}

	/**
	 * Print content to the user with colors
	 * @param  string  $content content to be printed
	 * @param  string  $fcolor  Foreground color name
	 * @param  string  $bcolor  Background color name
	 * @param  boolean $eol     If the console should return to the line
	 * @return void
	 */
	public function printContent($content = '', $fcolor = null, $bcolor = null, $eol = true)
	{
		$colors = new Colors();
		$contents = $content;
		if ($eol) {
			$contents .= PHP_EOL;
		}

		echo $colors->getColoredString($contents, $fcolor, $bcolor);
	}

	private function _getContent($content = '', $fcolor = null, $bcolor = null)
	{
		$colors = new Colors();
		$contents = $content;

		return $colors->getColoredString($contents, $fcolor, $bcolor) . " ";
	}

	public function printCommandHelp($cmd)
	{
		switch ($cmd) {
			case 'about':
				# code...
				break;
			case 'init':
		    	$usageTitle = "Usage:";
		    	$usage 		= <<< EOH
  init [options]
EOH;

		    	$optsTitle 	= "Options:";
				$options 	= <<< EOH
  -d 		Database name.
  -h 		Server host name.
  -u 		Database user.
  -p 		Database password.
  -t 		[optional] the table to be exported.
  --path 	[optional] The dbv folder path. Default: dbv
EOH;

		    	$helpTitle 	= "Help:";
		    	$helpStart 	= "  The ";
		    	$cmdName 	= "init ";
		    	$help 		= "command initialize DBVersioning by reading and saving database records
  in the 'dbv/data/records/";

				$this->printContent($usageTitle, 'yellow');
				$this->printContent($usage . PHP_EOL);
				$this->printContent($optsTitle, 'yellow');
				$this->printContent($options . PHP_EOL, 'green');
				$this->printContent($helpTitle, 'yellow');
				$this->printContent($helpStart, 'green', null, false);
				$this->printContent($cmdName, 'light_cyan', null, false);
				$this->printContent($help, 'green', null, false);
				$this->printContent("table_name","brown", null, false);
				$this->printContent(".json'.", "green");
				break;
			
			default:
				throw new Exception('command does not exist');
				break;
		}
	}

	/**
	 * Print help menu to the user
	 * @return void
	 */
	public function printHelp()
	{
		$name = "
    ____  ____ _    __               _             _            
   / __ \/ __ ) |  / /__  __________(_)___  ____  (_)___  ____ _
  / / / / __  | | / / _ \/ ___/ ___/ / __ \/ __ \/ / __ \/ __ `/
 / /_/ / /_/ /| |/ /  __/ /  (__  ) / /_/ / / / / / / / / /_/ / 
/_____/_____/ |___/\___/_/  /____/_/\____/_/ /_/_/_/ /_/\__, /  
                                                       /____/   version " . self::VERSION . PHP_EOL; 

        $name .= "DBVersioning - PHP-based database versioning" . PHP_EOL;


    	$usageTitle = "Usage:";
    	$usage 		= <<< EOH
  command [options] [arguments]
  command -H for help
EOH;

    	$optsTitle 	= "Options:";
		$options 	= <<< EOH
  -v 		Display the application version.
  -h, --help 	Display this help message.
EOH;

    	$cmdTitle 	= "The following commands are currently supported:";
    	$commands	= <<< EOH
  init 		Initialize DBVersioning by reading and saving records.
EOH;

		$this->printContent($name, 'green');
		$this->printContent($usageTitle, 'yellow');
		$this->printContent($usage . PHP_EOL);
		$this->printContent($optsTitle, 'yellow');
		$this->printContent($options . PHP_EOL, 'green');
		$this->printContent($cmdTitle, 'yellow');
		$this->printContent($commands, 'green');
	}
}

class Colors {
	private $foreground_colors = array();
	private $background_colors = array();

	public function __construct() 
	{
		// Set up shell colors
		$this->foreground_colors['black'] = '0;30';
		$this->foreground_colors['dark_gray'] = '1;30';
		$this->foreground_colors['blue'] = '0;34';
		$this->foreground_colors['light_blue'] = '1;34';
		$this->foreground_colors['green'] = '0;32';
		$this->foreground_colors['light_green'] = '1;32';
		$this->foreground_colors['cyan'] = '0;36';
		$this->foreground_colors['light_cyan'] = '1;36';
		$this->foreground_colors['red'] = '0;31';
		$this->foreground_colors['light_red'] = '1;31';
		$this->foreground_colors['purple'] = '0;35';
		$this->foreground_colors['light_purple'] = '1;35';
		$this->foreground_colors['brown'] = '0;33';
		$this->foreground_colors['yellow'] = '1;33';
		$this->foreground_colors['light_gray'] = '0;37';
		$this->foreground_colors['white'] = '1;37';

		$this->background_colors['black'] = '40';
		$this->background_colors['red'] = '41';
		$this->background_colors['green'] = '42';
		$this->background_colors['yellow'] = '43';
		$this->background_colors['blue'] = '44';
		$this->background_colors['magenta'] = '45';
		$this->background_colors['cyan'] = '46';
		$this->background_colors['light_gray'] = '47';
	}

	// Returns colored string
	public function getColoredString($string, $foreground_color = null, $background_color = null)
	{
		$colored_string = "";

		// Check if given foreground color found
		if (isset($this->foreground_colors[$foreground_color])) {
		$colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
		}
		// Check if given background color found
		if (isset($this->background_colors[$background_color])) {
		$colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
		}

		// Add string and end coloring
		$colored_string .=  $string . "\033[0m";

		return $colored_string;
	}

	// Returns all foreground color names
	public function getForegroundColors()
	{
		return array_keys($this->foreground_colors);
	}

	// Returns all background color names
	public function getBackgroundColors()
	{
		return array_keys($this->background_colors);
	}
}

$dbversioning = new dbversioning ();
$dbversioning->run($argv);