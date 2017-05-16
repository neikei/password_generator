<?php
/**
 * 
 * Password Generator
 * 
 * Supports generating passwords of unlimited length and selectable complexity.
 * 
 * @author nrekow
 * 
 */

// Die if request doesn't identify as AJAX request.
if (!isset($_POST['ajax']) || empty($_POST['ajax'])) {
	die('Missing parameter');
}


// This will hold the requested sets of characters.
$available_sets = '';

// Lowercase
if (isset($_POST['lowercase']) && !empty($_POST['lowercase'])) {
	$available_sets .= 'l';
}

// Uppercase
if (isset($_POST['uppercase']) && !empty($_POST['uppercase'])) {
	$available_sets .= 'u';
}

// Numbers
if (isset($_POST['numbers']) && !empty($_POST['numbers'])) {
	$available_sets .= 'd';
}

// Symbols
if (isset($_POST['symbols']) && !empty($_POST['symbols'])) {
	$available_sets .= 's';
}

// Similar chars
if (isset($_POST['similar']) && !empty($_POST['similar'])) {
	$available_sets .= 'x';
}

// Length
if (isset($_POST['length']) && !empty($_POST['length']) && is_numeric($_POST['length'])) {
	$length = $_POST['length'];
} else {
	$length = 16;
}

// Add dashes option
if (isset($_POST['dashes']) && !empty($_POST['dashes'])) {
	$add_dashes = true;
} else {
	$add_dashes = false;
}

// Custom characters
if (isset($_POST['custom']) && !empty($_POST['custom'])) {
	$custom = htmlspecialchars($_POST['custom'], ENT_QUOTES);
} else {
	$custom = '';
}

// Check if custom characters are mandatory
if (isset($_POST['mandatory']) && !empty($_POST['mandatory'])) {
	$mandatory = true;
} else {
	$mandatory = false;
}

// Define the characters of our sets.
$sets = array();
$lowercase = 'abcdefghijkmnopqrstuvwxyz';
$uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
$decimals  = '23456789';
$symbols   = '!@#$%&*?;:-+/()={[]}';
$similar   = 'lIO10';

$allSetChars = array($lowercase, $uppercase, $decimals, $symbols);

$available_sets = strtolower($available_sets); // Check sets as lowercase.

// Use lowercase characters
if (strpos($available_sets, 'l') !== false) {
	$sets[] = $lowercase;
}

// Use uppercase characters
if (strpos($available_sets, 'u') !== false) {
	$sets[] = $uppercase;
}

// Use decimals
if (strpos($available_sets, 'd') !== false) {
	$sets[] = $decimals;
}

// Use symbols
if (strpos($available_sets, 's') !== false) {
	$sets[] = $symbols;
}

// Use similar chars
if (strpos($available_sets, 'x') !== false) {
	$sets[] = $similar;
}

// Add all characters from chosen sets
$allChars = implode('', $sets);

// Add custom chars if they are not covered by chosen sets
if (!empty(($custom))) {
	// Check and add custom chars to chosen sets
	$tmpCustom = '';
	$customArray = str_split($custom);
	foreach ($customArray as $c) {
		if (strpos($allChars, $c) === false) {
			$tmpCustom .= $c;
		}
	}
	
	if (!empty($tmpCustom)) {
		$sets[] = $tmpCustom;
	}
	
	// Check and add custom chars to global sets
	$tmpCustom = '';
	$customArray= str_split($custom);
	$allSetCharsString = implode('', $allSetChars);
	foreach ($customArray as $c) {
		if (strpos($allSetCharsString, $c) === false) {
			$tmpCustom .= $c;
		}
	}
	
	if (!empty($tmpCustom)) {
		$allSetChars[] = $tmpCustom;
	}
}


// Check which action to perform and prepare JSON.
$json = '';
if (isset($_POST['checkstrength']) && !empty($_POST['checkstrength']) && isset($_POST['result']) && !empty($_POST['result'])) {
	// Use posted string as password.
	$password = $_POST['result'];
	
	// Add similar chars to set
	$chars = implode('', $allSetChars) . $similar;

	// Remove all chars from posted string which are not in the globally defined sets.
	$pattern = '/[^' . preg_quote($chars, '/') . ']/';
	$password = preg_replace($pattern, '', $password);
	
	// Check strength of entered password
	$strength = checkPasswordStrength($password, $allSetChars);
	
	// Return a JSON formatted array which contains the password and its strength.
	$json = json_encode(array('password' => $password, 'strength' => $strength));
} else {
	if (!isset($_POST['checkstrength']) || empty($_POST['checkstrength'])) {
		// Return the generated password and its strength as JSON 
		$json = generateStrongPassword($length, $add_dashes, $sets, $allSetChars, $mandatory);
	}
}

