<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("member_profile_end", array(MyProfileFriends::get_instance(), "member_profile_end"));

class MyProfileFriends {
	
	private static $instance = null;
	
	public function activate() {
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
		$templates = array();
		$templates["myprofile_friends"] = '<br /><table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
<tr>
<td width="100%" valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td colspan="4" class="thead"><strong>{$lang->mp_profile_friends} (Total {$count})</strong></td>
</tr>
{$friends}
</table>
</td>
</tr>
</table>';

		MyProfileUtils::insert_templates($templates);
		
		find_replace_templatesets("member_profile", "#" . preg_quote('{$contact_details}') . "#i", '{$myprofile_friends}{$contact_details}');
	}
	
	public function deactivate() {
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
		$templates = array(
			"myprofile_friends"
		);
		MyProfileUtils::delete_templates($templates);
		find_replace_templatesets("member_profile", "#" . preg_quote('{$myprofile_friends}') . "#i", '', 0);
	}
	
	public function member_profile_end() {
		global $lang, $templates, $db, $memprofile, $settings, $mybb, $count, $myprofile_friends, $theme;
		$lang->load("myprofile");
		// probably change the limit
		
		$friendsarray = array();
		$count = 0;
		
		if(strlen(trim($memprofile["buddylist"])) != 0) {
			$query = $db->simple_select("users", "*", "uid IN ({$memprofile['buddylist']})", array("limit" => 10));
			while($friend = $db->fetch_array($query)) {
				$friendsarray[] = $friend;
			}
			
			$query = $db->simple_select("users", "COUNT(*) as rows", "uid IN ({$memprofile['buddylist']})");
			$count = $db->fetch_field($query, "rows");
		}
		
		if(count($friendsarray) == 0) {
			$friends = '<tr>
	<td class="trow1">' . $memprofile['username'] . ' has not made any friends yet.</td>
	</tr>';
		}
		else {
			$friendfriends = $count == 1 ? "friend" : "friends";
			$friends = '<tr>
	<td class="trow1" colspan="4">' . $memprofile['username'] . ' has made ' . $count . ' ' . $friendfriends . ', ' . count($friendsarray) . ' of whom are displayed on this page.</td>
	</tr>';
			for($i = 0; $i < count($friendsarray); $i+=4) {
				$friends .= "<tr>";
				for($j = 0; $j < 4; $j++) {
					if(isset($friendsarray[$i + $j])) {
						$friendship = $friendsarray[$i + $j];
						// thanks MyBB 1.8
						$avatar = format_avatar($friendship["avatar"]);
						$username = format_name($friendship["username"], $friendship["usergroup"], $friendship["displaygroup"]);
						$friends .= '<td style="text-align:center;" class="' . alt_trow() . '" width="20%"><a href="' . get_profile_link($friendship['uid']) . '"><img src="' . $avatar["image"] . '" ' . $avatar["width_height"] . '><br />' . $username . '</a></td>';
					}
					else {
						$colspan = ($i + 4) - ($i + $j);
						$width = $colspan * 20;
						$friends .= '<td class="' . alt_trow() . '" width="' . $width . '%" colspan="' . $colspan . '"></td>';
						break;
					}
				}
				$friends .= "</tr>";
			}
		}
		
		eval("\$myprofile_friends .= \"".$templates->get('myprofile_friends')."\";");
		
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