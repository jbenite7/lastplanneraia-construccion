<?php

//start session on web page
session_start();

//config.php

//Include Google Client Library for PHP autoload file
// require '../../../../../vendor/autoload.php';
require '../../../composerFiles/vendor/autoload.php';

//Make object of Google API Client for call Google API
$google_client = new Google_Client();

//Set the OAuth 2.0 Client ID
$google_client->setClientId('24871191815-g58pip14vplps4jroc3lusd6s0dlq0at.apps.googleusercontent.com');

//Set the OAuth 2.0 Client Secret key
$google_client->setClientSecret('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');

//Set the OAuth 2.0 Redirect URI
$google_client->setRedirectUri('http://localhost/construccion/controlCambios/cargarODC/index.php');

// to get the email and profile 
$google_client->addScope('email');

$google_client->addScope('profile');

?>