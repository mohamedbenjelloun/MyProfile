<?php

if(!defined("IN_MYBB"))
{
        die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

define("IN_MYPROFILE", true);

require_once MYBB_ROOT . "inc/plugins/myprofile/myprofileessence.class.php";
require_once MYBB_ROOT . "inc/plugins/myprofile/myprofileutils.class.php";
require_once MYBB_ROOT . "inc/plugins/myprofile/myprofilecomments.class.php";
require_once MYBB_ROOT . "inc/plugins/myprofile/myprofilefriends.class.php";
require_once MYBB_ROOT . "inc/plugins/myprofile/myprofilevisitors.class.php";

function myprofile_info() {
	return array(
		"name" => "MyProfile",
		"description" => "Boosts MyBB's default users profiles comments, friend system and last visitors.",
		"website" => "http://community.mybb.com/",
		"author" => "TheGarfield",
		"authorsite" => "http://mohamedbenjelloun.com/",
		"version" => "0.1",
		"compatibility" => "18*",
		"guid" => "", // bye bye 1.6 :D
		"codename" => "myprofile"
    );
}

function myprofile_install() {
	myprofile_bundles_propagate_call("install");
}

function myprofile_is_installed() {
	/* assuming at least ONE bundle will override the isInstalled method and tell us in God's name if MyProfile is installed correctly or not, I'm pretty confident about that :D */
	$boolean = true;
	$results = myprofile_bundles_propagate_call("is_installed");
	foreach($results as $result) {
		$boolean = $boolean && $result;
	}
	return $boolean;
}

function myprofile_uninstall() {
	myprofile_bundles_propagate_call("uninstall");
}

function myprofile_activate() {
	myprofile_bundles_propagate_call("activate");
}

function myprofile_deactivate() {
	myprofile_bundles_propagate_call("deactivate");
}

/**
 * This function propagates an action to all the subsidiary classes (or bundles) that exist in MyProfile system.
 * If the $call_method exists, it will be executed otherwise nothing will happen.
 * @param string $call_method the method to call
 * @param array $parameters parameters to pass to the method (if it requires any), should be an array containing the parameters.
 * @return array of result.
 */
function myprofile_bundles_propagate_call($call_method, $parameters = array()) {
	/* if you create a new bundle, register it in here, so it receives the plugin's routines */
	$bundles = array(
		MyProfileEssence::get_instance(),
		MyProfileComments::get_instance(),
		MyProfileFriends::get_instance(),
		MyProfileVisitors::get_instance()
		//MyProfileTabs::get_instance()
	);
	$results = array();
	foreach($bundles as $bundle) {
		if(method_exists($bundle, $call_method)) {
			$results[] = call_user_func_array(array($bundle, $call_method), $parameters);
		}
	}
	return $results;
}