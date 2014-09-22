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
		"description" => "Boosts MyBB's default users profiles with tabs, profile comments, friend system and last visitors.",
		"website" => "http://community.mybb.com/",
		"author" => "TheGarfield",
		"authorsite" => "http://mohamedbenjelloun.com/",
		"version" => "0.3",
		"compatibility" => "18*",
		"guid" => "", // bye bye 1.6 :D
		"codename" => "myprofile"
    );
}

function myprofile_install() {
	myprofile_bundles_propagate_call("install");
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