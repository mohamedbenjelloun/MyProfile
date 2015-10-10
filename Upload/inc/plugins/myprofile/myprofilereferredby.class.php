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
if (defined('THIS_SCRIPT') && THIS_SCRIPT == "member.php") {
    global $templatelist;
    $templatelist .= ",myprofile_referredby";
}

/* load template */
$plugins->add_hook("member_profile_end", array(MyProfileReferredBy::get_instance(), "member_profile_end"));

class MyProfileReferredBy {

    private static $instance = null;

    public function install() {
        global $db, $lang;
        MyProfileUtils::lang_load_config_myprofile();

        $gid = MyProfileUtils::insert_settinggroups(array(
                    "name" => "myprofilereferredby",
                    "title" => $lang->mp_myprofile_referredby,
                    "description" => $lang->mp_myprofile_referredby_desc,
                    "isdefault" => 0
        ));

        MyProfileUtils::insert_settings(array(
            array(
                "name" => "mpreferredbyenabled",
                "title" => $lang->mp_myprofile_referredby_enabled,
                "description" => $lang->mp_myprofile_referredby_enabled_desc,
                "optionscode" => "yesno",
                "value" => 1,
                "gid" => $gid
            )
        ));
    }

    public function is_installed() {
        global $settings;
        return isset($settings['mpreferredbyenabled']);
    }

    public function uninstall() {
        global $db;

        MyProfileUtils::delete_settings(array("mpreferredbyenabled"));

        MyProfileUtils::delete_settinggroups(array("myprofilereferredby"));
    }

    public function activate() {
        require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

        MyProfileUtils::insert_templates(array(
            'myprofile_referredby' => '<tr>
	<td class="trow1" valign="top"><strong>{$lang->mp_referredby}</strong></td>
	<td class="trow1">{$referrer[\'referrer_formatted\']}</td>
</tr>'
        ));

        find_replace_templatesets("member_profile", "#" . preg_quote('{$referrals}') . "#i", '{$myprofile_referredby}{$referrals}');
    }

    public function deactivate() {
        require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

        MyProfileUtils::delete_templates(array("myprofile_referredby"));

        find_replace_templatesets("member_profile", "#" . preg_quote('{$myprofile_referredby}') . "#i", '', 0);
    }

    public function member_profile_end() {
        global $mybb;

        if (!$mybb->settings['mpreferredbyenabled'] || !$mybb->settings['usereferrals']) {
            return;
        }

        global $db, $memprofile, $myprofile_referredby, $lang, $templates;
        MyProfileUtils::lang_load_myprofile();

        $myprofile_referredby = '';

        $query = $db->query("
			SELECT u.uid, u.referrer, ru.uid AS referrer_uid, ru.username AS referrer_username, ru.usergroup AS referrer_usergroup, ru.displaygroup AS referrer_displaygroup
			FROM " . TABLE_PREFIX . "users u
			LEFT JOIN " . TABLE_PREFIX . "users ru ON (ru.uid=u.referrer) 
			WHERE u.referrer>'0' AND u.uid='" . (int) $memprofile['uid'] . "'
			LIMIT 1
		");
        $referrer = $db->fetch_array($query);

        if (!$referrer['referrer_uid']) {
            $referrer['referrer_formatted'] = $lang->mp_referredby_none;
        }

        $referrer['referrer_username'] = htmlspecialchars_uni($referrer['referrer_username']);
        if ($referrer['referrer_uid']) {
            $referrer['referrer_formatted'] = build_profile_link(
                    format_name(htmlspecialchars_uni($referrer['referrer_username']), $referrer['referrer_usergroup'], $referrer['referrer_displaygroup']), $referrer['referrer_uid']);
        }

        eval('$myprofile_referredby = "' . $templates->get('myprofile_referredby') . '";');
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
