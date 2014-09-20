<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("global_start", array(MyProfileEssence::get_instance(), "global_start"));

class MyProfileEssence {
	
	private static $instance = null;
	
	public function activate() {
		global $db, $lang;
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

		$templates = array();
		
		$templates["myprofile_member_headerinclude"] = '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/post.js?ver=1800"></script>
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/myprofile.js?ver=1800"></script>';
		
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
	 * Adds myprofile.js, tabulous.js, tabulous.css
	 */
	public function global_start() {
		global $mybb, $templates, $settings, $myprofile_headerinclude;
		$myprofile_headerinclude = "";
		if(defined("THIS_SCRIPT") && THIS_SCRIPT == "member.php") {
			$myprofile_tab_effect = $settings["mptabseffect"];
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