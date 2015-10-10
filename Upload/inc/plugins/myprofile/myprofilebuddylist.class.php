<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Mohamed Benjelloun
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
if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/* load template */
$plugins->add_hook("global_start", array(MyProfileBuddyList::get_instance(), "global_start"));
$plugins->add_hook("member_profile_end", array(MyProfileBuddyList::get_instance(), "member_profile_end"));

/* version 0.5 */
$plugins->add_hook("xmlhttp", array(MyProfileBuddyList::get_instance(), "xmlhttp"));

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
        $templates["myprofile_buddylist"] = '<div class="buddylist-content"><br /><table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
<tr>
<td width="100%" valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td colspan="4" class="thead"><strong>{$lang->mp_profile_buddylist} ({$lang->mp_profile_comments_total} {$count})</strong></td>
</tr>
<tr class="buddylist-pagination">
<td colspan="2" {$buddylist_pagination_style}>{$buddylist_pagination}</td>
</tr>
{$buddylist_count}
{$buddylist_content}
</table>
</td>
</tr>
</table></div>';

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
        if (defined('THIS_SCRIPT') && THIS_SCRIPT == "member.php") {
            $templatelist .= ",myprofile_buddylist,myprofile_buddylist_buddy_count,myprofile_buddylist_buddy,myprofile_buddylist_row,myprofile_buddylist_spacer";
        }
    }

    public function xmlhttp() {
        global $mybb, $settings;
        if ($settings["mpbuddylistenabled"] != "1") {
            return;
        }
        if (isset($mybb->input["action"]) && is_string($mybb->input["action"])) {
            switch ($mybb->input["action"]) {
                case "buddylist-load-page" :
                    $this->xmlhttp_buddylist_page();
                    break;
                default :
                    return;
                    break;
            }
        }
    }

    public function xmlhttp_buddylist_page() {
        global $mybb;

        $object = new stdClass();
        $object->error = false;
        $object->error_message = "";

        if (!isset($mybb->input["my_post_key"], $mybb->input["memberuid"]) || !is_string($mybb->input["my_post_key"]) || !verify_post_check($mybb->input["my_post_key"], true) || !is_numeric($mybb->input["memberuid"])) {
            return;
        }


        $page = isset($mybb->input["page"]) && is_numeric($mybb->input["page"]) && $mybb->input["page"] >= 1 ? (int) $mybb->input["page"] : 1;
        $memberuid = (int) $mybb->input["memberuid"];
        $memprofile = get_user($memberuid);

        if (empty($memprofile)) {
            return;
        }

        list($object->html, $object->count, $object->shown) = array_values($this->retrieve_buddylist_from_db($page, $memprofile));
        MyProfileUtils::output_json($object);
    }

    public function retrieve_buddylist_from_db($page, $memprofile) {
        global $db, $settings;
        $page = (int) $page;
        $buddylist = array();
        $count = count(array_filter(explode(",", $memprofile["buddylist"])));
        $limit = is_numeric($settings["mpbuddylistrecord"]) ? (int) $settings["mpbuddylistrecord"] : 4;
        $membuddylistarray = array_slice(explode(",", $memprofile["buddylist"]), ($page - 1) * $limit, $limit);
        $membuddylist = implode(",", $membuddylistarray);
        if (my_strlen(trim($membuddylist)) != 0) {
            $query = $db->simple_select("users", "*", "uid IN ({$membuddylist})", array("limit" => $limit));

            while ($buddy = $db->fetch_array($query)) {
                $buddylist[] = $buddy;
            }
            /* saving up a query */
        }

        return $this->buddylist_process($buddylist, $count, $memprofile, $limit, $page);
    }

    /* wow, this really needs a v2 */

    public function buddylist_process($buddylist, $count, $memprofile, $limit, $page) {
        global $lang, $templates, $settings, $mybb, $theme;

        MyProfileUtils::lang_load_myprofile();
        if (count($buddylist) == 0) {
            /* show them we've got no friends :( */
            $count_friends_text = $lang->sprintf($lang->mp_buddylist_no_friend, $memprofile["username"]);
            $count_colspan = 1;
        } else {
            $count_friends_text = $lang->sprintf($lang->mp_buddylist_friends, $memprofile["username"], $count, count($buddylist));
            $count_colspan = 4;
            $buddylist_content = "";
            for ($col = 0; $col < count($buddylist); $col += 4) {
                $row_content = "";
                for ($row = 0; $row < 4; $row++) {
                    if (isset($buddylist[$col + $row])) {
                        $buddy = $buddylist[$col + $row];
                        $td_class = alt_trow();
                        $profile_link = get_profile_link($buddy["uid"]);
                        list($avatar_src, $avatar_width_height) = array_values(format_avatar($buddy["avatar"], $buddy["avatardimensions"], $settings["mpbuddylistavatarmaxdimensions"]));
                        $username = format_name(htmlspecialchars_uni($buddy["username"]), $buddy["usergroup"], $buddy["displaygroup"]);
                        eval("\$row_content .= \"" . $templates->get('myprofile_buddylist_buddy') . "\";");
                    } else {
                        $td_class = alt_trow();
                        $td_colspan = 4 - $row;
                        $td_width = $td_colspan * 20;
                        eval("\$row_content .= \"" . $templates->get('myprofile_buddylist_spacer') . "\";");
                        break;
                    }
                }
                eval("\$buddylist_content .= \"" . $templates->get('myprofile_buddylist_row') . "\";");
            }
        }

        $buddylist_pagination = multipage($count, $limit, $page, "javascript:MyProfile.buddylistLoadPage({page});");
        if ($buddylist_pagination == null) {
            $buddylist_pagination_style = 'style="display: none;"';
        }
        eval("\$buddylist_count .= \"" . $templates->get('myprofile_buddylist_buddy_count') . "\";");
        eval("\$myprofile_buddylist .= \"" . $templates->get('myprofile_buddylist', 1, 0) . "\";");
        return array("html" => $myprofile_buddylist, "count" => $count, "shown" => count($buddylist));
    }

    public function member_profile_end() {
        global $memprofile, $myprofile_buddylist, $settings;
        if ($settings["mpbuddylistenabled"] != "1") {
            return;
        }
        list($myprofile_buddylist,, ) = array_values($this->retrieve_buddylist_from_db(1, $memprofile));
    }

    private function __construct() {
        
    }

    private function __clone() {
        
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
