<?php
/**
 * 
 * Password Generator
 * 
 * Supports generating passwords of unlimited length and selectable complexity.
 * 
 * @author nrekow
 *
 * 
 */

// Die if request doesn't identify as AJAX request.
if (!isset($_POST['ajax']) || empty($_POST['ajax'])) {
	die('Missing parameter!');
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
	$custom = $_POST['custom'];
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
$symbols   = '!"^°@#$%&*?;:.,\'~_-+/\\()=<{[]}>';
$similar_uppercase = 'IO';
$similar_lowercase = 'l';
$similar_decimals = '10';
$similar_symbols = '|';

$chartypes = array('/[a-z]/', '/[A-Z]/', '/[0-9]/', '/[\W]/');

$available_sets = strtolower($available_sets); // Check sets as lowercase.

// Use lowercase characters
if (strpos($available_sets, 'l') !== false) {
	if (strpos($available_sets, 'x') !== false) {
		$sets[] = $lowercase . $similar_lowercase;
	} else {
		$sets[] = $lowercase;
	}
}

// Use uppercase characters
if (strpos($available_sets, 'u') !== false) {
	if (strpos($available_sets, 'x') !== false) {
		$sets[] = $uppercase . $similar_uppercase;
	} else {
		$sets[] = $uppercase;
	}
}

// Use decimals
if (strpos($available_sets, 'd') !== false) {
	if (strpos($available_sets, 'x') !== false) {
		$sets[] = $decimals . $similar_decimals;
	} else {
		$sets[] = $decimals;
	}
}

// Use symbols
if (strpos($available_sets, 's') !== false) {
	if (strpos($available_sets, 'x') !== false) {
		$sets[] = $symbols . $similar_symbols;
	} else {
		$sets[] = $symbols;
	}
}


// Use custom chars and add them if they are not covered by the chosen/available sets
if (!empty(($custom))) {
	$tmpCustom = addCustomChars($custom, $sets);

	if ($tmpCustom) {
		$tmpCustom = explode('', $tmpCustom);
		foreach ($tmpCustom as $tc) {
			
		}
		//$sets[] = $tmpCustom;			// Add custom chars only to chosen sets
	}
}


// Check which action to perform and prepare JSON.
$json = '';
$password = '';
$ret = '';

if (isset($_POST['checkstrength']) && !empty($_POST['checkstrength']) && isset($_POST['result']) && !empty($_POST['result'])) {
	// Use posted string as password.
	$password = $_POST['result'];
	
	// Check strength of entered password
	$strength = checkPasswordStrength($password, $chartypes);
	
	// Return a JSON formatted array which contains the password and its strength.
	$json = json_encode(array('password' => $password, 'strength' => $strength), JSON_UNESCAPED_UNICODE);
} else {
	if (!isset($_POST['checkstrength']) || empty($_POST['checkstrength'])) {
		// Return the generated password and its strength as JSON
		// Circumvent duplicate AJAX calls due to hammering the "Generate" button. 
		do {
			$ret = generateStrongPassword($length, $add_dashes, $sets, $chartypes, $mandatory);
		} while (empty($ret));
		
		$json = json_encode($ret, JSON_UNESCAPED_UNICODE);
	}
}

// Add JSON header just in case JQuery messes things up if it's missing. Normally not required if dataType is set to 'json' in AJAX call.
header('Content-Type: application/json; charset=UTF-8');
// Return JSON
echo $json;
// Always die after an AJAX call.
die();



function cleanupString($s, $allSetChars) {
	return preg_replace( '/[^' . preg_quote( implode('', $allSetChars), '/' ) . ']/', '', $s );
}// END: cleanupString()


/**
 * Generate a password with a defined length out of sets of chars.
 *
 * @param integer $length
 * @param array $allChars
 * @return string
 */
function makePassword($length, $allChars, $n) {
	$tmpPassword = '';
	$use_random_int = false;
	
	if (function_exists('random_int')) {
		$use_random_int = true;
	}
	
	// Add random character from chosen sets to password
	for ($i = 0; $i < $length; $i++) {
		// Don't use array_rand(). It has a very strange randomness.
		//$tmpPassword .= $allChars[array_rand($allChars)];
		
		if ($use_random_int) {
			// If you're on PHP 7 use this, because random_int() is cryptografically secure and does the same as mt_rand().
			$tmpPassword .= $allChars[random_int(0, $n - 1)];
		} else {
			// If you're still on PHP 5 use the Mersenne Twister Random Number Generator instead.
			// It's much better than array_rand(), but keep in mind that it's cryptografically insecure.
			$tmpPassword .= $allChars[mt_rand(0, $n - 1)];
		}
	}
	
	return $tmpPassword;
}// END: makePassword()


/**
 * Split-up a string by inserting dashes.
 *
 * @param string $password
 * @param integer $length
 * @return string
 */
function addDashes($password, $length) {
	$dash_len = floor(sqrt($length));
	$dash_str = '';
	
	// Split password into equally sized blocks separated by dashes.
	while (strlen($password) > $dash_len) {
		$dash_str .= substr($password, 0, $dash_len) . '-';
		$password = substr($password, $dash_len);
	}
	
	$password = $dash_str . $password;
	
	return $password;
}// END: addDashes()


/**
 * Checks for custom chars and adds them to the chosen set.
 *
 * @param string $custom
 * @param string|array $sets
 * @return string|boolean
 */
function addCustomChars($custom, $sets) {
	$tmpCustom = '';
	
	// Convert array to string
	if (is_array($sets)) {
		$sets= implode('', $sets);
	}
	
	// Split string into array
	$customArray = str_split($custom);
	
	// Walk through the array and add every char which is not already in our sets
	foreach ($customArray as $c) {
		if (strpos($sets, $c) === false) {
			$tmpCustom .= $c;
		}
	}
	
	// Check if our temporary string is empty and set it to false (hail to no type safety!) ...
	empty($tmpCustom) ? $tmpCustom = false : null;
	
	// ... otherwise simply return our temporary string.
	return $tmpCustom;
}// END: addCustomChars()


/**
 * Generates a strong random password.
 * 
 * The characters l, I, O, 1, 0 have been left out, due to being too similar.
 * 
 * @param integer $length - The length of the resulting password.
 * @param string $add_dashes - Useful to generate serial number looking passwords.
 * @param string $available_sets - The sets to use. l = lower case, u = upper case, d = decimals and s = symbols.
 * @param string $custom - Collection of user-entered characters. Will be added to the sets unless the sets already contain them.
 * @param boolean $mandatory - Flag to toggle chosen sets mandatory.
 * @return array
 */
function generateStrongPassword($length = 16, $add_dashes = false, $sets = array(), $chartypes = array(), $mandatory = false) {
	// Shuffle the order of sets for additional randomness.
	shuffle($sets);
	
	// Add all characters from chosen sets.
	$allChars = implode('', $sets);
	
	// Convert $allChars from UTF-8 to ISO, because we select just one byte per round when generating the password. UTF-8 is two bytes long.
	$allChars = iconv("UTF-8", "ISO-8859-1//IGNORE", $allChars);
	
	// Split string into an array which contains one character per entry.
	$allChars = str_split($allChars);
	
	$password = ''; // Clear $password, just to be save.
	$n = count($allChars); // Faster to store it, than to request it again for each loop.
	
	// Generate a random password out of the defined chars.
	$password = makePassword($length, $allChars, $n);
	
	// Check if chosen char sets are mandatory and generate a random, temporary password.
	if ($mandatory && !hasMandatoryChars($password, $chartypes)) {
		$tmpPassword = '';
		foreach ($sets as $set) {
			$set = iconv('UTF-8', 'ISO-8859-1//IGNORE', $set);
			$tmpPassword .= $set[mt_rand(0, strlen($set) -1)]; // Use strlen() instead of count() here, because it's a string and not an array.
		}
		
		// Cut off the length of the temporary password, which we want to add, of the real password so the resulting length stays the same.
		$password = substr($password, 0, strlen($password) - strlen($tmpPassword)) . $tmpPassword;
	}
		
	// Shuffle the generated password for additional randomness.
	$password = str_shuffle($password);
	
	// Add dashes if requested
	if ($add_dashes) {
		$password = addDashes($password, $length);
	}

	// Convert $password back to UTF-8, because json_encode() expects UTF-8. Also the strength check would fail otherwise. 
	$password = iconv('ISO-8859-1', 'UTF-8', $password);

	// Check the password strength.
	$strength = checkPasswordStrength($password, $chartypes);
	
	// Return array which contains the password and its strength.
	return array('password' => $password, 'strength' => $strength);
}// END: generateStrongPassword()


/**
 * Checks if a given password contains at least one character of each set.
 * 
 * @param string $password
 * @param integer $tolerance
 * @return boolean
 */
function hasMandatoryChars($password, $chartypes, $tolerance = 0) {
	$strength = numCharTypes($password, $chartypes);
	$violations = abs($strength - count($chartypes));

	// If more violations of defined rules than defined tolerance return false.
	if ($violations > $tolerance) {
		return false;
	}
	
	return true;
}// END: hasMandatoryChars()


/**
 * Check how many char types a string has
 * 
 * @param string $s
 * @return number
 */
function numCharTypes($s, $chartypes) {
	$num = 0;
	
	// Check for all defined character types
	if (is_array($chartypes) && count($chartypes) > 0) {
		foreach ($chartypes as $chartype) {
			if (preg_match_all($chartype, $s)) {
				$num++;
			}
		}
	}
	
	return $num;
}// END: numCharTypes()


/**
 * Checks strength of a given password against all defined sets.
 * Returns a string that describes the strength of the password, in order to use that as id by the associated JavaScript.
 * 
 * @param string $password
 * @param array $allSetChars
 * @return string
 */
function checkPasswordStrength($password, $chartypes) {
	// Everything is considered a weak password unless it fits the definition below.
	$ret = 'weak';
	$length = strlen($password);
	$strength = numCharTypes($password, $chartypes);
	
	// Set return value according to length and calculated strength
	if ($length >= 9 && $strength == count($chartypes)) {
		$ret = 'good';
	} else if ($length >= 6 && $strength == count($chartypes) - 1) {
		$ret = 'fair';
	} else if ($length < 1) {
		$ret = '';
	}
	
	return $ret;
}// END: checkPasswordStrength()
?>