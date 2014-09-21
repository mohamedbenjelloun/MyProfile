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

/* load template */
$plugins->add_hook("global_start", array(MyProfileBuddyList::get_instance(), "global_start"));
$plugins->add_hook("member_profile_end", array(MyProfileBuddyList::get_instance(), "member_profile_end"));

class MyProfileBuddyList {
	
	private static $instance = null;
	
	public function install() {
		global $lang;
		MyProfileUtils::lang_load_config_myprofile();
		$settinggroups = array(
			"name" => "myprofilebuddylist",
			"title" => $lang->mp_myprofile_buddylist,
			"description" => $lang->mp_myprofile_buddylist_desc,
			"isdefault" => 0
		);
		
		$gid = MyProfileUtils::insert_settinggroups($settinggroups);
		
		$settings[] = array(
			"name" => "mpbuddylistenabled",
			"title" => $lang->mp_myprofile_buddylist_enabled,
			"description" => $lang->mp_myprofile_buddylist_enabled_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpbuddylistrecord",
			"title" => $lang->mp_myprofile_buddylist_record,
			"description" => $lang->mp_myprofile_buddylist_record_desc,
			"optionscode" => "select
4=4
8=8
12=12
16=16
20=20
24=24
40=40
80=80
100=100",
			"value" => "4",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpbuddylistavatarmaxdimensions",
			"title" => $lang->mp_myprofile_buddylist_avatar_max_dimensions,
			"description" => $lang->mp_myprofile_buddylist_avatar_max_dimensions_desc,
			"optionscode" => "text",
			"value" => "100x100",
			"gid" => $gid
		);
		
		MyProfileUtils::insert_settings($settings);
	}
	
	/* no is_installed() routine, swag! */
	
	public function activate() {
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
		$templates = array();
		$templates["myprofile_buddylist"] = '<br /><table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
<tr>
<td width="100%" valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td colspan="4" class="thead"><strong>{$lang->mp_profile_buddylist} ({$lang->mp_profile_comments_total} {$count})</strong></td>
</tr>
{$buddylist_count}
{$buddylist_content}
</table>
</td>
</tr>
</table>';

		$templates["myprofile_buddylist_buddy_count"] = '<tr>
<td class="trow1" colspan="{$count_colspan}">{$count_friends_text}</td>
</tr>';

		$templates["myprofile_buddylist_buddy"] = '<td style="text-align:center;" class="{$td_class}" width="20%"><a href="{$profile_link}"><img src="{$avatar_src}" {$avatar_width_height}><br />{$username}</a></td>';
		
		$templates["myprofile_buddylist_spacer"] = '<td class="{$td_class}" width="{$td_width}%" colspan="{$td_colspan}"></td>';
		
		$templates["myprofile_buddylist_row"] = '<tr>{$row_content}</tr>';
		
		MyProfileUtils::insert_templates($templates);
		
		find_replace_templatesets("member_profile", "#" . preg_quote('{$contact_details}') . "#i", '{$myprofile_buddylist}{$contact_details}');
	}
	
	public function deactivate() {
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
		$templates = array(
			"myprofile_buddylist",
			"myprofile_buddylist_buddy_count",
			"myprofile_buddylist_buddy",
			"myprofile_buddylist_row",
			"myprofile_buddylist_spacer"
		);
		MyProfileUtils::delete_templates($templates);
		find_replace_templatesets("member_profile", "#" . preg_quote('{$myprofile_buddylist}') . "#i", '', 0);
	}
	
	public function uninstall() {
		$settings = array(
			"mpbuddylistenabled",
			"mpbuddylistrecord",
			"mpbuddylistavatarmaxdimensions"
		);
		MyProfileUtils::delete_settings($settings);
		
		$settinggroups = array(
			"myprofilebuddylist"
		);
		MyProfileUtils::delete_settinggroups($settinggroups);
	}
	
	public function global_start() {
		global $templatelist;
		if(defined('THIS_SCRIPT') && THIS_SCRIPT == "member.php") {
			$templatelist .= ",myprofile_buddylist,myprofile_buddylist_buddy_count,myprofile_buddylist_buddy,myprofile_buddylist_row,myprofile_buddylist_spacer";
		}
	}
	
	public function member_profile_end() {
		global $lang, $templates, $db, $memprofile, $settings, $mybb, $count, $myprofile_buddylist, $theme;
		
		MyProfileUtils::lang_load_myprofile();
		
		$buddylist = array();
		$count = 0;
		
		if(my_strlen(trim($memprofile["buddylist"])) != 0) {
			$limit = is_numeric($settings["mpbuddylistrecord"]) ? (int) $settings["mpbuddylistrecord"] : 4;
			$query = $db->simple_select("users", "*", "uid IN ({$memprofile['buddylist']})", array("limit" => $limit));
			while($buddy = $db->fetch_array($query)) {
				$buddylist[] = $buddy;
			}
			/* update the counter */
			$query = $db->simple_select("users", "COUNT(*) as rows", "uid IN ({$memprofile['buddylist']})");
			$count = $db->fetch_field($query, "rows");
		}
		
		if(count($buddylist) == 0) {
			/* show them we've got no friends :( */
			$count_friends_text = $lang->sprintf($lang->mp_buddylist_no_friend, $memprofile["username"]);
			$count_colspan = 1;
		}
		else {
			$count_friends_text = $lang->sprintf($lang->mp_buddylist_friends, $memprofile["username"], $count, count($buddylist));
			$count_colspan = 4;
			$buddylist_content = "";
			for($col = 0; $col < count($buddylist); $col += 4) {
				$row_content = "";
				for($row = 0; $row < 4; $row++) {
					if(isset($buddylist[$col + $row])) {
						$buddy = $buddylist[$col + $row];
						$td_class = alt_trow();
						$profile_link = get_profile_link($buddy["uid"]);
						list($avatar_src, $avatar_width_height) = array_values(format_avatar($buddy["avatar"], $buddy["avatardimensions"], $settings["mpbuddylistavatarmaxdimensions"]));
						$username = htmlspecialchars_uni($buddy["username"]);
						eval("\$row_content .= \"".$templates->get('myprofile_buddylist_buddy')."\";");
					}
					else {
						$td_class = alt_trow();
						$td_colspan = ($i + 4) - ($i + $j);
						$td_width = $td_colspan * 20;
						eval("\$row_content .= \"".$templates->get('myprofile_buddylist_spacer')."\";");
						break;
					}
				}
				eval("\$buddylist_content .= \"".$templates->get('myprofile_buddylist_row')."\";");
			}
		}
		
		eval("\$buddylist_count .= \"".$templates->get('myprofile_buddylist_buddy_count')."\";");
		eval("\$myprofile_buddylist .= \"".$templates->get('myprofile_buddylist')."\";");
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