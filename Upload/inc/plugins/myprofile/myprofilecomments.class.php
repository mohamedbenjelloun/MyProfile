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

/* ajax */
$plugins->add_hook("xmlhttp", array(MyProfileComments::get_instance(), "xmlhttp"));

/* no ajax */
$plugins->add_hook("misc_start", array(MyProfileComments::get_instance(), "misc_start"));

/* admincp */
$plugins->add_hook("admin_formcontainer_end", array(MyProfileComments::get_instance(), "admin_formcontainer_end"));
$plugins->add_hook("admin_user_groups_edit_commit", array(MyProfileComments::get_instance(), "admin_user_groups_edit_commit"));

/* modcp */
/* - next version $plugins->add_hook("modcp_nav", array(MyProfileComments::get_instance(), "modcp_nav"));
$plugins->add_hook("modcp_start", array(MyProfileComments::get_instance(), "modcp_start")); */
$plugins->add_hook("modcp_reports_report", array(MyProfileComments::get_instance(), "modcp_reports_report"));

/* usercp */
$plugins->add_hook("usercp_do_options_end", array(MyProfileComments::get_instance(), "usercp_do_options_end"));
$plugins->add_hook("usercp_options_start", array(MyProfileComments::get_instance(), "usercp_options_start"));

/* report */
$plugins->add_hook("report_start", array(MyProfileComments::get_instance(), "report_start"));
$plugins->add_hook("report_end", array(MyProfileComments::get_instance(), "report_end"));
$plugins->add_hook("report_type", array(MyProfileComments::get_instance(), "report_type"));

/* member profile */
$plugins->add_hook("member_profile_end", array(MyProfileComments::get_instance(), "member_profile_end"));

/* comment notification */
$plugins->add_hook("global_start", array(MyProfileComments::get_instance(), "global_start"));


/* A custom MyAlerts Formatter for MyProfile Comments */
if(class_exists("MybbStuff_MyAlerts_Formatter_AbstractFormatter")) {
	class MyProfileCommentsMyAlertsFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter {
		/**
		 * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
		 *
		 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
		 * @return string The formatted alert string.
		 */
		public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert){
			return $this->lang->sprintf(
				$this->lang->mycustomalert_alert_format_string,
				$outputAlert['from_user_profilelink'],
				(int) $alert->getUserId(),
				$outputAlert['dateline']
			);
		}
		/**
		 * Init function called before running formatAlert(). Used to load language files and initialize other required resources.
		 *
		 * @return void
		 */
		public function init() {
			if (!$this->lang->mycustomalert) {
				$this->lang->load('mycustomalert');
			}
		}
		/**
		 * Let's not get lost in between things, this will return the alert type code
		 */
		public static function alert_type_code() {
			return "myprofilecomments";
		}
		public static function alert_type_title() {
			return "MyProfile Comments";
		}
	}
}


class MyProfileComments {
	
	private static $instance = null;
	
	public function install() {
		global $db, $cache, $lang, $mybbstuff_myalerts_alert_type_manager;
		MyProfileUtils::lang_load_config_myprofile();
		$tables = array();
		$collation = $db->build_create_table_collation();
		
		if(! $db->table_exists("myprofilecomments")) {
		$tables[] = "CREATE TABLE " . TABLE_PREFIX . "myprofilecomments (
				`cid` int unsigned NOT NULL auto_increment,
				`userid` int unsigned NOT NULL,
				`cuid` int unsigned NOT NULL,
				`message` text NOT NULL default '',
				`approved` int(1) NOT NULL default '0',
				`isprivate` int(1) NOT NULL default '0',
				`time` varchar(10) NOT NULL,
				PRIMARY KEY (`cid`)
			) ENGINE=MyISAM{$collation};";
		}
		
		foreach($tables as $table) {
			$db->write_query($table);
		}
		
		$db->add_column("usergroups", "canmanagecomments", "int(1) NOT NULL default '0'");
		$db->add_column("usergroups", "cansendcomments", "int(1) NOT NULL default '1'");
		$db->add_column("usergroups", "caneditowncomments", "int(1) NOT NULL default '1'");
		$db->add_column("usergroups", "candeleteowncomments", "int(1) NOT NULL default '0'");
		
		/* giving "manage" access to administrators, and delete own comments */
		$db->update_query("usergroups", array("canmanagecomments" => "1", "candeleteowncomments" => "1"), "gid='4'");
		/* revoking commenting + editing access from guests, banned and awaiting activation users */
		$db->update_query("usergroups", array("cansendcomments" => "0", "caneditowncomments" => "0"), "gid IN ('1', '5', '7')");
		
		$cache->update_usergroups();
		
		$db->add_column("users", "mpcommentsopen", "int(10) NOT NULL default '1'");
		$db->add_column("users", "mpcommentnotification", "int(10) NOT NULL default '1'");
		$db->add_column("users", "mpwhocancomment", "int(10) NOT NULL default '2'"); // 0=no one, 1=friendlist, 2=everyone
		$db->add_column("users", "mpnewcomments", "int(10) NOT NULL default '0'");
		$db->add_column("users", "mpcommentsapprove", "int(10) NOT NULL default '0'");
		
		
		$settinggroups = array(
			"name" => "myprofilecomments",
			"title" => $lang->mp_myprofile_comments,
			"description" => $lang->mp_myprofile_comments_desc,
			"isdefault" => 0
		);
		
		$gid = MyProfileUtils::insert_settinggroups($settinggroups);
		
		$settings = array();
		$settings[] = array(
			"name" => "mpcommentsenabled",
			"title" => $lang->mp_myprofile_comments_enabled,
			"description" => $lang->mp_myprofile_comments_enabled_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsajaxenabled",
			"title" => $lang->mp_myprofile_comments_ajax_enabled,
			"description" => $lang->mp_myprofile_comments_ajax_enabled_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsnotification",
			"title" => $lang->mp_myprofile_comments_notification,
			"description" => $lang->mp_myprofile_comments_notification_desc,
			"optionscode" => "select
myalertsoralertbar={$lang->mp_myprofile_comments_notification_myalerts_alertbar}
myalerts={$lang->mp_myprofile_comments_notification_myalerts}
alertbar={$lang->mp_myprofile_comments_notification_alertbar}
none={$lang->mp_myprofile_comments_notification_none}",
			"value" => "myalertsoralertbar",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsperpage",
			"title" => $lang->mp_myprofile_comments_perpage,
			"description" => $lang->mp_myprofile_comments_perpage_desc,
			"optionscode" => "select
5=5
6=6
7=7
8=8
9=9
10=10
15=15
25=25
30=30
	",
			"value" => "10",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsminlength",
			"title" => $lang->mp_myprofile_comments_min_length,
			"description" => $lang->mp_myprofile_comments_min_length_desc,
			"optionscode" => "text",
			"value" => "2",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsmaxlength",
			"title" => $lang->mp_myprofile_comments_max_length,
			"description" => $lang->mp_myprofile_comments_max_length_desc,
			"optionscode" => "text",
			"value" => "5000",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsignoreenabled",
			"title" => $lang->mp_myprofile_comments_ignore,
			"description" => $lang->mp_myprofile_comments_ignore_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentstimeedit",
			"title" => $lang->mp_myprofile_comments_edit_time,
			"description" => $lang->mp_myprofile_comments_edit_time_desc,
			"optionscode" => "select
0=0
5=5
10=10
15=15
20=20
30=30
45=45
60=60",
			"value" => "0",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsstatusenabled",
			"title" => $lang->mp_myprofile_comments_status_enabled,
			"description" => $lang->mp_myprofile_comments_status_enabled_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsallowimg",
			"title" => $lang->mp_myprofile_comments_allow_img,
			"description" => $lang->mp_myprofile_comments_allow_img_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsallowvideo",
			"title" => $lang->mp_myprofile_comments_allow_video,
			"description" => $lang->mp_myprofile_comments_allow_video_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsallowsmilies",
			"title" => $lang->mp_myprofile_comments_allow_smilies,
			"description" => $lang->mp_myprofile_comments_allow_smilies_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsallowmycode",
			"title" => $lang->mp_myprofile_comments_allow_mycode,
			"description" => $lang->mp_myprofile_comments_allow_mycode_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsfilterbadwords",
			"title" => $lang->mp_myprofile_comments_filter_badwords,
			"description" => $lang->mp_myprofile_comments_filter_badwords_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsallowhtml",
			"title" => $lang->mp_myprofile_comments_allow_html,
			"description" => $lang->mp_myprofile_comments_allow_html_desc,
			"optionscode" => "yesno",
			"value" => "0",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsshowwysiwyg",
			"title" => $lang->mp_myprofile_comments_show_wysiwyg,
			"description" => $lang->mp_myprofile_comments_show_wysiwyg_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsclosedonbanned",
			"title" => $lang->mp_myprofile_comments_closed_on_banned,
			"description" => $lang->mp_myprofile_comments_closed_on_banned_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		$settings[] = array(
			"name" => "mpcommentsshowstats",
			"title" => $lang->mp_myprofile_comments_show_stats,
			"description" => $lang->mp_myprofile_comments_show_stats_desc,
			"optionscode" => "yesno",
			"value" => "1",
			"gid" => $gid
		);
		
		MyProfileUtils::insert_settings($settings);
		
		if(MyProfileUtils::myalerts_exists()) {
			$alertType = new MybbStuff_MyAlerts_Entity_AlertType();
			$alertType = $alertType
							->setCode(MyProfileCommentsMyAlertsFormatter::alert_type_code())
							->setTitle(MyProfileCommentsMyAlertsFormatter::alert_type_title())
							->setEnabled(true);
			
			$mybbstuff_myalerts_alert_type_manager->registerAlertType($alertType);
		}
	}
	
	public function is_installed() {
		global $db;
		return $db->table_exists("myprofilecomments");
	}
	
