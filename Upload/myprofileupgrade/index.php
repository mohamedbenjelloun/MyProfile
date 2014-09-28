<?php


define("IN_MYBB", 1);
define("IN_MYPROFILE_UPGRADE", 1);

require_once "../global.php";


//$cache->delete("myprofile");exit;


/* this is the current version of the plugin */
$current_version = "0.5";
/* those are the previous versions to which there is an upgrader available, the upgrader from 0.3 to 0.5 for example, should be called resources/upgrade_0.3.php, and should return "0.5" as a value */
/* put them in order, key 0 will be executed first */
$previous_versions = array(
	0 => "0.3"
);

$myprofile_cache = $cache->read("myprofile");
if(empty($myprofile_cache)) {
	$myprofile_cache = array();
	$myprofile_version = "0.3";
}
else {
	$myprofile_version = $myprofile_cache["version"];
}

if($mybb->user["uid"] <= 0 || $mybb->usergroup["cancp"] != "1") {
	error_no_permission();
}

if($myprofile_version == $current_version) {
	error("You already have the latest version of MyProfile (<strong>MyProfile {$myprofile_version}</strong>).", "MyProfile Upgrade");
}

if(! isset($mybb->input["action"], $mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || $mybb->input["action"] != "do_upgrade") {
	error("Welcome to MyProfile upgrade, we have detected that you are currently using <strong>MyProfile {$myprofile_version}</strong> and wish to upgrade to <strong>MyProfile {$current_version}</strong>. Start the upgrading process? <br />
<form method=\"POST\">
<input type=\"hidden\" name=\"my_post_key\" value=\"{$mybb->post_code}\">
<input type=\"hidden\" name=\"action\" value=\"do_upgrade\">
<input type=\"submit\" class=\"button\" name=\"submit\" value=\"Start upgrading\">
</form>
", "MyProfile Upgrade");
}
else {
	verify_post_check($mybb->input["my_post_key"]);
	$keys = array_keys($previous_versions);
	sort($keys);
	foreach($keys as $key) {
		/* while we haven't reached our version, keep going */
		while($previous_versions[$key] != $myprofile_version) {
			continue 2;
		}
		$upgrade_version = require_once "./resources/upgrade_{$previous_versions[$key]}.php";
		$myprofile_cache["version"] = $upgrade_version;
	}
	$cache->update("myprofile", $myprofile_cache);
	error("Upgraded successfully. <span style=\"color: red\">We strongly recommend you to delete the <strong>myprofileupgrade</strong> folder.</span><br /><strong>Please also remember to deactivate/re-activate MyProfile for changes to take place!</strong>");
}