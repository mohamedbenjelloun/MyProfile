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

class MyProfileEssence {
	
	private static $instance = null;
	/* starting from version 0.5, we introduce a cache to know which version we are currently using, makes it easy to upgrade */
	public function install() {
		global $cache;
		$myprofile_info = myprofile_info();
		$myprofile_cache = array("version" => $myprofile_info["version"]);
		$cache->update("myprofile", $myprofile_cache);
	}
	
	public function uninstall() {
		global $cache;
		$cache->delete("myprofile");
	}
	
	public function activate() {
		global $db, $lang;
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

		$templates = array();
		
		$templates["myprofile_member_headerinclude"] = '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myprofile.js?ver=1800"></script>';
		
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
	 * Adds myprofile.js
	 */
	public function global_start() {
		global $mybb, $templates, $settings, $myprofile_headerinclude;
		$myprofile_headerinclude = "";
		if(defined("THIS_SCRIPT") && THIS_SCRIPT == "member.php") {
			/* abortion, we can't ask the global.php to load the following template as the templates class will do that anyway :) */
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