	public function uninstall() {
		global $db, $cache;
		
		$db->drop_table("myprofilecomments");
		
		$db->drop_column("usergroups", "canmanagecomments");
		$db->drop_column("usergroups", "cansendcomments");
		$db->drop_column("usergroups", "caneditowncomments");
		$db->drop_column("usergroups", "candeleteowncomments");
		
		$cache->update_usergroups();
		
		$db->drop_column("users", "mpcommentsopen");
		$db->drop_column("users", "mpcommentnotification");
		$db->drop_column("users", "mpwhocancomment");
		$db->drop_column("users", "mpnewcomments");
		$db->drop_column("users", "mpcommentsapprove");
		
		$settings = array(
			"mpcommentsenabled",
			"mpcommentsajaxenabled",
			"mpcommentsnotification",
			"mpcommentsperpage",
			"mpcommentsminlength",
			"mpcommentsmaxlength",
			"mpcommentsignoreenabled",
			"mpcommentstimeedit",
			"mpcommentsstatusenabled",
			"mpcommentsallowimg",
			"mpcommentsallowvideo",
			"mpcommentsallowsmilies",
			"mpcommentsallowmycode",
			"mpcommentsfilterbadwords",
			"mpcommentsallowhtml",
			"mpcommentsshowwysiwyg",
			"mpcommentsclosedonbanned",
			"mpcommentsshowstats"
		);
		MyProfileUtils::delete_settings($settings);
		
		$settinggroups = array(
			"myprofilecomments"
		);
		MyProfileUtils::delete_settinggroups($settinggroups);
		
		if(MyProfileUtils::myalerts_exists()) {
			$mybbstuff_myalerts_alert_type_manager->removeAlertTypeByCode(MyProfileCommentsMyAlertsFormatter::alert_type_code());
		}
	}
	
