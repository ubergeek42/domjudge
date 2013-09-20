<?php
/**
 * This file provides all functionality for authenticating teams. The
 * authentication method used is configured with the AUTH_METHOD
 * variable. When a team is succesfully authenticated, $login is set
 * to the team ID and $teamdata contains the corresponding row from
 * the database. $ip is set to the remote IP address used.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

$ip = $_SERVER['REMOTE_ADDR'];

$login = NULL;
$teamdata = NULL;

// Returns whether the connected user is logged in, sets $login, $teamdata
function logged_in()
{
	global $DB, $ip, $login, $teamdata;

	if ( !empty($login) && !empty($teamdata) ) return TRUE;

	// Retrieve teamdata for given AUTH_METHOD, assume not logged in
	// when teamdata is empty:
	switch ( AUTH_METHOD ) {
	case 'FIXED':
		$login = FIXED_TEAM;
		$teamdata = $DB->q('MAYBETUPLE SELECT * FROM team WHERE login = %s', $login);
		break;

	case 'IPADDRESS':
		$teamdata = $DB->q('MAYBETUPLE SELECT * FROM team WHERE authtoken = %s', $ip);
		break;

	case 'PHP_SESSIONS':
	case 'LDAP':
		session_start();
		if ( isset($_SESSION['teamid']) ) {
			$teamdata = $DB->q('MAYBETUPLE SELECT * FROM team WHERE login = %s',
			                   $_SESSION['teamid']);
		}
		break;

	default:
		error("Unknown authentication method '" . AUTH_METHOD . "' requested.");
	}

	if ( !empty($teamdata) ) {
		$login = $teamdata['login'];
		// Is this the first visit? Record that in the team table.
		if ( empty($teamdata['teampage_first_visited']) ) {
			$hostname = gethostbyaddr($ip);
			$DB->q('UPDATE team SET teampage_first_visited = %s, hostname = %s
			        WHERE login = %s',
			       now(), $hostname, $login);
		}
	}

	return $login!==NULL;
}

// Returns whether the active authentication method has logout functionality.
function have_logout()
{
	switch ( AUTH_METHOD ) {
	case 'FIXED':        return FALSE;
	case 'IPADDRESS':    return FALSE;
	case 'PHP_SESSIONS': return TRUE;
	case 'LDAP':         return TRUE;
	}
	return FALSE;
}

// Generate a page stating that login has failed with $msg and exit.
function show_failed_login($msg)
{
	$title = 'Login failed';
	$menu = false;
	require(LIBWWWDIR . '/header.php');
	echo "<h1>Not Authenticated</h1>\n\n<p>$msg</p>\n\n";
	require(LIBWWWDIR . '/footer.php');
	exit;
}

// This function presents some kind of login page, which should e.g.
// have a POST form to supply login credentials. This function does
// not return.
function show_loginpage()
{
	global $ip;

	switch ( AUTH_METHOD ) {
	case 'IPADDRESS':
	case 'PHP_SESSIONS':
	case 'LDAP':
		if ( NONINTERACTIVE ) error("Not authenticated");
		$title = 'Not Authenticated';
		$menu = false;

		include(LIBWWWDIR . '/header.php');
		?>
<h1>Not Authenticated</h1>

<p>Sorry, we are unable to identify you as a valid team
(IP <?php echo htmlspecialchars($ip); ?>).</p>

<p>
Please supply team credentials below, or contact a staff member for assistance.
</p>

<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
<input type="hidden" name="cmd" value="login" />
<table>
<tr><td><label for="login">Login:</label></td><td><input type="text" id="login" name="login" value="" size="15" maxlength="15" accesskey="l" autofocus /></td></tr>
<tr><td><label for="passwd">Password:</label></td><td><input type="password" id="passwd" name="passwd" value="" size="15" maxlength="255" accesskey="p" /></td></tr>
<tr><td></td><td><input type="submit" value="Login" /></td></tr>
</table>
</form>

<?php
if (dbconfig_get('allow_registration', false)) { ?>
<p>If you do not have an account, you can register for one below: </p>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
<input type="hidden" name="cmd" value="register" />
<table>
<tr><td><label for="login">Username:</label></td><td><input type="text" id="login" name="login" value="" size="15" maxlength="15" accesskey="l" /></td></tr>
<tr><td><label for="name">Team Name:</label></td><td><input type="text" id="name" name="name" value="" size="15" maxlength="15" accesskey="n" /></td></tr>
<tr><td><label for="passwd">Password:</label></td><td><input type="password" id="passwd" name="passwd" value="" size="15" maxlength="255" accesskey="p" /></td></tr>
<tr><td><label for="passwd2">Retype password:</label></td><td><input type="password" id="passwd2" name="passwd2" value="" size="15" maxlength="255" accesskey="r" /></td></tr>
<tr><td></td><td><input type="submit" value="Register" /></td></tr>
</table>
</form>
<?php } //endif allow_registration ?>

<?php
		putDOMjudgeVersion();
		include(LIBWWWDIR . '/footer.php');
		break;

	default:
		error("Unknown authentication method '" . AUTH_METHOD .
		      "' requested, or login not supported.");
	}

	exit;
}

// Check LDAP user and password credentials by trying to login to
// the LDAP server(s).
function ldap_check_credentials($user, $pass)
{
	foreach ( explode(' ', LDAP_SERVERS) as $server ) {

		// The connection may only be really established when needed,
		// so execute a dummy query to test if the server is available:
		$conn = @ldap_connect($server);
		if ( !$conn || !ldap_get_option($conn, LDAP_OPT_PROTOCOL_VERSION, $dummy) ) {
			continue;
		}

/*
		// The following options are necessary to be able to talk
        // to an Active Directory:
		if ( !ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3) ) {
			error("Failed to set protocol version to 3.");
		}

		if ( !ldap_set_option($conn, LDAP_OPT_REFERRALS, 0) ) {
			error("Failed to set LDAP_OPT_REFERRALS.");
		}

		if ( !ldap_set_option($conn, LDAP_OPT_DEREF, 0) ) {
			error("Failed to set LDAP_OPT_DEREF.");
		}
*/

		// Create the dn
		$ldap_dn = str_replace('&', $user, LDAP_DNQUERY);

		// Try to login to test credentials
		if ( @ldap_bind($conn, $ldap_dn, $pass) ) {
			@ldap_unbind($conn);
			return TRUE;
		}
	}
	return FALSE;
}

