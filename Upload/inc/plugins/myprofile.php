<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Mohamed Benjelloun
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.

 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE. 
 */
 
if(!defined("IN_MYBB"))
{
        die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

define("IN_MYPROFILE", true);

require_once MYBB_ROOT . "inc/plugins/myprofile/myprofileessence.class.php";
require_once MYBB_ROOT . "inc/plugins/myprofile/myprofileutils.class.php";
require_once MYBB_ROOT . "inc/plugins/myprofile/myprofilecomments.class.php";
require_once MYBB_ROOT . "inc/plugins/myprofile/myprofilebuddylist.class.php";
require_once MYBB_ROOT . "inc/plugins/myprofile/myprofilevisitors.class.php";

function myprofile_info() {
	return array(
		"name" => "MyProfile",
		"description" => "Enhances MyBB's default users profiles with comments, friend system and last visitors.",
		"website" => "http://community.mybb.com/",
		"author" => "TheGarfield",
		"authorsite" => "http://mohamedbenjelloun.com/",
		"version" => "0.5",
		"compatibility" => "18*",
		"guid" => "", // bye bye 1.6 :D
		"codename" => "myprofile"
    );
}

/* starting version 0.5, we add a cache that handles versionning */
function myprofile_install() {
	global $cache;
	myprofile_bundles_propagate_call("install");
	$info = myprofile_info();
	$myprofile_cache = array("version" => $info["version"]);
	$cache->update("myprofile", $myprofile_cache);
}

function myprofile_is_installed() {
	/* assuming at least ONE bundle will override the is_installed method and tell us if MyProfile is installed correctly or not, I'm pretty confident about that :D */
	$boolean = true;
	$results = myprofile_bundles_propagate_call("is_installed");
	foreach($results as $result) {
		$boolean = $boolean && $result;
	}
	return $boolean;
}

function myprofile_uninstall() {
	global $mybb;

	if($mybb->request_method == 'post')
	{
		if(!verify_post_check($mybb->input['my_post_key']))
		{
			global $lang;

			flash_message($lang->invalid_post_verify_key2, 'error');
			admin_redirect("index.php?module=config-plugins");
		}

		if(isset($mybb->input['no']))
		{
			admin_redirect('index.php?module=config-plugins');
		}

		myprofile_bundles_propagate_call("uninstall");
		$mybb->cache->delete("myprofile");

		return true;
	}

	global $page;

	$page->output_confirm_action("index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=myprofile");
}

function myprofile_activate() {
	global $cache;
	/* before activating, if we were running on an old version, update */
	$info = myprofile_info();
	$myprofile_cache = $cache->read("myprofile");
	if($myprofile_cache == null || ! isset($myprofile_cache["version"])) {
		/* lucky us, only version 0.3 doesn't have a version cache */
		$version = "0.3";
		$myprofile_cache = array();
	}
	else {
		$version = $myprofile_cache["version"];
	}
	if($version != $info["version"]) {
		/* newer version, call methods that will upgrade from the older version to the new one */
		$version = str_replace(".", "_", trim($version));
		myprofile_bundles_propagate_call("upgrade_" . $version);
		/* update the cache now that the upgrade has been done */
		$myprofile_cache["version"] = $info["version"];
		$cache->update("myprofile", $myprofile_cache);
	}
	
	/* activate bundles */
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
		MyProfileBuddyList::get_instance(),
		MyProfileVisitors::get_instance()
	);
	$results = array();
	foreach($bundles as $bundle) {
		if(method_exists($bundle, $call_method)) {
			$results[] = call_user_func_array(array($bundle, $call_method), $parameters);
		}
	}
	return $results;
}