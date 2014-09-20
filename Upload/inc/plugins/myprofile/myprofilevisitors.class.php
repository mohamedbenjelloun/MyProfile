<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("member_profile_end", array(MyProfileVisitors::get_instance(), "member_profile_end"));

class MyProfileVisitors {
	
	private static $instance = null;
	
	public function install() {
		global $db;
		$tables = array();
		$collation = $db->build_create_table_collation();
		
		if(! $db->table_exists("myprofilevisitors")) {
		$tables[] = "CREATE TABLE " . TABLE_PREFIX . "myprofilevisitors (
				`vid` int unsigned NOT NULL auto_increment,
				`uid` int unsigned NOT NULL,
				`vuid` int unsigned NOT NULL,
				`time` varchar(10) NOT NULL,
				PRIMARY KEY (`vid`)
			) ENGINE=MyISAM{$collation}";
		}
		
		foreach($tables as $table) {
			$db->write_query($table);
		}
	}
	
	public function is_installed() {
		global $db;
		return $db->table_exists("myprofilevisitors");
	}
	
	public function uninstall() {
		global $db;
		$db->write_query("DROP TABLE IF EXISTS " . TABLE_PREFIX . "myprofilevisitors");
	}
	
	public function activate() {
		global $db, $lang;
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
		$lang->load("config_myprofile");
		$templates = array();
		
		$templates["myprofile_visitors"] = '<br /><table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
<tr>
<td width="100%" valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td colspan="2" class="thead"><strong>{$lang->mp_profile_last_visitors}</strong></td>
</tr>
<tr>
<td class="trow1">{$lastvisitors}</td>
</tr>
</table>
</td>
</tr>
</table>
';
		MyProfileUtils::insert_templates($templates);
		
		$settinggroups = array(
			"name" => "myprofilevisitors",
			"title" => $lang->mp_myprofile_visitors,
			"description" => $lang->mp_myprofile_visitors_desc,
			"isdefault" => 0
		);
		
		$gid = MyProfileUtils::insert_settinggroups($settinggroups);
		
		$settings[] = array(
			"name" => "mpvisitorsenabled",
			"title" => $lang->mp_myprofile_visitors_enabled,
			"description" => $lang->mp_myprofile_visitors_enabled_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpvisitorsrecord",
			"title" => $lang->mp_myprofile_visitors_record,
			"description" => $lang->mp_myprofile_visitors_record_desc,
			"optionscode" => "select
5=5
10=10
15=15
20=20
30=30
50=50
100=100",
			"value" => "10",
			"gid" => $gid
		);
		
		MyProfileUtils::insert_settings($settings);
		find_replace_templatesets("member_profile", "#" . preg_quote('{$contact_details}') . "#i", '{$myprofile_visitors}{$contact_details}');
	}
	
	public function deactivate() {
		global $db;
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
		$templates = array(
			"myprofile_visitors"
		);
		MyProfileUtils::delete_templates($templates);
		
		$settings = array(
			"mpvisitorsenabled",
			"mpvisitorsrecord"
		);
		MyProfileUtils::delete_settings($settings);
		
		$settinggroups = array(
			"myprofilevisitors"
		);
		MyProfileUtils::delete_settinggroups($settinggroups);
		find_replace_templatesets("member_profile", "#" . preg_quote('{$myprofile_visitors}') . "#i", '', 0);
	}
	
	public function member_profile_end() {
		global $templates, $theme, $memprofile, $settings, $db, $mybb, $lang, $myprofile_visitors, $theme;
		if($settings["mpvisitorsenabled"] != "1") {
			return;
		}
		$lang->load("myprofile");
		// we don't care if I'm a guest, or I'm visiting my own profile
		if(isset($mybb->user["uid"]) && $mybb->user["uid"] > 0 && $mybb->user["uid"] != $memprofile["uid"]) {
		
			$query = $db->simple_select("myprofilevisitors", "*", "uid='{$db->escape_string($memprofile['uid'])}' AND vuid='{$db->escape_string($mybb->user['uid'])}'");
			
			if($db->num_rows($query) > 0) {
				// update
				$update_array = array(
					"time" => TIME_NOW
				);
				$db->update_query("myprofilevisitors", $update_array, "uid='{$db->escape_string($memprofile['uid'])}' AND vuid='{$db->escape_string($mybb->user['uid'])}'");
			}
			else {
				// insert
				$insert_array = array(
					"uid" => $db->escape_string($memprofile['uid']),
					"vuid" => $db->escape_string($mybb->user['uid']),
					"time" => TIME_NOW
				);
				$db->insert_query("myprofilevisitors", $insert_array);
			}
		}
		
		$query = $db->simple_select("myprofilevisitors", "*", "uid='{$db->escape_string($memprofile['uid'])}'", array(
			"limit" => isset($settings["mpvisitorsrecord"]) && is_numeric($settings["mpvisitorsrecord"]) ? $settings["mpvisitorsrecord"] : "10",
			"order_by" => "time",
			"order_dir" => "DESC"
		));
		
		if($db->num_rows($query) == 0) {
			$lastvisitors = $lang->mp_profile_visitors_no_visit;
		}
		else {
			$lastvisitors_array = array();
			while($visit = $db->fetch_array($query)) {
				$visitor = get_user($visit["vuid"]);
				if(!empty($visitor)) {
					$date = my_date($settings["dateformat"], $visit["time"]);
					$time = my_date($settings["timeformat"], $visit["time"]);
					
					$username = build_profile_link(format_name($visitor["username"], $visitor["usergroup"], $visitor["displaygroup"]), $visitor["uid"]);
					$lastvisitors_array[] = $username . " ({$date} - {$time})";
				}
			}
			$lastvisitors = implode(", ", $lastvisitors_array);
		}
		
		eval("\$myprofile_visitors .= \"".$templates->get('myprofile_visitors')."\";");
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