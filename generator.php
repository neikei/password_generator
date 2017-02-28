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

if (!isset($_POST['ajax']) || empty($_POST['ajax'])) {
	die('Missing parameter');
}

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

print generateStrongPassword($length, $add_dashes, $available_sets, $custom, $mandatory);
die();


/**
 * Generates a strong random password.
 * 
 * The characters l, I, O, 1, 0 have been left out, due to being to similar.
 * 
 * @param number $length - The length of the resulting password.
 * @param string $add_dashes - Useful to generate serial number looking passwords.
 * @param string $available_sets - The sets to use. l = lower case, u = upper case, d = decimals and s = symbols.
 * @return string
 */

function generateStrongPassword($length = 16, $add_dashes = false, $available_sets = 'luds', $custom = '', $mandatory = false) {
	$sets = array();
	$available_sets = strtolower($available_sets); // Check sets as lowercase.
	
	// Use lowercase characters
	if (strpos($available_sets, 'l') !== false) {
		$sets[] = 'abcdefghijkmnopqrstuvwxyz';
	}
	
	// Use uppercase characters
	if (strpos($available_sets, 'u') !== false) {
		$sets[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
	}

	// Use decimals
	if (strpos($available_sets, 'd') !== false) {
		$sets[] = '23456789';
	}
	
	// Use symbols
	if (strpos($available_sets, 's') !== false) {
		$sets[] = '!@#$%&*?;:-+/()={[]}';
	}

	$allChars = implode('', $sets); // Add all characters from chosen sets
	$password = '';
	
	// Add custom characters unless they are already in $allChars. 
	if (!empty($custom)) {
		$custom = str_split($custom);
		foreach ($custom as $c) {
			if (strpos($allChars, $c) === false) {
				$allChars .= $c;
			}
		}
	}
	
	foreach ($sets as $set) {
		$password .= $set[array_rand(str_split($set))];	// Generate first part of password
	}

	$allChars = str_split($allChars);
	for ($i = 0; $i < $length - count($sets); $i++) {
		$password .= $allChars[array_rand($allChars)];	// Add random character from chosen sets to password
	}

	if ($mandatory && !empty($custom)) {
		$i = rand(0, count($custom) - 1);				// Get random number. Used as index in $custom array.
		$password = substr($password, 0, -1);			// Cut-off last character.
		$password .= $custom[$i];						// Add one mandatory character.
	}
	
	$password = str_shuffle($password);					// Shuffle the generated password for additional randomness.

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
	
	return $password;
}// END: generateStrongPassword()
?>