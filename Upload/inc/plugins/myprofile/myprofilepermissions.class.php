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

/* cache template */
if (defined('THIS_SCRIPT') && THIS_SCRIPT == "usercp.php") {
    global $templatelist;
    $templatelist .= ",myprofile_permissions";
}

/* load template */
$plugins->add_hook("usercp_profile_end", array(MyProfilePermissions::get_instance(), "usercp_profile_end"));
$plugins->add_hook("datahandler_user_update", array(MyProfilePermissions::get_instance(), "datahandler_user_update"));
$plugins->add_hook("member_profile_start", array(MyProfilePermissions::get_instance(), "member_profile_start"));

class MyProfilePermissions {

    private static $instance = null;

    public function install() {
        global $db, $lang;
        MyProfileUtils::lang_load_config_myprofile();

        if (!$db->field_exists("myprofilepermissions", "users")) {
            $definition = $db->type == 'pgsql' ? 'smallint' : 'tinyint(1)';
            $db->add_column("users", "myprofilepermissions", "{$definition} NOT NULL default '0'");
        }

        $gid = MyProfileUtils::insert_settinggroups(array(
                    "name" => "myprofilepermissions",
                    "title" => $lang->mp_myprofile_permissions,
                    "description" => $lang->mp_myprofile_permissions_desc,
                    "isdefault" => 0
        ));

        MyProfileUtils::insert_settings(array(
            array(
                "name" => "mppermissionsenabled",
                "title" => $lang->mp_myprofile_permissions_enabled,
                "description" => $lang->mp_myprofile_permissions_enabled_desc,
                "optionscode" => "yesno",
                "value" => 1,
                "gid" => $gid
            ),
            array(
                "name" => "mppermissionsgroups",
                "title" => $lang->mp_myprofile_permissions_groups,
                "description" => $lang->mp_myprofile_permissions_groups_desc,
                "optionscode" => "groupselect",
                "value" => -1,
                "gid" => $gid
            )
        ));
    }

    public function is_installed() {
        global $db;
        return $db->field_exists("myprofilepermissions", "users");
    }

    public function uninstall() {
        global $db;
        if ($db->field_exists("myprofilepermissions", "users")) {
            $db->drop_column("users", "myprofilepermissions");
        }

        MyProfileUtils::delete_settings(array("mppermissionsenabled", "mppermissionsgroups"));

        MyProfileUtils::delete_settinggroups(array("myprofilepermissions"));
    }

    public function activate() {
        require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

        MyProfileUtils::insert_templates(array(
            'myprofile_permissions' => '<tr>
<td colspan="3">
<span class="smalltext">{$lang->mp_myprofile_permissions_ucp}</span>
</td>
</tr>
<tr>
<td colspan="3">
<select name="myprofile_permissions">
<option value="everybody"{$selected[\'everybody\']}>{$lang->myprofile_permissions_everybody}</option>
<option value="nobody"{$selected[\'nobody\']}>{$lang->myprofile_permissions_nobody}</option>
<option value="buddies"{$selected[\'buddies\']}>{$lang->myprofile_permissions_buddies}</option>
</select>
</td>
</tr>'
        ));

        find_replace_templatesets("usercp_profile", "#" . preg_quote('{$website}') . "#i", '{$myprofile_permissions}{$website}');
    }

    public function deactivate() {
        require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

        MyProfileUtils::delete_templates(array("myprofile_permissions"));

        find_replace_templatesets("usercp_profile", "#" . preg_quote('{$myprofile_permissions}') . "#i", '', 0);
    }

    public function usercp_profile_end() {
        global $myprofile_permissions, $mybb;
        $myprofile_permissions = '';

        if (!$mybb->settings['mppermissionsenabled']) {
            return;
        }

        if ($mybb->settings['mppermissionsgroups'] != -1 && !is_member($mybb->settings['mppermissionsgroups'])) {
            return;
        }

        global $lang, $templates, $user;
        MyProfileUtils::lang_load_myprofile();

        switch ($mybb->get_input('myprofile_permissions')) {
            case 'buddies':
                $user['myprofilepermissions'] = 2;
                break;
            case 'nobody':
                $user['myprofilepermissions'] = 1;
                break;
            case 'everybody':
                $user['myprofilepermissions'] = 0;
                break;
        }

        $selected = array('everybody' => '', 'nobody' => '', 'buddies' => '');
        switch ($user['myprofilepermissions']) {
            case 2:
                $selected['buddies'] = ' selected="selected"';
                break;
            case 1:
                $selected['nobody'] = ' selected="selected"';
                break;
            default:
                $selected['everybody'] = ' selected="selected"';
                break;
        }

        eval("\$myprofile_permissions .= \"" . $templates->get('myprofile_permissions') . "\";");
    }

    public function datahandler_user_update(&$dh) {
        global $mybb;

        if ($mybb->settings['mppermissionsgroups'] != -1 && !is_member($mybb->settings['mppermissionsgroups'])) {
            return;
        }

        if ($mybb->settings['mppermissionsenabled']) {
            switch ($mybb->get_input('myprofile_permissions')) {
                case 'buddies':
                    $dh->user_update_data['myprofilepermissions'] = 2;
                    break;
                case 'nobody':
                    $dh->user_update_data['myprofilepermissions'] = 1;
                    break;
                default:
                    $dh->user_update_data['myprofilepermissions'] = 0;
                    break;
            }
        }
    }

    public function member_profile_start() {
        global $mybb;

        if (!$mybb->settings['mppermissionsenabled'] || !$mybb->usergroup['canviewprofiles']) {
            return;
        }

        $memprofile = false;
        $uid = $mybb->get_input('uid', 1);
        if ($uid) {
            $memprofile = get_user($uid);
        } elseif ($mybb->user['uid']) {
            $memprofile = $mybb->user;
        }

        if ($mybb->settings['mppermissionsgroups'] != -1 && !is_member($mybb->settings['mppermissionsgroups'], array('usergroup' => $memprofile['usergroup'], 'additionalgroups' => $memprofile['additionalgroups']))) {
            return;
        }

        if (!$memprofile || !$memprofile['myprofilepermissions'] || $mybb->user['uid'] == $memprofile['uid'] || $mybb->usergroup['caneditprofiles']) {
            return;
        }

        require_once MYBB_ROOT . 'inc/functions_modcp.php';
        if (modcp_can_manage_user($memprofile['uid'])) {
            return;
        }

        if ($memprofile['myprofilepermissions'] == 1 || (!$memprofile['buddylist'] && !$memprofile['ignorelist'])) {
            error_no_permission();
        }

        if (my_strpos(',' . $memprofile['ignorelist'] . ',', ',' . $mybb->user['uid'] . ',') !== false) {
            error_no_permission();
        }

        if (!my_strpos(',' . $memprofile['buddylist'] . ',', ',' . $mybb->user['uid'] . ',') !== false) {
            error_no_permission();
        }
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
