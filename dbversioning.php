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
			$this->argumentsList 	= array("init");
			$this->optionsList 		= array("-p");

			if (!isset($arguments[1])) {
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

	public function initDataVersioning($arguments)
	{
		if (!defined('PDO::ATTR_DRIVER_NAME')) {
			throw new Exception("PDO driver unavailable", 1);
		}


		// Required dsn informations
		$host 	= "localhost";
		$dbname = "";
		$user 	= "root";
		$pass 	= "";
		$port 	= "3306";

		// Optionals database parameter
		$table 	= false;
		$fPath 	= "dbv";

		$h 		= array_search('-h', $arguments);
		$d 		= array_search('-d', $arguments);
		$u 		= array_search('-u', $arguments);
		$p 		= array_search('-p', $arguments);

		$t 		= array_search('-t', $arguments);
		$path 	= array_search('--path', $arguments);

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

		// Handle the -p password option
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

		$dsn = "mysql:host=$host;dbname=$dbname;port=$port";
		$driverOptions = array(
		   PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
		   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		);
		$this->pdo = new PDO($dsn, $user, $pass, $driverOptions);

		$this->printContent(PHP_EOL . "[init] Database connection established", "light_cyan");
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

		// var_dump($database, $table);
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
			$this->printContent("[init] Creating records files", "light_cyan");

			if (!$table) {
				foreach ($result as $key => $value) {
					$tName = $value['Tables_in_prestashop'];

					$queryRecords = "SELECT * FROM $tName";

					$req = $pdo->prepare($queryRecords);
					$req->execute();
					$records = $req->fetchAll();

					file_put_contents($folderPath . "/data/records/$tName.json", json_encode($records));
				}
			}
			$this->printContent("[init] Successfully create records files", "light_cyan");
			
			$this->printContent("[Tip] Run \"diff\" command to generate revision files", "brown");
		}
	}

	public function diffRecords($arguments)
	{
		# code...
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
EOH;

    	$optsTitle 	= "Options:";
		$options 	= <<< EOH
  -v 	(DBVersioning version)
EOH;

    	$cmdTitle 	= "The following commands are currently supported:";
    	$commands	= <<< EOH
  about 	(Short information about DBVersioning)
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