// Add JSON header just in case JQuery messes things up if it's missing. Normally not required if dataType is set to 'json' in AJAX call.
header('Content-Type: application/json');
// Return JSON
echo $json;
// Always die after an AJAX call.
die();


/**
 * Generates a strong random password.
 * 
 * The characters l, I, O, 1, 0 have been left out, due to being too similar.
 * 
 * @param Integer $length - The length of the resulting password.
 * @param String $add_dashes - Useful to generate serial number looking passwords.
 * @param String $available_sets - The sets to use. l = lower case, u = upper case, d = decimals and s = symbols.
 * @param String $custom - Collection of user-entered characters. Will be added to the sets unless the sets already contain them.
 * @param Boolean $mandatory - Flag to toggle chosen sets mandatory.
 * @return String (JSON)
 */
function generateStrongPassword($length = 16, $add_dashes = false, $sets = array(), $allSetChars = '', $mandatory = false) {
	// Shuffle the order of sets for additional randomness.
	shuffle($sets);
	
	// Add all characters from chosen sets.
	$allChars = implode('', $sets);

	// Split string into an array which contains one character per entry.
	$allChars = str_split($allChars);
	
	// Prepare results array (one entry per set)
	$ret = array();
	for ($i = 0; $i < count($sets); $i++) {
		$ret[$i] = 0;
	}
	
	if ($mandatory) {
		do {
			$password = '';
			for ($i = 0; $i < $length; $i++) {
				$password .= $allChars[array_rand($allChars)];	// Add random character from chosen sets to password
			}
		} while(!hasMandatoryChars($password, $sets, $ret));
	} else {
		$password = '';
		for ($i = 0; $i < $length; $i++) {
			$password .= $allChars[array_rand($allChars)];		// Add random character from chosen sets to password
		}
	}
	
	$password = str_shuffle($password);							// Shuffle the generated password for additional randomness.

	// Add dashes if requested
	if ($add_dashes) {
		$dash_len = floor(sqrt($length));
		$dash_str = '';
		
		// Split password into equally sized blocks separated by dashes.
		while (strlen($password) > $dash_len) {
			$dash_str .= substr($password, 0, $dash_len) . '-';
			$password = substr($password, $dash_len);
		}
		
		$password = $dash_str . $password;
	}

	// Check the password strength.
	$strength = checkPasswordStrength($password, $allSetChars);
	
	// Return a JSON formatted array which contains the password and its strength.
	return json_encode(array('password' => $password, 'strength' => $strength));
}// END: generateStrongPassword()


/**
 * Checks if a given password contains at least one character of each set.
 * 
 * @param String $password
 * @param Array $sets
 * @param Array $ret
 * @return boolean
 */
function hasMandatoryChars($password, $sets, $ret, $tolerance = 0) {
	// Check if each char of each set is at least one time in password.
	foreach ($sets as $key => $value) {
		for ($i = 0; $i < strlen($value); $i++) {
			if (strpos($password, $value[$i]) !== false) {
				$ret[$key] = 1;
			}
		}
	}
	
	// Check if all true in results array
	$violations = 0;
	foreach($ret as $r) {
		if (empty($r)) {
			$violations++;
		}
	}

	// If more violations of defined rules than defined tolerance return false.
	if ($violations > $tolerance) {
		return false;
	}
	
	return true;
}// END: hasMandatoryChars()


/**
 * Checks strength of a given password against all defined sets.
 * Returns a string that describes the strength of the password, in order to use that as id by the associated JavaScript.
 * 
 * @param String $password
 * @param Array $allSetChars
 * @return string
 */
function checkPasswordStrength($password, $allSetChars) {
	$ret = 'weak';
	$length = strlen($password);
	
	// Clear temporary array
	$tmp = array();
	for ($i = 0; $i < count($allSetChars); $i++) {
		$tmp[$i] = 0;
	}
	
	// Check if password is at least 8 chars long and contains at least one char of each set. No tolerance here!
	if ($length >= 8 && hasMandatoryChars($password, $allSetChars, $tmp)) {
		$ret = 'good';
	} else {
		// Check if password is at least 6 chars long and contains at least one char of three of all sets. Tolerance is 1.
		if ($length >= 6 && hasMandatoryChars($password, $allSetChars, $tmp, 1)) {
			$ret = 'fair';
		}
		
		// Everything else is considered a weak password as defined at the top of this function.
	}
	
	return $ret;
}// END: checkPasswordStrength()
?>