<?php

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
	
	// Redirect user to OP
	// You can also use OpenID::GetRequestAuthentificationURL() and redirect the user yourself
	$oid->RedirectUser();
	
} elseif($oid->IsResponse()) {
	// We got a reply from an openid provider
	
	// Geht the openid.mode and check if it is id_res (i.e. successful login)
	$mode = $oid->GetResponseMode();
	if ($mode == 'id_res') {
		// Login successfull, now verify the login
		try {
			$status = $oid->VerifyAssertion();
		} catch (OpenIDException $e) {
			// If the data cechks are invalid, or we fail to contact the OP
			$status = false;
			echo "Failed to verify your login: " . $e->getMessage() . '<br />';
		}
		
		
		if($status) {
			// Now start a session for the user, save to DB or whatever
			echo "Login successful";
		} else {
			echo "Login failed";
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
		<form action="example.php" method="post">
		OpenID Identifier: <input type="text" name="openid_identifier" /><br />
		<input type="submit" name="submit" value="Login" />
		</form>
	</body>
</html>

<?php

}

?>