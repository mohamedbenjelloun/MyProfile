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

/* static class, if that makes sense :D */

abstract class MyProfileUtils {

    /**
     * Used by other classes to escape the dangerous fields in $settings array, before inserting it in DB. 
     */
    public static function escape_settings($settings) {
        global $db;
        $disporder = 0;
        foreach ($settings as &$setting) {
            $setting["name"] = $db->escape_string($setting["name"]);
            $setting["title"] = $db->escape_string($setting["title"]);
            $setting["description"] = $db->escape_string($setting["description"]);
            // not touching the optionscode or the value
            $setting["disporder"] = ++$disporder;
            $setting["gid"] = (int) $setting["gid"];
        }
        return $settings;
    }

    /**
     * Insert a single settinggroups into the DB and returns its assigned gid.
     */
    public static function insert_settinggroups($settinggroups) {
        global $db;
        $query = $db->simple_select("settinggroups", "COUNT(*) as `cnt`");
        $rows = $db->fetch_field($query, "cnt");
        $settinggroups = array(
            "name" => $settinggroups["name"],
            "title" => $db->escape_string($settinggroups["title"]),
            "description" => $db->escape_string($settinggroups["description"]),
            "disporder" => ++$rows,
            "isdefault" => $settinggroups["isdefault"]
        );
        return $db->insert_query("settinggroups", $settinggroups);
    }
    
    /**
     * Get a setting group GID from name
     */
    public static function get_settinggroup_gid($settinggroup_name) {
        global $db;
        $name = $db->escape_string($settinggroup_name);
        $query = $db->simple_select("settinggroups", "gid", "name='{$name}'");
        $array = $db->fetch_array($query);
        if (isset($array["gid"])) {
            return (int) $array["gid"];
        }
        return null;
    }

    /**
     * Delete an array settinggroups
     */
    public static function delete_settinggroups($settinggroups) {
        global $db;
        $settinggroups = "'" . implode("','", $settinggroups) . "'";
        $db->delete_query("settinggroups", "name IN ({$settinggroups})");
    }

    /**
     * Insert an array of settings
     */
    public static function insert_settings($settings) {
        global $db;
        $settings = self::escape_settings($settings);
        $db->insert_query_multiple("settings", $settings);
        rebuild_settings();
    }

    /**
     * Delete a bunch of settings
     */
    public static function delete_settings($settings) {
        global $db;
        $settings = "'" . implode("','", $settings) . "'";
        $db->delete_query("settings", "name IN ({$settings})");
    }

    /**
     * Insert new templates into the DB.
     */
    public static function insert_templates($templates) {
        global $db;
        $insert_array = array();
        foreach ($templates as $template_title => $template) {
            $insert_array[] = array(
                "title" => $db->escape_string($template_title),
                "template" => $db->escape_string($template),
                "sid" => -2,
                "version" => 1800,
                "dateline" => TIME_NOW
            );
        }
        $db->insert_query_multiple("templates", $insert_array);
    }

    /**
     * Delete a bunch of templates
     */
    public static function delete_templates($templates) {
        global $db;
        $templates = "'" . implode("','", $templates) . "'";
        $db->delete_query("templates", "title IN ({$templates})");
    }

    /**
     * Output content as JSON, exits right afterwards, if $exit is passed and equals false, this method won't exit.
     */
    public static function output_json($content, $exit = true) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($content);
        if ($exit) {
            exit;
        }
    }

    /**
     * Send an error header to AJAX
     */
    public static function output_error($error_message, $error_header = 404, $exit = true) {
        $messages = array(
            // Client Error 4xx
            400 => "Bad Request",
            401 => "Unauthorized",
            402 => "Payment Required",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            406 => "Not Acceptable",
            407 => "Proxy Authentication Required",
            408 => "Request Timeout",
            409 => "Conflict",
            410 => "Gone",
            411 => "Length Required",
            412 => "Precondition Failed",
            413 => "Request Entity Too Large",
            414 => "Request-URI Too Long",
            415 => "Unsupported Media Type",
            416 => "Requested Range Not Satisfiable",
            417 => "Expectation Failed",
            // Server Error 5xx
            500 => "Internal Server Error",
            501 => "Not Implemented",
            502 => "Bad Gateway",
            503 => "Service Unavailable",
            504 => "Gateway Timeout",
            505 => "HTTP Version Not Supported",
            509 => "Bandwidth Limit Exceeded"
        );
        /* any $message (event "X") is valid, but it's recommended to send the right message to the browser */
        $message = "X";
        if (isset($messages[$error_header])) {
            $message = $messages[$error_header];
        }
        header("HTTP/1.0 {$error_header} {$message}", true, $error_header);
        echo $error_message;
        if ($exit) {
            exit;
        }
    }

    /**
     * Check if MyAlerts 2.0 exists.
     */
    public static function myalerts_exists() {
        return function_exists("myalerts_is_installed") && myalerts_is_installed();
    }

    public static function lang_load_myprofile() {
        global $lang;
        if (!$lang->mp_myprofile) {
            $lang->load("myprofile");
        }
    }

    public static function lang_load_config_myprofile() {
        global $lang;
        if (!$lang->mp_my_profile) {
            $lang->load("config_myprofile");
        }
    }

    private function __construct() {
        
    }

    private function __clone() {
        
    }

}
