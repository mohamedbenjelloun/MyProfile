<?php

if(!defined("IN_MYPROFILE_UPGRADE")) {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$update_array = array("optionscode" => "select
2=2
3=3
4=4
5=5
6=6
7=7
8=8
9=9
10=10
15=15
25=25
30=30");
$db->update_query("settings", $update_array, "name='mpcommentsperpage'", "1");
rebuild_settings();

/* return the version we upgraded to */
return "0.5";