// Try to login a team with e.g. authentication data POST-ed. Function
// does not return and should generate e.g. a redirect back to the
// referring page.
function do_login()
{
	global $DB, $ip, $login, $teamdata;

	switch ( AUTH_METHOD ) {
	// Generic authentication code for IPADDRESS and PHP_SESSIONS;
	// some specializations are handled by if-statements.
	case 'IPADDRESS':
	case 'PHP_SESSIONS':
		$user = trim($_POST['login']);
		$pass = trim($_POST['passwd']);

		$title = 'Authenticate user';
		$menu = false;

		if ( empty($user) || empty($pass) ) {
			show_failed_login("Please supply a username and password.");
		}

		$teamdata = $DB->q('MAYBETUPLE SELECT * FROM team
		                    WHERE login = %s AND authtoken = %s',
		                   $user, md5($user."#".$pass));

		if ( !$teamdata ) {
			sleep(3);
			show_failed_login("Invalid username or password supplied. " .
			                  "Please try again or contact a staff member.");
		}

		$login = $teamdata['login'];

		if ( AUTH_METHOD=='IPADDRESS' ) {
			$cnt = $DB->q('RETURNAFFECTED UPDATE team SET authtoken = %s
			               WHERE login = %s', $ip, $login);
			if ( $cnt != 1 ) error("cannot set IP for team '$login'");
		}
		if ( AUTH_METHOD=='PHP_SESSIONS' ) {
			session_start();
			$_SESSION['teamid'] = $login;
			auditlog('team', $login, 'logged in', $_SERVER['REMOTE_ADDR']);
		}
		break;

	case 'LDAP':
		$user = trim($_POST['login']);
		$pass = trim($_POST['passwd']);

		$title = 'Authenticate user';
		$menu = false;

		if ( empty($user) || empty($pass) ) {
			show_failed_login("Please supply a username and password.");
		}

		$teamdata = $DB->q('MAYBETUPLE SELECT * FROM team
		                    WHERE login = %s', $user);

		if ( !$teamdata ||
		     !ldap_check_credentials($teamdata['authtoken'], $pass) ) {
			sleep(3);
			show_failed_login("Invalid username or password supplied. " .
			                  "Please try again or contact a staff member.");
		}

		$login = $teamdata['login'];

		session_start();
		$_SESSION['teamid'] = $login;
		auditlog('team', $login, 'logged in', $_SERVER['REMOTE_ADDR']);
		break;

	default:
		error("Unknown authentication method '" . AUTH_METHOD .
		      "' requested, or login not supported.");
	}

	// Authentication success. We could just return here, but we do a
	// redirect to clear the POST data from the browser.
	header("Location: ./");
	exit;
}

function do_register() {
	global $DB, $ip;
	if ( !dbconfig_get('allow_registration', false) ) {
		error("Self-Registration is disabled.");
	}
	if ( AUTH_METHOD != "PHP_SESSIONS") {
		error("You can only register if the site is using PHP Sessions for authentication.");
	}

	$login = trim($_POST['login']);
	$name = trim($_POST['name']);
	$pass = trim($_POST['passwd']);
	$pass2 = trim($_POST['passwd2']);

	if ( $login == '' || $name== '' || $pass == '') {
		error("You must enter all fields");
	}

	if ( $pass != $pass2 ) {
		error("Your passwords do not match.  Please go back and try registering again.");
	}
	$team = $DB->q('MAYBETUPLE SELECT * FROM team WHERE login = %s', $login);
	if ( $team ) {
		error("That login is already taken.");
	}

	$i = array();
	$i['login'] = $login;
	$i['authtoken'] = md5($login."#".$pass);
	$i['name'] = $name;
	$i['categoryid'] = 2;
	$i['enabled'] = 1;
	$i['comments'] = "Registered by $ip on " . date();

	$newid = $DB->q("RETURNID INSERT INTO team SET %S", $i);
	auditlog('team', $newid, 'registered by ' . $ip);

	$title = 'Account Registered';
	$menu = false;

	require(LIBWWWDIR . '/header.php');
	echo "<h1>Account registered</h1>\n\n<p><a href="./">Click here to login.</a></p>\n\n";
	require(LIBWWWDIR . '/footer.php');
	exit;
}

// Logout a team. Function does not return and should generate a page
// showing logout and optionally refer to a login page.
function do_logout()
{
	global $DB, $ip, $login, $teamdata;

	switch ( AUTH_METHOD ) {
	case 'PHP_SESSIONS':
	case 'LDAP':
		// Unset all of the session variables.
		$_SESSION = array();

		// Also delete the session cookie.
		if ( ini_get("session.use_cookies") ) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
			          $params["path"], $params["domain"],
			          $params["secure"], $params["httponly"]);
		}

		// Finally, destroy the session.
		if ( !session_destroy() ) error("PHP session not successfully destroyed.");
		break;

	default:
		error("Unknown authentication method '" . AUTH_METHOD .
		      "' requested, or logout not supported.");
	}

	$title = 'Logout';
	$menu = FALSE;
	auditlog('team', $login, 'logged out', $_SERVER['REMOTE_ADDR']);

	require(LIBWWWDIR . '/header.php');
	echo "<h1>Logged out</h1>\n\n<p>Successfully logged out as team " .
	    htmlspecialchars($login) . ".</p>\n" .
	    "<p><a href=\"./\">Click here to login again.</a></p>\n\n";
	require(LIBWWWDIR . '/footer.php');
	exit;
}
