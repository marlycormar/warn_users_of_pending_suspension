<?php
/**
 * @file
 * Allows user to update both user_lastactivity and user_lastlogin
 */

define('NOAUTH',true);
if (!file_exists('../../redcap_connect.php')) 
    require_once "/var/www/redcap/redcap_connect.php";
else 
    require_once '../../redcap_connect.php';

$username = $_GET["username"];

if($username == "") 
	print_r("We are unable to extend your account suspension time. Please contact the REDCap Support Team.");

else {
	$sql = "update redcap_user_information set user_lastactivity = NOW(),	 user_lastlogin = NOW() where username ='$username';";

	db_query($sql);
	
	print_r("Your account suspension time has been succesfully extended.");

	// Logging event
	Logging::logEvent($sql, "redcap_user_information", "MANAGE", $username, "username = '$username'", "Extend user suspension date.", "", "SYSTEM");
}