	public function activate() {
		global $db, $cache;
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
		
		
		$templates = array();
		
		$templates["myprofile_comments_content"] = '{$comments_form}
{$comment_form_script}
{$comments_table}';
		
		$templates["myprofile_comments_form"] = '<form action="misc.php" method="post" name="comments-form" id="comments-form">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="action" value="comments-add" />
<input type="hidden" name="memberuid" value="{$memprofile[\'uid\']}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->mp_profile_comments_add_comment}</strong></td>
</tr>
{$status}
<tr>
<td class="trow2">
<textarea id="message" name="message" rows="10" cols="70" tabindex="2"></textarea>
{$codebuttons}
</td>
</tr>
<tr>
<td class="trow2">
<input id="submit_comment" type="submit" class="button" name="submit" value="{$lang->mp_profile_comments_add_comment}" tabindex="3" />
<span id="spinner_span"></span>
</td>
</tr>
{$modoptions}
</table>
</form>';

		$templates["myprofile_comments_form_modoptions"] = '<tr>
<td class="trow2">
<span><strong>{$lang->mp_comments_moderation} : </strong></span>
<button type="button" class="button comment-action comments-delete-all">{$lang->mp_comments_action_delete_all_comments}</button>
<span class="spinner_delete_all_span"></span>
</td>
</tr>';

		$templates["myprofile_comments_stats"] = '<tr>
<td class="trow2"><strong>{$lang->mp_profile_comments_comments}</strong></td>
<td class="trow2">{$stats_total} ({$stats_sent} {$lang->mp_comments_stats_sent} | {$stats_received} {$lang->mp_comments_stats_received})</td>
</tr>';

		$templates["myprofile_comments_form_script"] = '<script>
lang.mp_comments_comment_wrong_length = "{$lang->mp_comments_comment_wrong_length}";
lang.mp_comments_confirm_delete = "{$lang->mp_comments_confirm_delete}";
lang.mp_comments_confirm_delete_all = "{$lang->mp_comments_confirm_delete_all}";
MyProfile.memberUid = {$comments_memberuid};
MyProfile.ajax = {$comments_ajax};
MyProfile.commentsMinLength = {$comments_minlength};
MyProfile.commentsMaxLength = {$comments_maxlength};
MyProfile.commentsSCEditor = {$comments_sceditor};
MyProfile.commentsPage = {$comments_page};
</script>';

		$templates["myprofile_comments_form_status"] = '<tr>
<td class="trow1">
<span>{$lang->mp_comments_comment_status} : </span>
<select class="{$status_select_class}" name="isprivate">
<option value="0" {$comment_public_selected}>{$lang->mp_comments_comment_public}</option>
<option value="1" {$comment_private_selected}>{$lang->mp_comments_comment_private}</option>
</select>
</td>
</tr>';

		$templates["myprofile_comments_table"] = '{$comments_separator}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->mp_profile_comments_comments} ({$lang->mp_profile_comments_total} <span id="comments-total">{$comments_total}</span>{$useroptions})</strong></td>
</tr>
<tr class="comments-pagination">
<td colspan="2" {$comments_pagination_style}>{$comments_pagination}</td>
</tr>
<tbody class="comments-content">
{$comments_content}
</tbody>
<tr class="comments-pagination">
<td colspan="2" {$comments_pagination_style}>{$comments_pagination}</td>
</tr>
</table>
<br />';

		$templates["myprofile_comments_comment"] = '<tr width="100%" data-cid="{$comment[\'cid\']}">
<td class="trow2 {$trow_class}" rowspan="2" style="text-align: center; vertical-align: top;" data-cid="{$comment[\'cid\']}">
<img src="{$avatar_src}" {$avatar_width_height} alt />
</td>
<td style="height: 20px;" class="trow1 {$trow_class}" width="100%" data-cid="{$comment[\'cid\']}">
{$profile_link} <small style="font-size: 10px;">({$date} - {$time}) <em>{$comment_private}</em></small><br />
<span style="font-size: 10px;" data-cid="{$comment[\'cid\']}">
{$comments_approve}
{$comments_reply}
{$comments_edit}
{$comments_delete}
{$comments_report}
</span>
</td>
</tr>
<tr>
<td class="trow2 comment_message scaleimages {$trow_class}" style="max-width: 400px; overflow: hidden;" data-cid="{$comment[\'cid\']}">
{$message}
</td>
</tr>';

		$templates["myprofile_comments_no_comment"] = '<tr width="100%">
<td class="trow2">{$lang->mp_comments_no_comment_to_display}</td>
</tr>';

		$templates["myprofile_comments_comment_approve"] = '<form action="misc.php" method="post" style="display: inline;">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="action" value="comments-approve" />
<input type="hidden" name="cid" value="{$comment[\'cid\']}" />
<input type="hidden" name="memberuid" value="{$comment[\'userid\']}" />
<button class="comments-approve comments-action button">{$lang->mp_comments_action_approve}</button>
</form>';

		$templates["myprofile_comments_comment_reply"] = '<form action="{$mybb->settings[\'bburl\']}/member.php" method="get" style="display: inline;">
<input type="hidden" name="action" value="profile" />
<input type="hidden" name="uid" value="{$commentor_uid}" />
<button>{$lang->mp_comments_action_reply}</button>
</form>';
		
		$templates["myprofile_comments_comment_edit"] = '<button class="comments-edit comments-action button">{$lang->mp_comments_action_edit}</button>';
		
		$templates["myprofile_comments_comment_delete"] = '<form action="misc.php" method="post" style="display: inline;">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="action" value="comments-delete" />
<input type="hidden" name="cid" value="{$comment[\'cid\']}" />
<input type="hidden" name="memberuid" value="{$comment[\'userid\']}" />
<button class="comments-delete comments-action button">{$lang->mp_comments_action_delete}</button>
</form>';
		
		$templates["myprofile_comments_comment_report"] = '<button class="comments-report comments-action button">{$lang->mp_comments_action_report}</button>';
		
		$templates["myprofile_comments_report_reasons"] = '<tr>
<td class="trow1" align="left" style="width: 25%"><span class="smalltext"><strong>{$lang->report_reason}</strong></span></td>
<td class="trow1" align="left">
<select name="reason" id="report_reason">
<option value="rules">{$lang->report_reason_rules}</option>
<option value="bad">{$lang->report_reason_bad}</option>
<option value="spam">{$lang->report_reason_spam}</option>
<option value="other">{$lang->report_reason_other}</option>
</select>
</td>
</tr>
<tr id="reason">
<td class="trow2">&nbsp;</td>
<td class="trow2" align="left">
<div>{$lang->report_reason_other_description}</div>
<input type="text" class="textbox" name="comment" size="40" maxlength="250" />
</td>
</tr>
<tr>
	<td colspan="2" class="tfoot"><input type="submit" class="button" value="{$report_title}" /></td>
</tr>';
	
		$templates["myprofile_comments_usercp"] = '<legend><strong>{$lang->mp_my_profile}</strong></legend>
<table cellspacing="0" cellpadding="2">
<tr>
<td valign="top" width="1"><input type="checkbox" class="checkbox" name="mpcommentsopen" id="mpcommentsopen" value="1" {$mpcommentsopen} /></td>
<td><span class="smalltext"><label for="mpcommentsopen">{$lang->mp_comments_open}</label></span></td>
</tr>
<tr>
<td colspan="2"><span class="smalltext">{$lang->mp_who_can_leave_comments}</span></td>
</tr>
<tr>
<td colspan="2">
<select name="mpwhocancomment" id="mpwhocancomment">
<option value="0" {$nobodycanleavecomments}>{$lang->mp_nobody_can_leave_comments}</option>
<option value="1" {$friendlistcanleavecomment}>{$lang->mp_friendlist_can_leave_comments}</option>
<option value="2" {$anyonecanleavecomments}>{$lang->mp_anyone_can_leave_comments}</option>
</td>
</tr>
<tr>
<td valign="top" width="1"><input type="checkbox" class="checkbox" name="mpcommentsapprove" id="mpcommentsapprove" value="1" {$mpcommentsapprove} /></td>
<td><span class="smalltext"><label for="mpcommentsapprove">{$lang->mp_comments_approve}</label></span></td>
</tr>
<tr>
<td valign="top" width="1"><input type="checkbox" class="checkbox" name="mpcommentnotification" id="mpcommentnotification" value="1" {$mpcommentnotification} /></td>
<td><span class="smalltext"><label for="mpcommentnotification">{$lang->mp_comments_notification}</label></span></td>
</tr>
</table>
</fieldset>
<br />
<fieldset class="trow2">';

		$templates["myprofile_modcp_nav_comments"] = '<tr><td class="trow1 smalltext"><a href="modcp.php?action=myprofilecomments" class="modcp_nav_item modcp_nav_ipsearch">{$lang->mp_myprofile_comments}</a></td></tr>';
		
		$templates["myprofile_comments_edit"] = '
<div class="modal">
<div style="overflow-y: auto; max-height: 400px;" class="modal_2">
<form action="misc.php" method="post" id="comments-edit-form" name="comments-edit-form">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="action" value="comments-do-edit" />
<input type="hidden" name="memberuid" value="{$memprofile[\'uid\']}" />
<input type="hidden" name="no_modal" value="1" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
	<td class="thead" colspan="2"><strong>{$lang->mp_profile_comments_edit_comment}</strong></td>
</tr>
{$status}
<tr>
<td class="trow2">
<textarea id="message_edit" name="message_edit" rows="10" cols="70" tabindex="2"></textarea>
</td>
</tr>
<tr>
<td class="trow2">
<input type="submit" class="comments-edit-submit button" name="submit" value="{$lang->mp_profile_comments_edit_comment}" tabindex="3" data-cid="{$cid}" />
</td>
</tr>
</table>
</form>
<script>$(function() {
var original_text = "{$original_text}";
var instance = {};
if(MyProfile.commentsSCEditor) {
$("#message_edit").sceditor(opt_editor);
instance = $("#message_edit").sceditor(\'instance\');
}
else {
instance = $("#message_edit");
}
instance.val(original_text);
});
</script>
</div>
</div>';

		$templates["myprofile_comments_alertbar"] = '<div class="pm_alert" id="mp_comments_notice">
<div class="float_right"><a href="#" id="mp_comments_notice_url" title="{$lang->mp_comments_dismiss_notice}"><img src="{$theme[\'imgdir\']}/dismiss_notice.png" alt="{$lang->mp_comments_dismiss_notice}" title="{$lang->mp_comments_dismiss_notice}"></a></div>
<div>{$comments_text}</div>
</div>
<script>
$(document).ready(function() {
	$(document).on("click", "a#mp_comments_notice_url", myprofile_dismiss_alertbar);
	
	function myprofile_dismiss_alertbar() {
		$.ajax({
			"url": rootpath + "/xmlhttp.php",
			"data": {
				"action" : "comments-dismiss",
				"my_post_key" : my_post_key
			},
			"type": "POST"
		});
		$("#mp_comments_notice").remove();
	}
});
</script>';
/* - next version
		$templates["myprofile_comments_modcp_start"] = '<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->warning_logs}</title>
{$headerinclude}
</head>
<body>
	{$header}
	<table width="100%" border="0" align="center">
		<tr>
			{$modcp_nav}
			<td valign="top">
				<form action="modcp.php" method="get">
					<input type="hidden" name="action" value="warninglogs" />
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead" colspan="2"><strong>{$lang->filter_warning_logs}</strong></td>
						</tr>
						<tr>
							<td class="trow1" width="25%"><strong>{$lang->filter_warned_user}</strong></td>
							<td class="trow1" width="75%"><input type="text" name="filter[username]" id="username" value="{$mybb->input[\'filter\'][\'username\']}" class="textbox" /></td>
						</tr>
						<tr>
							<td class="trow2" width="25%"><strong>{$lang->filter_issued_by}</strong></td>
							<td class="trow2" width="75%"><input type="text" name="filter[mod_username]" value="{$mybb->input[\'filter\'][\'mod_username\']}" class="textbox" /></td>
						</tr>
						<tr>
							<td class="trow1" width="25%"><strong>{$lang->filter_reason}</strong></td>
							<td class="trow1" width="75%"><input type="text" name="filter[reason]" value="{$mybb->input[\'filter\'][\'reason\']}" class="textbox" /></td>
						</tr>
						<tr>
							<td class="trow2" width="25%"><strong>{$lang->sort_by}</strong></td>
							<td class="trow2" width="75%">
								<select name="filter[sortby]">
									<option value="username"{$sortbysel[\'username\']}>{$lang->username}</option>
									<option value="issuedby"{$sortbysel[\'issuedby\']}>{$lang->issued_by}</option>
									<option value="dateline"{$sortbysel[\'dateline\']}>{$lang->issued_date}</option>
									<option value="expires"{$sortbysel[\'expires\']}>{$lang->expiry_date}</option>
								</select>
								{$lang->in}
								<select name="filter[order]">
									<option value="asc"{$ordersel[\'asc\']}>{$lang->asc}</option>
									<option value="desc"{$ordersel[\'desc\']}>{$lang->desc}</option>
								</select>
								{$lang->order}
							</td>
						</tr>
						<tr>
							<td class="trow1" width="25%"><strong>{$lang->per_page}</strong></td>
							<td class="trow1" width="75%"><input type="text" name="filter[per_page]" value="{$per_page}" class="textbox" /></td>
						</tr>
					</table>
					<br />
					<div align="center">
						<input type="submit" class="button" value="{$lang->filter_warning_logs}" />
					</div>
				</form>
				{$multipage}
				<br />
				<form action="modcp.php" method="get">
					<input type="hidden" name="action" value="warninglogs" />
					<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
						<tr>
							<td class="thead" colspan="2"><strong>{$lang->filter_warning_logs}</strong></td>
						</tr>
						<tr>
							<td class="trow1" width="25%"><strong>{$lang->filter_warned_user}</strong></td>
							<td class="trow1" width="75%"><input type="text" name="filter[username]" id="username" value="{$mybb->input[\'filter\'][\'username\']}" class="textbox" /></td>
						</tr>
						<tr>
							<td class="trow2" width="25%"><strong>{$lang->filter_issued_by}</strong></td>
							<td class="trow2" width="75%"><input type="text" name="filter[mod_username]" value="{$mybb->input[\'filter\'][\'mod_username\']}" class="textbox" /></td>
						</tr>
						<tr>
							<td class="trow1" width="25%"><strong>{$lang->filter_reason}</strong></td>
							<td class="trow1" width="75%"><input type="text" name="filter[reason]" value="{$mybb->input[\'filter\'][\'reason\']}" class="textbox" /></td>
						</tr>
						<tr>
							<td class="trow2" width="25%"><strong>{$lang->sort_by}</strong></td>
							<td class="trow2" width="75%">
								<select name="filter[sortby]">
									<option value="username"{$sortbysel[\'username\']}>{$lang->username}</option>
									<option value="issuedby"{$sortbysel[\'issuedby\']}>{$lang->issued_by}</option>
									<option value="dateline"{$sortbysel[\'dateline\']}>{$lang->issued_date}</option>
									<option value="expires"{$sortbysel[\'expires\']}>{$lang->expiry_date}</option>
								</select>
								{$lang->in}
								<select name="filter[order]">
									<option value="asc"{$ordersel[\'asc\']}>{$lang->asc}</option>
									<option value="desc"{$ordersel[\'desc\']}>{$lang->desc}</option>
								</select>
								{$lang->order}
							</td>
						</tr>
						<tr>
							<td class="trow1" width="25%"><strong>{$lang->per_page}</strong></td>
							<td class="trow1" width="75%"><input type="text" name="filter[per_page]" value="{$per_page}" class="textbox" /></td>
						</tr>
					</table>
					<br />
					<div align="center">
						<input type="submit" class="button" value="{$lang->filter_warning_logs}" />
					</div>
				</form>
			</td>
		</tr>
	</table>
	{$footer}
<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js"></script>
<script type="text/javascript">
<!--
if(use_xmlhttprequest == "1")
{
	MyBB.select2();
	$("#username").select2({
		placeholder: "{$lang->search_user}",
		minimumInputLength: 3,
		maximumSelectionSize: 3,
		multiple: false,
		ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
			url: "xmlhttp.php?action=get_users",
			dataType: \'json\',
			data: function (term, page) {
				return {
					query: term, // search term
				};
			},
			results: function (data, page) { // parse the results into the format expected by Select2.
				// since we are using custom formatting functions we do not need to alter remote JSON data
				return {results: data};
			}
		},
		initSelection: function(element, callback) {
			var value = $(element).val();
			if (value !== "") {
				callback({
					id: value,
					text: value
				});
			}
		},
	});
}
// -->
</script>
</body>
</html>';
*/
		MyProfileUtils::insert_templates($templates);
		find_replace_templatesets("usercp_options", '#' . preg_quote('<legend><strong>{$lang->date_time_options}</strong></legend>') . '#i', '{$myprofile_comments_usercp}<legend><strong>{$lang->date_time_options}</strong></legend>');
		find_replace_templatesets("modcp_nav_users", "#" . preg_quote('{$nav_editprofile}') . "#i", '{$nav_editprofile}{$nav_myprofilecomments}');
		find_replace_templatesets("member_profile", "#" . preg_quote('{$modoptions}') . "#i", '{$modoptions}{$myprofile_comments}');
		find_replace_templatesets("member_profile", "#" . preg_quote('{$warning_level}') . "#i", '{$warning_level}{$myprofile_comments_stats}');
		find_replace_templatesets('header', "#" . preg_quote('{$unreadreports}') . "#i", '{$unreadreports}{$myprofile_alertbar}');
	}
	
	public function deactivate() {
		global $db;
		require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
		
		$templates = array(
			"myprofile_comments_content",
			"myprofile_comments_stats",
			"myprofile_comments_form",
			"myprofile_comments_form_modoptions",
			"myprofile_comments_form_script",
			"myprofile_comments_form_status",
			"myprofile_comments_table",
			"myprofile_comments_comment",
			"myprofile_comments_no_comment",
			"myprofile_comments_comment_approve",
			"myprofile_comments_comment_reply",
			"myprofile_comments_comment_edit",
			"myprofile_comments_comment_delete",
			"myprofile_comments_comment_report",
			"myprofile_comments_report_reasons",
			"myprofile_comments_usercp",
			"myprofile_modcp_nav_comments",
			"myprofile_comments_edit",
			"myprofile_comments_alertbar",
			// - next version "myprofile_comments_modcp_start"
		);
		MyProfileUtils::delete_templates($templates);
		
		find_replace_templatesets("member_profile", "#" . preg_quote('{$myprofile_comments}') . "#i", '', 0);
		find_replace_templatesets("member_profile", "#" . preg_quote('{$myprofile_comments_stats}') . "#i", '', 0);
		find_replace_templatesets("usercp_options", '#' . preg_quote('{$myprofile_comments_usercp}') . '#i', '', 0);
		find_replace_templatesets("modcp_nav_users", "#" . preg_quote('{$nav_myprofilecomments}') . "#i", '', 0);
		find_replace_templatesets('header', "#" . preg_quote('{$myprofile_alertbar}') . "#i", '', 0);
	}
	
	public function global_start() {
		global $mybb, $db, $settings, $lang, $templates, $myprofile_alertbar, $formatterManager, $templatelist;
		if(defined('THIS_SCRIPT') && THIS_SCRIPT == "member.php") {
			/* load our templates */
			$templatelist .= ",myprofile_comments_content,myprofile_comments_stats,myprofile_comments_form,myprofile_comments_form_modoptions,myprofile_comments_form_script,myprofile_comments_form_status,myprofile_comments_table,myprofile_comments_comment,myprofile_comments_no_comment,myprofile_comments_comment_approve,myprofile_comments_comment_reply,myprofile_comments_comment_edit,myprofile_comments_comment_delete,myprofile_comments_comment_report,multipage_page_current,multipage_page,multipage_nextpage,multipage,codebuttons";
			/* if we are in member.php, and there is logged in user, or the user is viewing own profile */
			if(isset($mybb->user["uid"], $mybb->input["uid"], $mybb->input["action"]) && $mybb->input["action"] == "profile" && $mybb->user["uid"] > 0 && $mybb->input["uid"] == $mybb->user["uid"]) {
				/* if the user has no new comments, why reset? */
				if($mybb->user["mpnewcomments"] != "0") {
					$update_array = array(
						"mpnewcomments" => "0"
					);
					$db->update_query("users", $update_array, "uid='{$mybb->user['uid']}'", "1");
					/* be kind and update the user? */
					$mybb->user["mpnewcomments"] = "0";
				}
			}
		}
		
		/* did the user choose to see notification? and is the notification system "MyAlerts or alert bar" or "Alert bar"? and does the user have new comments */
		if($mybb->user["uid"] > 0 && $mybb->user["mpcommentnotification"] == "1" && $mybb->user["mpnewcomments"] > 0 && in_array($settings["mpcommentsnotification"], array("myalertsoralertbar", "alertbar"))) {
			/* we will only keep going on if the admin selected "Alert bar" / "MyAlerts or Alert bar" and MyAlerts isn't installed */
			if($settings["mpcommentsnotification"] == "alertbar" || ! MyProfileUtils::myalerts_exists()) {
				/* ok show that bar! */
				MyProfileUtils::lang_load_myprofile();
				$comments_text = $lang->sprintf($lang->mp_comments_new_comments, "<a href=\"{$settings['bburl']}/member.php?action=profile&uid={$mybb->user['uid']}\">", $mybb->user["mpnewcomments"], "</a>");
				eval("\$myprofile_alertbar .= \"".$templates->get("myprofile_comments_alertbar")."\";");
			}
		}
		
		/* now if the admin has chosen to activate MyAlerts, hook my custom alert formatter classer on global_start */
		if(in_array($settings["mpcommentsnotification"], array("myalertsoralertbar", "myalerts")) && MyProfileUtils::myalerts_exists() && !empty($formatterManager)) {
			if(class_exists("MyProfileCommentsMyAlertsFormatter")) {
				$formatterManager->registerFormatter(new MyProfileCommentsMyAlertsFormatter($mybb, $lang, MyProfileCommentsMyAlertsFormatter::alert_type_code()));
			}
		}
	}
	
	
	public function xmlhttp() {
		global $mybb, $settings;
		/* are we providing a string action ? */
		if(! isset($mybb->input["action"]) || ! is_string($mybb->input["action"])) {
			return;
		}
		if($mybb->input["action"] == "comments-do-edit") {
			$this->xmlhttp_comments_do_edit();
			return;
		}
		elseif($mybb->input["action"] == "comments-dismiss") {
			$this->xmlhttp_comments_dismiss();
			return;
		}
		elseif($mybb->input["action"] == "comments-delete-all") {
			$this->xmlhttp_comments_delete_all();
			return;
		}
		/* are we allowed to perform ajax ? */
		if($settings["mpcommentsajaxenabled"] != "1") {
			/* bad admin :( */
			return;
		}
		switch($mybb->input["action"]) {
			case "comments-retrieve":
				$this->xmlhttp_comments_retrieve();
			break;
			case "comments-add":
				$this->xmlhttp_comments_add();
			break;
			case "comments-approve":
				$this->xmlhttp_comments_approve();
			break;
			case "comments-delete":
				$this->xmlhttp_comments_delete();
			break;
			default:
				return; /* no need to break, but.. */
			break;
		}
	}
	
	public function xmlhttp_comments_dismiss() {
		global $mybb, $db;
		if($mybb->request_method != "post") {
			return;
		}
		/* do we have a valid post key? */
		if(! isset($mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || ! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		if($mybb->user["uid"] > 0 && $mybb->user["mpnewcomments"] > "0") {
			$update_array = array(
				"mpnewcomments" => "0"
			);
			$db->update_query("users", $update_array, "uid='{$mybb->user['uid']}'", "1");
			echo "1";
		}
	}
	
	public function xmlhttp_comments_retrieve() {
		global $mybb, $settings, $db, $lang, $templates;
		/* are we making a POST request? */
		if($mybb->request_method != "post") {
			return;
		}
		/* do we have a valid post key? */
		if(! isset($mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || ! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		
		MyProfileUtils::lang_load_myprofile();
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		if(! isset($mybb->input["memberuid"]) || ! is_numeric($mybb->input["memberuid"]) || $mybb->input["memberuid"] <= 0) {
			$result->error = true;
			$result->error_message = $lang->mp_profile_comments_invalid_user;
			MyProfileUtils::output_json($result);
		}
		
		$memberuid = (int) $mybb->input["memberuid"];
		$memprofile = get_user($memberuid);
		
		$page = isset($mybb->input["page"]) && is_numeric($mybb->input["page"]) && $mybb->input["page"] >= 1 ? (int) $mybb->input["page"] : 1;
		// math PRO :D
		$limit_start = ($page - 1) * $settings["mpcommentsperpage"];
		
		$comments = $this->comments_retrieve_from_db($page, $memprofile);
		$result->comments = $comments;
		if(count($comments) == 0) {
			eval("\$result->empty = \"".$templates->get('myprofile_comments_no_comment')."\";");
		}
		$rows = $this->comments_count($memprofile);
		$result->rows = $rows;
		$result->pagination = multipage($rows, $settings['mpcommentsperpage'], $page, "#comments/{page}");
		MyProfileUtils::output_json($result);
	}
	
	/**
	 * Retrieve comments from DB, for the page number {$id}, corresponding to the user with a uid {$memberuid}.
	 * If you are retrieving a comment, change $type to 'comment', and provide the comment's ID you're looking for as $id. $type can either be 'comment' or 'page'.
	 * @return array either way, this function will return an array of comments already processed, ONLY comments that the user has the right to read will be returned.
	 */
	public function comments_retrieve_from_db($id, $memprofile, $type = 'page') {
		global $db, $settings, $mybb;
		/* memberuid = 0 if no memberprofile have been provided */
		
		$memberuid = !empty($memprofile) ? $memprofile["uid"] : 0;
		$where = "c.cuid=u.uid AND c.userid='{$memberuid}'";
		$fields = "*";
		
		/* If I'm not visiting my profile, and I cannot manage comments, add some restrictions */
		if($memprofile["uid"] != $mybb->user["uid"] && ! $this->can_manage_comments()) {
			/* Fetch comments that are either approved, or not yet approved but I'm their author. And comments that are either public, or private but I'm their author */
			$where .= " AND (c.approved='1' OR (c.approved='0' AND c.cuid='{$mybb->user['uid']}')) AND (c.isprivate='0' OR (c.isprivate='1' AND c.cuid='{$mybb->user['uid']}'))";
		}
		
		/* If I'm the author, I shall always see my own comments */
		if($type == "comment") {
			$where .= " AND c.cid='{$id}'";
			$limit = " LIMIT 1";
		}
		elseif($type == "page") {
			$limit_start = ($id - 1) * $settings['mpcommentsperpage'];
			$limit = " LIMIT {$limit_start}, {$settings['mpcommentsperpage']}";
		}
		else {
			$limit = "";
			$fields = "COUNT(*) AS rows";
		}
		
		$query = $db->query("SELECT {$fields} FROM " . TABLE_PREFIX . "myprofilecomments c, " . TABLE_PREFIX . "users u 
				WHERE {$where} ORDER BY c.time DESC{$limit}");
		
		if($type == "comment") {
			return $db->fetch_array($query);
		}
		elseif($type == "page") {
			$results = array();
			while($comment = $db->fetch_array($query)) {
				$results[] = $this->comment_process($comment, $memprofile);
			}
			return $results;
		}
		else {
			return $db->fetch_field($query, "rows");
		}
	}
	
	/**
	 * Retrieve only one comment from DB, alias for comments_retrieve_from_db($cid, $memberuid, 'comment');
	 * This function will return an array containing exactly ONE comment if the $cid has been found, and the user requesting is able to see it. An empty array otherwise.
	 */
	public function comment_retrieve_from_db($cid, $memprofile) {
		return $this->comments_retrieve_from_db($cid, $memprofile, "comment");
	}
	
	public function comments_count($memprofile) {
		return $this->comments_retrieve_from_db(null, $memprofile, "count");
	}
	
	public function xmlhttp_comments_add() {
		global $mybb, $lang, $settings;
		/* post request ? */
		if($mybb->request_method != "post") {
			return;
		}
		/* valid post key ? */
		if(! isset($mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || ! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		/* have we provided all the necessary fields ? With the appropriate types ? */
		if(! isset($mybb->input["message"], $mybb->input["memberuid"])
			|| ! is_string($mybb->input["message"]) || ! is_numeric($mybb->input["memberuid"])) {
			return;
		}
		MyProfileUtils::lang_load_myprofile();
		$memberuid = (int) $mybb->input["memberuid"];
		$memprofile = get_user($memberuid);
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		/* can the user send comments to the profile's owner? */
		if(! $this->can_send_comments($mybb->user, $memprofile, $result->error_message)) {
			$result->error = true;
			MyProfileUtils::output_json($result);
		}
		
		$message = $mybb->input["message"];
		/* is the comment private? is the sneaky user trying to set it to private while the private status is globally disabled? */
		$isprivate = isset($mybb->input["isprivate"]) && in_array($mybb->input["isprivate"], array("0", "1")) && $settings["mpcommentsstatusenabled"] == "1" ? (int) $mybb->input["isprivate"] : 0;
		/* are we respecting the comment lengths? */
		if(my_strlen(trim($message)) < $settings["mpcommentsminlength"] || my_strlen($message) > $settings["mpcommentsmaxlength"]) {
			$result->error = true;
			$result->error_message = $lang->sprintf($lang->mp_comments_comment_wrong_length, $settings["mpcommentsminlength"], $settings["mpcommentsmaxlength"]);
			MyProfileUtils::output_json($result);
		}
		
		/* go get em */
		$result = $this->insert_comment($message, $memberuid, $isprivate);
		MyProfileUtils::output_json($result);
	}
	
	public function xmlhttp_comments_do_edit() {
		global $mybb, $lang, $settings;
		/* post request ? */
		if($mybb->request_method != "post") {
			return;
		}
		/* valid post key ? */
		if(! isset($mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || ! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		/* have we provided all the necessary fields ? With the appropriate types ? */
		if(! isset($mybb->input["message"], $mybb->input["memberuid"], $mybb->input["cid"])
			|| ! is_string($mybb->input["message"]) || ! is_numeric($mybb->input["memberuid"]) || ! is_numeric($mybb->input["cid"])) {
			return;
		}
		MyProfileUtils::lang_load_myprofile();
		$memberuid = (int) $mybb->input["memberuid"];
		$memprofile = get_user($memberuid);
		
		$cid = (int) $mybb->input["cid"];
		$comment = $this->comment_retrieve_from_db($cid, $memprofile);
		$message = $mybb->input["message"];
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		if(empty($comment) || ! $this->can_edit_comment($comment)) {
			$result->error = true;
			$result->error_message = $lang->mp_comments_cannot_edit_comment;
			MyProfileUtils::output_json($result);
		}
		$isprivate = isset($mybb->input["isprivate"]) && in_array($mybb->input["isprivate"], array("0", "1")) && $settings["mpcommentsstatusenabled"] == "1" ? (int) $mybb->input["isprivate"] : 0;
		
		if(my_strlen(trim($message)) < $settings["mpcommentsminlength"] || my_strlen($message) > $settings["mpcommentsmaxlength"]) {
			$result->error = true;
			$result->error_message = $lang->sprintf($lang->mp_comments_comment_wrong_length, $settings["mpcommentsminlength"], $settings["mpcommentsmaxlength"]);
			MyProfileUtils::output_json($result);
		}
		
		$result = $this->update_comment($cid, $message, $memberuid, $isprivate);
		MyProfileUtils::output_json($result);
	}
	
	public function xmlhttp_comments_approve() {
		global $mybb, $db, $settings, $lang;
		/* post request? */
		if($mybb->request_method != "post") {
			return;
		}
		/* valid post key? */
		if(! isset($mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || ! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		if(! isset($mybb->input["cid"]) || ! is_numeric($mybb->input["cid"])) {
			return;
		}
		
		MyProfileUtils::lang_load_myprofile();
		
		$result = $this->approve_comment((int) $mybb->input["cid"]);
		MyProfileUtils::output_json($result);
	}
	
	public function comment_process($comment, $memprofile) {
		global $templates, $cache, $settings, $mybb, $lang;
		MyProfileUtils::lang_load_myprofile();
		
		$usergroups = $cache->read("usergroups");
		$editable = $this->can_edit_comment($comment);
		$comment["editable"] = $editable;
		$approvable = $this->can_approve_comment($comment);
		$comment["approvable"] = $approvable;
		$deletable = $this->can_delete_comment($comment);
		$comment["deletable"] = $deletable;
		/* replyable: well, it's replyable if I'm memprofile, and I'm trying to send a comment to the commentor (but I am not the commentor, otherwise it will be an infinite loop) */
		$replyable = $mybb->user["uid"] == $memprofile["uid"] && $comment["cuid"] != $comment["userid"] && $this->can_send_comments($memprofile, $comment);
		$comment["replyable"] = $replyable;
		
		/* now we add html content to the comment */
		list($avatar_src, $avatar_width_height) = array_values(format_avatar($comment["avatar"], $comment["avatardimensions"]));
		
		$date = my_date($settings["dateformat"], $comment["time"]);
		$time = my_date($settings["timeformat"], $comment["time"]);
		
		$username = format_name(htmlspecialchars_uni($comment["username"]), $comment["usergroup"], $comment["displaygroup"]);
		$profile_link = build_profile_link($username, $comment["userid"]);
		
		$message = $this->parse_comment($comment["message"]);
		
		if($editable) {
			eval("\$comments_edit = \"".$templates->get('myprofile_comments_comment_edit')."\";");
		}
		
		if($approvable) {
			$trow_class = "trow_shaded";
			eval("\$comments_approve = \"".$templates->get('myprofile_comments_comment_approve')."\";");
		}
		
		if($deletable) {
			eval("\$comments_delete = \"".$templates->get('myprofile_comments_comment_delete')."\";");
		}
		
		if($replyable) {
			$commentor_uid = $comment["cuid"];
			eval("\$comments_reply = \"".$templates->get('myprofile_comments_comment_reply')."\";");
		}
		/* if the user isn't a guest, and the user's usergroup isn't banned, can report */
		if($mybb->user["uid"] > 0 && $mybb->usergroup["usergroup"]["isbannedgroup"] != "1") {
			eval("\$comments_report = \"".$templates->get('myprofile_comments_comment_report')."\";");
		}
		
		if($comment["isprivate"] == "1") {
			$comment_private = $lang->mp_comments_comment_private;
		}
		
		if(isset($mybb->input["highlight"]) && $mybb->input["highlight"] == $comment["cid"]) {
			$trow_class = "trow_selected";
		}
		
		/* last eval() */
		eval("\$comment_content = \"".$templates->get('myprofile_comments_comment')."\";");
		
		//$comment["html"] = $comment_content;
		
		return $comment_content;
	}
	
	
	/**
	 * Can $user comment on $memprofile ? An empty $user means a guest
	 * Provide an empty variable as third parameter to receive the error message to why the $user can't send a comment to the $target
	 */
	public function can_send_comments($user, $target, &$error_message = "") {
		global $settings, $cache, $lang;
		MyProfileUtils::lang_load_myprofile();
		$usergroups = $cache->read("usergroups");
		/* if the user is a mod, always return true please */
		if($this->can_manage_comments($user)) {
			return true;
		}
		
		$usergroup = $usergroups[$user["usergroup"]];
		/* if user's usergroup can't make comments */
		if($usergroup["cansendcomments"] == "0") {
			$error_message = $lang->mp_comments_cannot_send_comments;
			return false;
		}
		/* if the target user is banned */
		if($usergroups[$target["usergroup"]]["isbannedgroup"] == "1" && $settings["mpcommentsclosedonbanned"] == "1") {
			$error_message = $lang->mp_comments_banned_user;
			return false;
		}
		/* if the user has chosen to close all comments */
		if($target["mpwhocancomment"] == "0" || $target["mpcommentsopen"] == "0") {
			$error_message = $lang->mp_comments_user_closed_comments;
			return false;
		}
		/* the target is on ignorelist of the user */
		if($settings["mpcommentsignoreenabled"] == "1" && in_array($user["uid"], explode(",", $target["ignorelist"]))) {
			$error_message = $lang->mp_comments_user_ignored_you;
			return false;
		}
		/* I'm making a comment on my profile */
		if($user["uid"] == $target["uid"]) {
			return true;
		}
		/* If the user is only accepting comments from friends */
		if($target["mpwhocancomment"] == "1" && ! in_array($user["uid"], explode(",", $target["buddylist"]))) {
			$error_message = $lang->mp_comments_not_friend_with_user;
			return false;
		}
		/* all well that ends well */
		return true;
	}
	
	function can_approve_comment($comment) {
		global $mybb;
		/* approvable: it's approvable if not already approved, and the user is seeing own profile, and the user is the comment's receiver, or it's a moderator */
		return $comment["approved"] == "0" ? $this->can_manage_comments() || $mybb->user["uid"] == $comment["userid"] : false;;
	}
	
	/* if $user provided, it will check if they can manage comments, otherwise it will look if the connected user can do that */
	public function can_manage_comments($user = null) {
		global $mybb, $cache;
		$usergroups = $cache->read("usergroups");
		return !empty($user) ? $usergroups[$user["usergroup"]]["canmanagecomments"] == "1" : $mybb->usergroup["canmanagecomments"] == "1";
	}
	
	public function can_delete_comment($comment) {
		global $mybb;
		/* deletable: it's deletable if we're a mod, or we're the author of the comment and we can delete own comments */
		return $this->can_manage_comments() || ($comment["cuid"] == $mybb->user["uid"] && $mybb->usergroup["candeleteowncomments"] == "1");
	}
	
	public function can_edit_comment($comment) {
		global $mybb, $settings;
		/* editable: it's editable if we are a moderator, or if we are the author and we can edit own comments */
		if($this->can_manage_comments()) {
			return true;
		}
		$editable = $comment["cuid"] == $mybb->user["uid"] && $mybb->usergroup["caneditowncomments"] == "1";
		/* did the allowed time to edit expire? */
		if($settings["mpcommentstimeedit"] > "0") {
			$time_limit = TIME_NOW - ($settings["mpcommentstimeedit"] * 60);
			$editable = $comment["time"] > $time_limit;
		}
		/* if the user has already approved the message, the user shouldn't be able to edit it anyway. we add $editable so it doesn't perform the other tests if it's not editable anyway. */
		if($editable && $memprofile["mpcommentsapprove"] == "1" && $comment["approved"] == "1") {
			$editable = false;
		}
		return $editable;
	}
	
	public function xmlhttp_comments_delete() {
		global $mybb, $db, $settings, $lang;
		/* post request? */
		if($mybb->request_method != "post") {
			return;
		}
		/* valid post key? */
		if(! isset($mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || ! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		MyProfileUtils::lang_load_myprofile();
		
		if(! isset($mybb->input["cid"]) || ! is_numeric($mybb->input["cid"])) {
			return;
		}
		$result = $this->delete_comment((int) $mybb->input["cid"]);
		MyProfileUtils::output_json($result);
	}
	
	public function xmlhttp_comments_delete_all() {
		global $mybb, $db, $settings, $lang;
		if($mybb->request_method != "post") {
			return;
		}
		if(! isset($mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || ! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		if(! $this->can_manage_comments()) {
			/* nice try! */
			return;
		}
		MyProfileUtils::lang_load_myprofile();
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		if(! isset($mybb->input["memberuid"]) || ! is_numeric($mybb->input["memberuid"])) {
			$result->error = true;
			$result->error_message = $lang->mp_profile_comments_no_user_selected;
			
			MyProfileUtils::output_json($result);
		}
		
		$memberuid = (int) $mybb->input["memberuid"];
		$query = $db->delete_query("myprofilecomments", "userid='{$memberuid}'");
		MyProfileUtils::output_json($result);
	}
	
	/*
	public function xmlhttp_comments_edit_get() {
		global $mybb, $db, $settings, $lang;
		if(! isset($mybb->input["action"]) || $mybb->input["action"] != "comments_edit_get") {
			return;
		}
		if(! isset($mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || ! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		MyProfileUtils::lang_load_myprofile();
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		if(! isset($mybb->input["cid"]) || ! is_numeric($mybb->input["cid"])) {
			$result->error = true;
			$result->error_message = $lang->mp_profile_comments_no_comment_selected;
			
			MyProfileUtils::output_json($result);
		}
		
		$cid = (int) $mybb->input["cid"];
		$query = $db->simple_select("myprofilecomments", "message", "cid='{$cid}'");
		
		if($db->num_rows($query) != 1) {
			$result->error = true;
			$result->error_message = $lang->mp_profile_comments_no_comment_selected;
			
			MyProfileUtils::output_json($result);
		}
		
		$message = $db->fetch_field($query, "message");
		
		$result->message = $message;
		
		MyProfileUtils::output_json($result);
	}
	
	public function xmlhttp_comments_edit_set() {
		global $mybb, $db, $settings, $lang;
		if(! isset($mybb->input["action"]) || $mybb->input["action"] != "comments_edit_set" || $mybb->request_method != "post") {
			return;
		}
		if(! isset($mybb->input["my_post_key"]) || ! is_string($mybb->input["my_post_key"]) || ! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		MyProfileUtils::lang_load_myprofile();
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		if(! isset($mybb->input["cid"], $mybb->input["message"]) || ! is_numeric($mybb->input["cid"]) || ! is_string($mybb->input["message"])) {
			$result->error = true;
			$result->error_message = $lang->mp_profile_comments_no_comment_selected;
			
			MyProfileUtils::output_json($result);
		}
		
		$cid = (int) $mybb->input["cid"];
		$message = $mybb->input["message"];
		
		$query = $db->simple_select("myprofilecomments", "message", "cid='{$cid}'");
		
		if($db->num_rows($query) != 1) {
			$result->error = true;
			$result->error_message = $lang->mp_profile_comments_no_comment_selected;
			
			MyProfileUtils::output_json($result);
		}
		
		$db->update_query("myprofilecomments", array("message" => $db->escape_string($message)), "cid='{$cid}'");
		$query = $db->simple_select("myprofilecomments", "message", "cid='{$cid}'");
		
		$message = $db->fetch_field($query, "message");
		
		$result->message = $this->parse_comment($message);
		
		MyProfileUtils::output_json($result);
	}*/
	
	/* insert a comment to $uid, that is private or not */
	public function insert_comment($message, $uid, $isprivate = 0) {
		global $mybb, $settings, $db, $lang;
		MyProfileUtils::lang_load_myprofile();
		$user = get_user($uid);
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		if(empty($user)) {
			$result->error = true;
			$result->error_message = $lang->mp_profile_comments_no_user_selected;
			return $result;
		}
		
		/* should the user approve the message first? */
		$approved = $user["mpcommentsapprove"] == "0";
		
		$insert_array = array(
			"userid" => (int) $user["uid"],
			"cuid" => (int) $mybb->user["uid"],
			"message" => $db->escape_string($message),
			"approved" => $approved,
			"isprivate" => $isprivate,
			"time" => TIME_NOW
		);
		$cid = $db->insert_query("myprofilecomments", $insert_array);
		$this->alert_comment($user, $mybb->user, $cid);
		
		// all's well that ends well
		return $result;
	}
	
	public function update_comment($cid, $message, $uid, $isprivate = 0) {
		global $mybb, $settings, $db, $lang;
		MyProfileUtils::lang_load_myprofile();
		$user = get_user($uid);
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		if(empty($user)) {
			$result->error = true;
			$result->error_message = $lang->mp_profile_comments_no_user_selected;
			return $result;
		}
		
		/* should the user approve the message first? */
		$approved = $user["mpcommentsapprove"] == "0";
		
		$update_array = array(
			"message" => $db->escape_string($message),
			"approved" => $approved,
			"isprivate" => $isprivate
		);
		$cid = $db->update_query("myprofilecomments", $update_array, "cid='{$cid}'", "1");
		
		// all's well that ends well
		return $result;
	}
	
	public function alert_comment($user, $commentor, $cid) {
		global $db, $settings, $mybbstuff_myalerts_alert_manager;
		/* if the admin choosed alertbar, or "MyAlerts or Alert bar" but MyAlerts don't exist, notify the user */
		if($settings["mpcommentsnotification"] == "alertbar" || ($settings["mpcommentsnotification"] == "myalertsoralertbar" && ! MyProfileUtils::myalerts_exists())) {
			$update_array = array(
				"mpnewcomments" => $user["mpnewcomments"] + 1
			);
			$db->update_query("users", $update_array, "uid='{$user['uid']}'", "1");
			$user["mpnewcomments"]++;
		}
		/* if the admin choosed myalerts and it exists */
		elseif($settings["mpcommentsnotification"] == "myalerts" && MyProfileUtils::myalerts_exists()) {
			$alert = MybbStuff_MyAlerts_Entity_Alert::make($user["uid"], MyProfileCommentsMyAlertsFormatter::alert_type_code(), 0, array());
			$mybbstuff_myalerts_alert_manager->addAlert($alert);
		}
	}
	
	public function delete_comment($cid) {
		global $db, $lang;
		MyProfileUtils::lang_load_myprofile();
		$query = $db->simple_select("myprofilecomments", "*", "cid='{$cid}'");
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		if($db->num_rows($query) != 1) {
			$result->error = true;
			$result->error_message = $lang->mp_comments_comment_not_found;
			return $result;
		}
		
		$comment = $db->fetch_array($query);
		
		if(! $this->can_delete_comment($comment)) {
			$result->error = true;
			$result->error_message = $lang->mp_comments_cannot_delete_comment;
			return $result;
		}
		
		$query = $db->delete_query("myprofilecomments", "cid='{$cid}'");
		return $result;
	}
	
	public function approve_comment($cid) {
		global $db, $lang;
		MyProfileUtils::lang_load_myprofile();
		$query = $db->simple_select("myprofilecomments", "*", "cid='{$cid}'");
		
		$result = new stdClass();
		$result->error = false;
		$result->error_message = "";
		
		if($db->num_rows($query) != 1) {
			$result->error = true;
			$result->error_message = $lang->mp_comments_comment_not_found;
			return $result;
		}
		
		$comment = $db->fetch_array($query);
		if(! $this->can_approve_comment($comment)) {
			$result->error = true;
			$result->error_message = $lang->mp_comments_cannot_approve_comment;
			return $result;
		}
		
		$update_array = array(
			"approved" => "1"
		);
		$db->update_query("myprofilecomments", $update_array, "cid='{$cid}'", "1");
		return $result;
	}
	
	public function misc_start() {
		global $mybb, $settings;
		if(! isset($mybb->input["action"]) || ! is_string($mybb->input["action"])) {
			return;
		}
		if($mybb->input["action"] == "comments-edit") {
			$this->misc_comments_edit();
			return;
		}
		if($mybb->input["action"] == "comments-do-edit") {
			$this->misc_comments_do_edit();
			return;
		}
		/* is ajax activated? */
		if($settings["mpcommentsajaxenabled"] != "0") {
			return;
		}
		
		switch($mybb->input["action"]) {
			case "comments-add" :
				$this->misc_comments_add();
			break;
			case "comments-delete" :
				$this->misc_comments_delete();
			break;
			case "comments-delete-all" :
				$this->misc_comments_delete_all();
			break;
			case "comments-approve" :
				$this->misc_comments_approve();
			break;
			default :
				return; /* again, no need to break after a return but I'm a paranoid */
			break;
		}
	}
	
	public function misc_comments_add() {
		global $mybb, $lang, $settings;
		/* post request ? */
		if($mybb->request_method != "post") {
			return;
		}
		/* valid post key ? */
		verify_post_check($mybb->input["my_post_key"]);

		/* have we provided all the necessary fields ? With the appropriate types ? */
		if(! isset($mybb->input["message"], $mybb->input["memberuid"])
			|| ! is_string($mybb->input["message"]) || ! is_numeric($mybb->input["memberuid"])) {
			return;
		}
		MyProfileUtils::lang_load_myprofile();
		$memberuid = (int) $mybb->input["memberuid"];
		$memprofile = get_user($memberuid);
		
		$error_message = "";
		
		/* can the user send comments to the profile's owner? */
		if(! $this->can_send_comments($mybb->user, $memprofile, $error_message)) {
			$this->redirect($memberuid, $error_message);
		}
		
		$message = $mybb->input["message"];
		/* is the comment private? is the sneaky user trying to set it to private while the private status is globally disabled? */
		$isprivate = isset($mybb->input["isprivate"]) && in_array($mybb->input["isprivate"], array("0", "1")) && $settings["mpcommentsstatusenabled"] == "1" ? (int) $mybb->input["isprivate"] : 0;
		/* are we respecting the comment lengths? */
		if(my_strlen(trim($message)) < $settings["mpcommentsminlength"] || my_strlen($message) > $settings["mpcommentsmaxlength"]) {
			$error_message = $lang->sprintf($lang->mp_comments_comment_wrong_length, $settings["mpcommentsminlength"], $settings["mpcommentsmaxlength"]);
			$this->redirect($memberuid, $error_message);
		}
		
		/* go get em */
		$result = $this->insert_comment($message, $memberuid, $isprivate);
		if($result->error) {
			$this->redirect($memberuid, $result->error_message);
		}
		else {
			$this->redirect($memberuid, $lang->mp_comments_comment_added_successfully);
		}
	}
	
	public function misc_comments_delete() {
		global $mybb, $db, $settings, $lang;
		/* post request? */
		if($mybb->request_method != "post") {
			return;
		}
		/* valid post key? */
		verify_post_check($mybb->input["my_post_key"]);
		if(! isset($mybb->input["cid"], $mybb->input["memberuid"]) || ! is_numeric($mybb->input["cid"]) || ! is_numeric($mybb->input["memberuid"])) {
			return;
		}
		MyProfileUtils::lang_load_myprofile();
		$result = $this->delete_comment((int) $mybb->input["cid"]);
		if($result->error) {
			$this->redirect($mybb->input["memberuid"], $result->error_message);
		}
		else {
			$this->redirect($mybb->input["memberuid"], $lang->mp_comments_comment_deleted_successfully);
		}
	}
	
	public function misc_comments_delete_all() {
		global $mybb, $lang;
		if($mybb->request_method != "get") {
			return;
		}
		verify_post_check($mybb->input["my_post_key"]);
		if(! isset($mybb->input["memberuid"]) || ! is_numeric($mybb->input["memberuid"])) {
			return;
		}
		if(! $this->can_manage_comments()) {
			error_no_permission();
		}
		else {
			MyProfileUtils::lang_load_myprofile();
			$this->redirect((int) $mybb->input["memberuid"], $lang->mp_comments_comments_deleted_successfully);
		}
	}
	
	public function misc_comments_approve() {
		global $mybb, $db, $settings, $lang;
		/* post request? */
		if($mybb->request_method != "post") {
			return;
		}
		/* valid post key? */
		verify_post_check($mybb->input["my_post_key"]);
		if(! isset($mybb->input["cid"], $mybb->input["memberuid"]) || ! is_numeric($mybb->input["cid"]) || ! is_numeric($mybb->input["memberuid"])) {
			return;
		}
		
		MyProfileUtils::lang_load_myprofile();
		
		$memberuid = (int) $mybb->input["memberuid"];
		
		$result = $this->approve_comment((int) $mybb->input["cid"]);
		
		if($result->error) {
			$this->redirect($memberuid, $result->error_message);
		}
		else {
			$this->redirect($memberuid, $lang->mp_comments_comment_approved_successfully);
		}
	}
	
	public function misc_comments_edit() {
		global $mybb, $settings, $lang, $theme, $templates, $db;
		if(! isset($mybb->input["cid"], $mybb->input["my_post_key"], $mybb->input["memberuid"]) || ! is_numeric($mybb->input["cid"]) || ! is_numeric($mybb->input["memberuid"]) || ! is_string($mybb->input["my_post_key"])) {
			return ;
		}
		
		$memprofile = get_user($mybb->input["memberuid"]);
		
		$comment = $this->comment_retrieve_from_db((int) $mybb->input["cid"], $memprofile);
		
		MyProfileUtils::lang_load_myprofile();
		if(empty($comment) || ! $this->can_edit_comment($comment)) {
			MyProfileUtils::output_error($lang->mp_comments_cannot_edit_comment, 400);
		}
		
		$original_text = $comment["message"];
		if($settings["mpcommentsstatusenabled"] == "1") {
			if($comment["isprivate"] == "0") {
				$comment_public_selected = 'selected="selected"';
			}
			else {
				$comment_private_selected = 'selected="selected"';
			}
			$status_select_class = "select-comments-edit";
			eval("\$status .= \"".$templates->get('myprofile_comments_form_status')."\";");
		}
		$cid = $comment["cid"];
		eval("\$comments_edit .= \"".$templates->get('myprofile_comments_edit', 1, 0)."\";");
		echo $comments_edit;
	}
	
	public function misc_comments_do_edit() {
		global $mybb, $lang;
		if(! isset($mybb->input["my_post_key"], $mybb->input["page"], $mybb->input["memberuid"]) || ! is_string($mybb->input["my_post_key"]) ||
			! is_numeric($mybb->input["page"]) || ! is_numeric($mybb->input["memberuid"])) {
			return;
		}
		if(! verify_post_check($mybb->input["my_post_key"], true)) {
			return;
		}
		MyProfileUtils::lang_load_myprofile();
		$this->redirect((int) $mybb->input["memberuid"], $lang->mp_comments_comment_edited_successfully, "&page={$mybb->input['page']}");
	}
	
	public function redirect($uid, $message, $supplement = "") {
		redirect(get_profile_link((int) $uid) . $supplement, $message);
	}
	
	public function member_profile_end() {
		global $templates, $theme, $memprofile, $settings, $db, $mybb, $lang, $myprofile_comments, $theme, $myprofile_comments_stats;
		
		if($settings["mpcommentsenabled"] != "1") {
			return;
		}
		
		MyProfileUtils::lang_load_myprofile();
		$page = isset($mybb->input["page"]) && is_numeric($mybb->input["page"]) && $mybb->input["page"] >= 1 ? (int) $mybb->input["page"] : 1;
		
		$comments_memberuid = $memprofile["uid"];
		$comments_ajax = $settings["mpcommentsajaxenabled"] == "1" ? 1 : 0;
		$comments_minlength = $settings["mpcommentsminlength"];
		$comments_maxlength = $settings["mpcommentsmaxlength"];
		$comments_page = $page;
		$comments_sceditor = 0;
		
		if($this->can_send_comments($mybb->user, $memprofile)) {
			$show_smilies = $settings["mpcommentsallowsmilies"] == "1";
			if($settings["mpcommentsshowwysiwyg"] == "1") {
				$codebuttons = build_mycode_inserter("message", $show_smilies);
				/* small hack to shrink the sceditor */
				$codebuttons = str_replace(array('|left,center,right,justify', '|bulletlist,orderedlist'), '', $codebuttons);
				$comments_sceditor = 1;
			}
			else {
				$comments_sceditor = 0;
			}
			if($this->can_manage_comments()) {
				eval("\$modoptions .= \"".$templates->get('myprofile_comments_form_modoptions')."\";");
			}
			if($settings["mpcommentsstatusenabled"] == "1") {
				$comment_public_selected = 'selected="selected"';
				$status_select_class = "select-comments-add";
				eval("\$status .= \"".$templates->get('myprofile_comments_form_status')."\";");
			}
			eval("\$comments_form .= \"".$templates->get('myprofile_comments_form')."\";");
		}
		
		if($settings["mpcommentsajaxenabled"] == "0") {
			/* ajax disabled, do the dirty work */
			$comments_content = "";
			$comments = $this->comments_retrieve_from_db($page, $memprofile);
			if(is_array($comments) && count($comments) > 0) {
				foreach($comments as $comment) {
					$comments_content .= $comment;
				}
			}
			else {
				eval("\$comments_content = \"".$templates->get('myprofile_comments_no_comment')."\";");
			}
			$comments_total = $this->comments_count($memprofile);
			$comments_pagination = multipage($comments_total, $settings['mpcommentsperpage'], $page, get_profile_link($memprofile["uid"]) . "&page={page}");
			if($comments_pagination == null) {
				$comments_pagination_style = 'style="display: none;"';
			}
		}
		
		eval("\$comment_form_script .= \"".$templates->get('myprofile_comments_form_script')."\";");
		
		/* darn that <br /> bug! */
		if(!empty($GLOBALS['modoptions']) || !empty($comments_form)) {
			$comments_separator = "<br />";
		}
		
		eval("\$comments_table .= \"".$templates->get('myprofile_comments_table')."\";");
		
		eval("\$myprofile_comments .= \"".$templates->get('myprofile_comments_content')."\";");
		
		if($settings["mpcommentsshowstats"] == "1") {
			$result = $this->user_statistics($memprofile["uid"]);
			$stats_sent = $result["sent"];
			$stats_received = $result["received"];
			$stats_total = $result["total"];
			eval("\$myprofile_comments_stats .= \"".$templates->get('myprofile_comments_stats')."\";");
		}
	}
	
	public function admin_formcontainer_end() {
		global $run_module, $form_container, $lang, $form, $mybb;
		
		if($run_module == "user" && !empty($form_container->_title) && !empty($lang->users_permissions) && $form_container->_title == $lang->users_permissions) {
			MyProfileUtils::lang_load_config_myprofile();
			
			$mp_options = array();
			$mp_options[] = $form->generate_check_box("canmanagecomments", 1, $lang->mp_options_can_manage_comments, array('checked' => $mybb->input["canmanagecomments"]));
			$mp_options[] = $form->generate_check_box("cansendcomments", 1, $lang->mp_options_can_send_comments, array('checked' => $mybb->input["cansendcomments"]));
			$mp_options[] = $form->generate_check_box("caneditowncomments", 1, $lang->mp_options_can_edit_own_comments, array('checked' => $mybb->input["caneditowncomments"]));
			$mp_options[] = $form->generate_check_box("candeleteowncomments", 1, $lang->mp_options_can_delete_own_comments, array('checked' => $mybb->input["candeleteowncomments"]));
			
			$form_container->output_row($lang->mp_myprofile, '', '<div class="group_settings_bit">'.implode('</div><div class="group_settings_bit">', $mp_options).'</div>');
		}
	}
	
	public function admin_user_groups_edit_commit() {
		global $updated_group, $mybb;

		$updated_group['canmanagecomments'] = $mybb->input['canmanagecomments'];
		$updated_group['cansendcomments'] = $mybb->input['cansendcomments'];
		$updated_group['caneditowncomments'] = $mybb->input['caneditowncomments'];
		$updated_group['candeleteowncomments'] = $mybb->input['candeleteowncomments'];
	}
	
	public function report_start() {
		global $lang;
		MyProfileUtils::lang_load_myprofile();
	}
	
	public function report_end() {
		global $templates, $error, $report, $report_reasons, $mybb, $lang, $report_title;
		if(empty($error) && empty($report)) {
			eval("\$report_reasons = \"".$templates->get("myprofile_comments_report_reasons")."\";");
		}
	}
	
	public function report_type() {
		global $mybb, $db, $lang, $report_type, $report_type_db, $verified, $id, $id2, $id3, $error;
		if($report_type == 'comment') {
			if(! isset($mybb->input["pid"]) || ! is_numeric($mybb->input["pid"])) {
				$error = $lang->error_invalid_report;
			}
			else {
				$cid = (int) $mybb->input["pid"];
				$query = $db->simple_select("myprofilecomments", "*", "cid = '".$cid."'");
				if(! $db->num_rows($query)) {
					$error = $lang->error_invalid_report;
				}
				else {
					$verified = true;
					$comment = $db->fetch_array($query);
					$id = $comment["cid"];
					$id2 = $comment["userid"]; // user who received the comment
					$id3 = $comment["cuid"]; // user who made the comment
					$report_type_db = "type = 'comment'";
				}
			}
		}
	}
	
	public function modcp_reports_report() {
		global $report, $report_data, $lang;
		if($report["type"] == "comment") {
			MyProfileUtils::lang_load_myprofile();
			$from_user = get_user($report['id3']);
			$to_user = get_user($report['id2']);
			$from_profile_link = build_profile_link(htmlspecialchars_uni($from_user["username"]), $from_user["uid"]);
			$to_profile_link = build_profile_link(htmlspecialchars_uni($to_user["username"]), $to_user["uid"]);
			$comment_link = $this->build_comment_link($report["id"]);
			$report_data["content"] = $lang->sprintf($lang->mp_report_from, $comment_link, $from_profile_link);
			$report_data["content"] .= $lang->sprintf($lang->mp_report_to, $to_profile_link);
		}
	}
	
	public function usercp_do_options_end() {
		global $mybb, $db;
		
		// basically, if the my profile params are submitted, and they are equivalent to the old ones, return.
		if(isset($mybb->input["mpcommentsopen"]) && $mybb->user["mpcommentsopen"] == $mybb->input["mpcommentsopen"]
			&& isset($mybb->input["mpwhocancomment"]) && $mybb->user["mpwhocancomment"] == $mybb->input["mpwhocancomment"]
			&& isset($mybb->input["mpcommentnotification"]) && $mybb->user["mpcommentnotification"] == $mybb->input["mpcommentnotification"]
			&& isset($mybb->input["mpcommentsapprove"]) && $mybb->user["mpcommentsapprove"] == $mybb->input["mpcommentsapprove"]) {
				return;
		}
		
		$update_array = array();
		// in_array is also a great way to escape strings without calling the database :D
		if(isset($mybb->input["mpcommentsopen"]) && in_array($mybb->input["mpcommentsopen"], array("0", "1"))) {
			$update_array["mpcommentsopen"] = $mybb->input["mpcommentsopen"];
		}
		elseif(! isset($mybb->input["mpcommentsopen"])) {
			$update_array["mpcommentsopen"] = "0";
		}
		
		if(in_array($mybb->input["mpwhocancomment"], array("0", "1", "2"))) {
			$update_array["mpwhocancomment"] = $mybb->input["mpwhocancomment"];
		}
		
		if(isset($mybb->input["mpcommentnotification"]) && in_array($mybb->input["mpcommentnotification"], array("0", "1"))) {
			$update_array["mpcommentnotification"] = $mybb->input["mpcommentnotification"];
		}
		elseif(! isset($mybb->input["mpcommentnotification"])) {
			$update_array["mpcommentnotification"] = "0";
		}
		
		if(isset($mybb->input["mpcommentsapprove"]) && in_array($mybb->input["mpcommentsapprove"], array("0", "1"))) {
			$update_array["mpcommentsapprove"] = $mybb->input["mpcommentsapprove"];
		}
		elseif(! isset($mybb->input["mpcommentsapprove"])) {
			$update_array["mpcommentsapprove"] = "0";
		}
		
		if(count($update_array) > 0) {
			$db->update_query("users", $update_array, "uid='{$mybb->user['uid']}'", "1");
		}
	}
	
	public function usercp_options_start() {
		global $templates, $myprofile_comments_usercp, $lang, $mybb;
		MyProfileUtils::lang_load_myprofile();
		$mpcommentsopen = $mybb->user["mpcommentsopen"] == "1" ? "checked=\"checked\"" : "";
		$nobodycanleavecomments = $mybb->user["mpwhocancomment"] == "0" ? "selected=\"selected\"" : "";
		$friendlistcanleavecomment = $mybb->user["mpwhocancomment"] == "1" ? "selected=\"selected\"" : "";
		$anyonecanleavecomments = $mybb->user["mpwhocancomment"] == "2" ? "selected=\"selected\"" : "";
		$mpcommentnotification = $mybb->user["mpcommentnotification"] == "1" ? "checked=\"checked\"" : "";
		$mpcommentsapprove = $mybb->user["mpcommentsapprove"] == "1" ? "checked=\"checked\"" : "";
		eval("\$myprofile_comments_usercp = \"".$templates->get("myprofile_comments_usercp")."\";");
	}
	
	public function modcp_nav() {
		global $templates, $nav_myprofilecomments, $lang, $mybb;
		if($mybb->usergroup["canmanagecomments"] == "1") {
			MyProfileUtils::lang_load_myprofile();
			eval("\$nav_myprofilecomments = \"".$templates->get("myprofile_modcp_nav_comments")."\";");
		}
	}
	
	public function modcp_start() {
		global $mybb, $lang, $theme, $settings, $templates, $headerinclude, $header, $modcp_nav;
		if(isset($mybb->input["action"]) && is_string($mybb->input["action"])) {
			$action = $mybb->input["action"];
			if($action == "myprofilecomments") {
				if($mybb->usergroup["canmanagecomments"] == "0") {
					error_no_permission();
				}
				else {
					add_breadcrumb($lang->mcp_nav_users, "modcp.php?action=myprofile");
					eval("\$myprofile = \"".$templates->get("myprofile_comments_modcp_start")."\";");
					output_page($myprofile);
				}
			}
		}
	}
	
	/**
	 * This will try to guess the exact page on which the comment identified by $cid exists.
	 * @param int $cid The comment ID
	 * @param boolean $href whether or not to surround the link with an anchor
	 * @param string $link_name if $href is enabled, enter the link name that will be displayed between the anchor opening and closing tags
	 * @param string $other_params if you wish to include some other parameters such as target="_blank", do that here, include a space at the beginning
	 * @return string the comment link, or an empty string if no comment has been found
	 */
	public function build_comment_link($cid, $href=false, $link_name="", $other_params="") {
		global $db, $settings, $mybb;
		//SELECT a.*, (select count(*) from `mybb_myprofilecomments` b where a.cid >= b.cid) as cnt FROM `mybb_myprofilecomments` a WHERE a.cid='2'
		$query = $db->query("SELECT a.*, (SELECT COUNT(*) FROM " . TABLE_PREFIX . "myprofilecomments b WHERE a.cid = b.cid) AS rownum FROM " . TABLE_PREFIX . "myprofilecomments a WHERE a.cid='{$db->escape_string($cid)}'");

		if($db->num_rows($query) != 1) {
			return "";
		}
		$comment = $db->fetch_array($query);
		$user = get_user($comment["userid"]);
		$complement = "#comments/" . ceil($comment["rownum"] / $settings["mpcommentsperpage"]) . "/highlight/" . $cid;
		$profile_link = "{$mybb->settings['bburl']}/" . get_profile_link($user["uid"]) . $complement;
		if($href) {
			$profile_link = "<a href=\"{$profile_link}\"{$other_params}>{$link_name}</a>";
		}
		return $profile_link;
	}
	
	public function parse_comment($message) {
		global $mybb, $parser, $settings;
		
		if(!isset($parser)) {
			require_once MYBB_ROOT . "inc/class_parser.php";
			$parser = new postParser;
		}
		
		$options = array(
			"allow_html" => (int) $settings["mpcommentsallowhtml"],
			"allow_mycode" => (int) $settings["mpcommentsallowmycode"],
			"allow_smilies" => (int) $settings["mpcommentsallowsmilies"],
			"allow_imgcode" => (int) $settings["mpcommentsallowimg"],
			"allow_videocode" => (int) $settings["mpcommentsallowvideo"],
			"filter_badwords" => (int) $settings["mpcommentsfilterbadwords"]
		);
		
		return $parser->parse_message($message, $options);
	}
	
	public function user_statistics($uid) {
		global $db;
		$result = array();
		$query = $db->simple_select("myprofilecomments", "COUNT(*) as `sent`", "cuid='{$uid}'", array("limit" => 1));
		$result["sent"] = $db->fetch_field($query, "sent");
		$query = $db->simple_select("myprofilecomments", "COUNT(*) as `received`", "userid='{$uid}'", array("limit" => 1));
		$result["received"] = $db->fetch_field($query, "received");
		$result["total"] = $result["sent"] + $result["received"];
		return $result;
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
