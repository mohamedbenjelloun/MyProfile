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

$available_upgraders = str_replace(array("./resources/upgrade_", ".php"), "", glob("./resources/upgrade_*.php"));

$myprofile_cache = $cache->read("myprofile");

if(empty($myprofile_cache)) {
	/* The only version that has no cache is 0.3 */
	$myprofile_cache = array("version" => "0.3");
}

if($mybb->user["uid"] <= 0 || $mybb->usergroup["cancp"] != "1") {
	error_no_permission();
}

if($myprofile_cache["version"] == $current_version) {
	error("You already have the latest version of MyProfile (<strong>MyProfile {$myprofile_cache['version']}</strong>).", "MyProfile Upgrade");
}

if(! isset($mybb->input["action"], $mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || $mybb->input["action"] != "do_upgrade") {
	error("Welcome to MyProfile upgrade, we have detected that you are currently using <strong>MyProfile {$myprofile_cache['version']}</strong> and wish to upgrade to <strong>MyProfile {$current_version}</strong>. Do you want to start the upgrading process? <br />
<form method=\"POST\">
<input type=\"hidden\" name=\"my_post_key\" value=\"{$mybb->post_code}\">
<input type=\"hidden\" name=\"action\" value=\"do_upgrade\">
<input type=\"submit\" class=\"button\" name=\"submit\" value=\"Start upgrading\">
</form>
", "MyProfile Upgrade");
}
else {
	verify_post_check($mybb->input["my_post_key"]);
	
	while(in_array($myprofile_cache["version"], $available_upgraders)) {
		$myprofile_cache["version"] = require_once "./resources/upgrade_{$myprofile_cache['version']}.php";
	}
	$cache->update("myprofile", $myprofile_cache);
	error("Upgraded successfully.
<span style=\"color: green\">Don't forget to deactivate / re-activate MyProfile plugin for changes to take place!</span>
<br />
We strongly recommend you to delete the <strong>myprofileupgrade</strong> folder.", "MyProfile Upgrade");
}