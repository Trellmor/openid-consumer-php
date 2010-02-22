<?php

/**
 * This is an example on how to use OpenID extensions with the OpenID class
 * 
 * The setup is pretty much the same as with an regular login, but befor we 
 * redirect the user we set additional parameters
 */

error_reporting(E_ALL);

require_once('class.openid.php');

// Load OpenID Class
$oid = new OpenID();

if (isset($_POST['openid_identifier'])) {
	// If the user submitted the form, we try to find and endpoint and send him ofer there to log in
	
	$oid->SetIdentifier($_POST['openid_identifier']);

	try {
		$oid->DiscoverEndpoint();
	} catch (OpenIDException $e) {
		// If we fail to discover an endpoint, exit
		echo $e->getMessage();
		die();
	}
	// You can cache the endpoint locally, in that case use OpenID::GetEndpoint() 
	// and cache it locally and use OpenID::SetEndpoint(endpoint) instead of OpenID::DiscoverEndpooint()
	
	// Set where we want the user to be send after the OP did the login
	$oid->SetReturnTo('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
	
	// Optional, set authentification realm, see http://openid.net/specs/openid-authentication-2_0.html#realms
	$oid->SetRealm('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));
	
	// Setup Attribute eXchange (AX) extension
	// See http://openid.net/specs/openid-attribute-exchange-1_0.html
	// See http://code.google.com/apis/accounts/docs/OpenID.html
	$oid->SetParameter('openid.ns.ax', 'http://openid.net/srv/ax/1.0');
	$oid->SetParameter('openid.ax.mode', 'fetch_request');
	$oid->SetParameter('openid.ax.required', 'email,firstname');
	$oid->SetParameter('openid.ax.type.email', 'http://axschema.org/contact/email');
	$oid->SetParameter('openid.ax.type.firstname', 'http://axschema.org/namePerson/first');
	
	// Redirect user to OP
	// You can also use OpenID::GetRequestAuthentificationURL() and redirect the user yourself
	echo $oid->GetRequestAuthentificationURL();
	//$oid->RedirectUser();
	
} elseif($oid->IsResponse()) {
	// We got a reply from an openid provider
	
	// Geht the openid.mode and check if it is id_res (i.e. successful login)
	$mode = $oid->GetResponseMode();
	if ($mode == 'id_res') {
		// Login successfull, now verify the login
		try {
			if($oid->VerifyAssertion()) {
				// Find out which namespace was used
				$ns = $oid->GetNamespace('http://openid.net/srv/ax/1.0');
				echo "Login successful<br />";
				echo 'First name: ' . $oid->GetParameter('openid.' . $ns . '.value.firstname') . '<br />';
				echo 'eMail: ' . $oid->GetParameter('openid.' . $ns . '.value.email');
			} else {
				echo "Login failed";
			}
		} catch (OpenIDException $e) {
			// If the data cechks are invalid, or we fail to contact the OP
			$status = false;
			echo "Failed to verify your login: " . $e->getMessage() . '<br />';
		}
	} else {
		// Refer to http://openid.net/specs/openid-authentication-2_0.html#negative_assertions if you need to further handle negative responses
		echo "Login failed: " . $mode;
	}
	
} else {
	
?>

<html>
	<head>
		<title>OpenID Example Login</title>
	</head>
	<body>
		<form action="example_extension.php" method="post">
		OpenID Identifier: <input type="text" name="openid_identifier" /><br />
		<input type="submit" name="submit" value="Login" />
		</form>
	</body>
</html>

<?php

}

?>