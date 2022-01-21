<?php

date_default_timezone_set('America/Recife');
header('Content-type: text/html; charset=iso-8859-1'); // latin1

require_once '../dbconn/dbconn.php';
require_once 'genreq/genreq.php';

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

$db->query("DELETE FROM tokens WHERE Expiration < " . time());

// if user is logged in and is an admin, redirect to the admin page,
// else, display under construction page.

// retrieve info from cookies
$token = $_COOKIE['token'];
$uid = $_COOKIE['userId'];

if (isset($token) && isset($uid) && strlen($token) == 26 && preg_match('/^([0-9a-z])+$/', $token) && preg_match('/^([0-9])+$/', $uid) &&
    !!$db->query("SELECT COUNT(*) FROM users WHERE ID = $uid AND Blocked = 0 AND Status > 0")->fetch_row()[0]){
    
    if (!!$db->query("SELECT COUNT(*) FROM tokens WHERE Token = '" . $db->real_escape_string($token) . "' AND UserID = " . $db->real_escape_string($uid))->fetch_row()[0]){

        $db->close();
        
        // redirect to admin page
        header("Location: admin/");
        die();

    }
    else {
        // not logged in, remove cookies
        setcookie("token", "", time() - 3600, '/', COOKIE_DOMAIN);
        setcookie("userId", "", time() - 3600, '/', COOKIE_DOMAIN);
    }
    
}

$db->close();

?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" >
<head>
    <title>Under Construction</title>
</head>
<body style="background-color: white;">

    <div style="text-align: center;"><img src="images/under_construction.jpg"/></div>
    
</body>
</html>