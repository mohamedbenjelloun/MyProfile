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
 
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("global_start", array(MyProfileEssence::get_instance(), "global_start"));
$plugins->add_hook("global_intermediate", array(MyProfileEssence::get_instance(), "global_intermediate"));

class MyProfileEssence {
	
	private static $instance = null;
	
	public function activate() {
		global $db, $lang, $cache;
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

		$templates = array();
		$myprofile_cache = $cache->read("myprofile");
		$ver = str_replace(".", "_", trim($myprofile_cache["version"]));
		/* we're allowed to hard write the version inside the template, it will be updated every time there's a new version */
		$templates["myprofile_member_headerinclude"] = '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myprofile.js?ver=' . $ver . '"></script>';
		
		MyProfileUtils::insert_templates($templates);
		
		find_replace_templatesets("headerinclude", "#" . preg_quote('{$stylesheets}') . "#i", '{$stylesheets}{$myprofile_headerinclude}');		
	}
	
	public function deactivate() {
		global $db;
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
		$templates = array(
			"myprofile_member_headerinclude"
		);
		MyProfileUtils::delete_templates($templates);
		find_replace_templatesets("headerinclude", "#" . preg_quote('{$myprofile_headerinclude}') . "#i", '', 0);
	}
	
	/**
	 * Adds myprofile.js, myprofile.css
	 */
	public function global_start() {
		global $templates,  $myprofile_headerinclude, $templatelist;
		$myprofile_headerinclude = "";
		if(defined("THIS_SCRIPT") && THIS_SCRIPT == "member.php") {
			$templatelist .= ",myprofile_member_headerincluder";
		}
	}
	
	public function global_intermediate() {
		global $templates, $myprofile_headerinclude, $mybb;
		if(THIS_SCRIPT == "member.php")
		{
			eval("\$myprofile_headerinclude .= \"".$templates->get('myprofile_member_headerinclude')."\";");
		}
	}
	
	private function __construct() {
		
	}
	
	private function __clone() {
		
	}
	
	public static function get_instance() {
		if(null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
