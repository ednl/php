<?php declare(encoding='UTF-8');

/**
 * Password constants
 */
define('PASSWORD_ALGORITHM', 'sha256');
define('PASSWORD_STRETCH_KEY', 10);
define('PASSWORD_LENGTH_EASY', 8);
define('PASSWORD_LENGTH_HARD', 16);
define('PASSWORD_LENGTH_SALT', 64);
define('PASSWORD_COMPLEXITY_WORD', 0);
define('PASSWORD_COMPLEXITY_EASY', 1);
define('PASSWORD_COMPLEXITY_HARD', 2);
define('PASSWORD_COMPLEXITY_SALT', 3);

/**
 * Login constants
 */
if (!defined('YEAR_IN_SECONDS'))
	define('YEAR_IN_SECONDS', 366 * 24 * 3600);
define('LOGIN_EXPIRE', YEAR_IN_SECONDS);

/**
 * Return random digit
 */
function password_digit() {
	return chr(ord('0') + mt_rand(0, 9));
}

/**
 * Return random vowel
 */
function password_vowel() {
	$ch = 'aeiouy';
	return $ch[mt_rand(0, strlen($ch) - 1)];
}

/**
 * Return random consonant
 */
function password_consonant() {
	$ch = 'bcdfghjklmnpqrstvwxz';
	return $ch[mt_rand(0, strlen($ch) - 1)];
}

/**
 * Return random 3-letter syllable
 */
function password_syllable() {
	return password_consonant() . password_vowel() . password_consonant();
}

/**
 * Generate pronounceable password that people might remember, 576 million possibilities
 */
function password_word() {
	return password_syllable() . ucfirst(password_syllable()) . password_digit() . password_digit();
}

/**
 * Make random password from set of characters, strlen(chars)^length possibilities
 */
function password_make($chars, $length) {
	$ch = str_shuffle($chars);
	$k = strlen($ch) - 1;
	$str = '';
	for ($i = 0; $i < $length; ++$i)
		$str .= $ch[mt_rand(0, $k)];
	return(str_shuffle($str));
}

/**
 * Generate an easy password from unambiguous lowercase letters and 1 digit, ~27 billion possibilities
 */
function password_easy($length = PASSWORD_LENGTH_EASY) {
	return(password_make('abcdefghjkmnpqrstuvwxyz', $length - 1) . mt_rand(2, 9));
}

/**
 * Generate a hard to guess (or type, or pronounce) password from unambiguous alphanum chars and some symbols, 3.7e+28 possibilities
 */
function password_hard($length = PASSWORD_LENGTH_HARD) {
	return(password_make('!@#%&?23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz', $length));
}

/**
 * Generate salt from lowercase hex characters, 1.2e+77 possibilities
 */
function password_salt($length = PASSWORD_LENGTH_SALT) {
	return(password_make('0123456789abcdef', $length));
}

/**
 * Hash password with random salt, return salt + hash
 */
function password_hash($password) {
	//generate salt as long as the password hash
	$salt = password_salt();
	//use salt to hash password
	$hash = $password;
	for ($i = 0; $i < PASSWORD_STRETCH_KEY; ++$i)
		$hash = hash(PASSWORD_ALGORITHM, $salt . $hash);
	//return combined salt + hash
	$encoded = $salt . $hash; //128-character hex string
	return($encoded);
}

/**
 * Validate plain text password against salt + hash
 */
function password_isok($password, $encoded) {
	//extract salt and old hash
	$salt = substr($encoded, 0, PASSWORD_LENGTH_SALT);
	$oldhash = substr($encoded, PASSWORD_LENGTH_SALT);
	//use salt to make new hash from password
	$newhash = $password;
	for ($i = 0; $i < PASSWORD_STRETCH_KEY; ++$i)
		$newhash = hash(PASSWORD_ALGORITHM, $salt . $newhash);
	//return comparison result
	return(!strcmp($newhash, $oldhash));
}

/**
 * Create (almost) unique Client ID as <User ID>_<IP Address>_<Browser ID>
 */
function login_clientid($userid = 0) {
	return($userid . '_' . $_SERVER['REMOTE_ADDR'] . '_' . $_SERVER['HTTP_USER_AGENT']);
}

/**
 * Set login cookie to hashed Client ID, prepend plain text User ID for later retrieval
 */
function login_set($userid = 0) {
	$value = $userid . '_' . password_hash(login_clientid($userid));
	$expire = time() + LOGIN_EXPIRE; // set cookie for a certain time
	setcookie('login', $value, $expire);
}

/**
 * Delete the login cookie (= log out)
 */
function login_del() {
	if (isset($_COOKIE['login'])) {
		$expire = time() - YEAR_IN_SECONDS; // expire a year ago
		setcookie('login', '', $expire);
		unset($_COOKIE['login']);
	}
}

/**
 * Check the login cookie, return User ID from cookie if not zero, log out and return FALSE on fail
 */
function login_chk($userid = 0) {
	if (isset($_COOKIE['login'])) {
		$parts = explode('_', $_COOKIE['login'], 2);
		if (is_array($parts) && count($parts) == 2) {
			$cookieid = max(0, (int)$parts[0]);
			if ($cookieid == $userid || !$userid)
				if (password_isok(login_clientid($cookieid), $parts[1]))
					return($cookieid ? $cookieid : TRUE);
		}
		login_del();
	}
	return(FALSE);
}

/**
 * Get the logged in user, update last visit, return user array or FALSE on fail
 */
function login_get() {
	if ($userid = login_chk())
		if (is_int($userid)) {
			$sql = sprintf('select * from `user` where `id` = %u limit 1', $userid);
			if ($res = @mysql_query($sql))
				if (@mysql_num_rows($res))
					if ($user = @mysql_fetch_assoc($res)) {
						//update last visit time
						if (array_key_exists('lastvisit', $user)) {
							$sql = sprintf('update `user` set `lastvisit` = NOW() where `id` = %u limit 1',
								$user['id']);
							@mysql_query($sql);
						}
						//return complete user tuple except the password hash
						if (array_key_exists('hash', $user))
							unset($user['hash']);
						return($user);
					}
		}
	return(FALSE);
}

/**
 * Validate username/password, log in and return user array on success, log out and return FALSE on fail
 */
function login_val($username, $password) {
	$sql = sprintf('select * from `user` where `username` = "%s" and `banned` = 0 limit 1',
		@mysql_real_escape_string($username));
	if ($res = @mysql_query($sql))
		if (@mysql_num_rows($res))
			if ($user = @mysql_fetch_assoc($res))
				if (array_key_exists('id', $user) && array_key_exists('hash', $user))
					if (password_isok($password, $user['hash'])) {
						//set login cookie
						login_set($user['id']);
						//update last login time
						if (array_key_exists('lastlogin', $user)) {
							$sql = sprintf('update `user` set `lastlogin` = NOW() where `id` = %u limit 1',
								$user['id']);
							@mysql_query($sql);
						}
						//return complete user tuple except the password hash
						unset($user['hash']);
						return($user);
					}
	login_del();
	return(FALSE);
}

?>
