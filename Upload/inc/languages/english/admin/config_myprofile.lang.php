<?php
/**
 * MyBB 1.8 English Language Pack
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 */

// plugin's info()
$l['mp_myprofile'] = "MyProfile";
$l['mp_myprofile_desc'] = "Enhances default users' profiles with tabbed profiles, comments, last visits and more.";

// settings
//// profile tabs
$l['mp_myprofile_tabs'] = "MyProfile Tabs";
$l['mp_myprofile_tabs_desc'] = "Here you can specify all the settings that are related to the tabbed profiles.";
$l['mp_myprofile_tabs_enabled'] = "MyProfile Tabs Enabled";
$l['mp_myprofile_tabs_enabled_desc'] = "Set to Yes if you want to enable tabbed profiles, or No to disable them.";
$l['mp_myprofile_tabs_effect'] = "Tabs Animation Effect";
$l['mp_myprofile_tabs_effect_desc'] = "Choose the animation effect that happens when changing tabs. You want to know how each effect looks? Try it out ;)";
$l['mp_myprofile_tabs_effect_flip'] = "Flip";
$l['mp_myprofile_tabs_effect_scaledown'] = "Scale Down";
$l['mp_myprofile_tabs_effect_scaleup'] = "Scale Up";

//// comments
$l['mp_myprofile_comments'] = "MyProfile Comments";
$l['mp_myprofile_comments_desc'] = "Here you can enhance your users' profiles with a powerful comment system.";
$l['mp_myprofile_comments_enabled'] = "MyProfile Comments Enabled";
$l['mp_myprofile_comments_enabled_desc'] = "Set to Yes if you want to enable profile comments, or No to disable them.";
$l['mp_myprofile_comments_ajax_enabled'] = "AJAX enabled";
$l['mp_myprofile_comments_ajax_enabled_desc'] = "Set to Yes to enable requests on AJAX, this allows the user to edit / store / retrieve comments without having to reload the page on every action performed. If you don't know what to set, leave it to Yes.";
$l['mp_myprofile_comments_notification'] = "Notification System";
$l['mp_myprofile_comments_notification_desc'] = "Please select the notification system that MyProfile Comments should use to inform users when they receive new comments. If you choose <em>MyAlerts or Alert bar</em>, it will first look for the MyAlerts plugin if installed, if not, it will use the Alert bar. If you choose <em>MyAlerts</em>, it will only use it if the plugin is installed.";
$l['mp_myprofile_comments_notification_myalerts_alertbar'] = "MyAlerts or Alert bar";
$l['mp_myprofile_comments_notification_myalerts'] = "MyAlerts";
$l['mp_myprofile_comments_notification_alertbar'] = "Alert bar";
$l['mp_myprofile_comments_notification_none'] = "None";
$l['mp_myprofile_comments_perpage'] = "Number Of Comments Per Page";
$l['mp_myprofile_comments_perpage_desc'] = "Choose the number of comments you want to enable per page.";
$l['mp_myprofile_comments_min_length'] = "Comment Minimum Length In Characters";
$l['mp_myprofile_comments_min_length_desc'] = "Enter the minimum number of characters that are allowed in comments.";
$l['mp_myprofile_comments_max_length'] = "Comment Maximum Length In Characters";
$l['mp_myprofile_comments_max_length_desc'] = "Enter the maximumnum number of characters that are allowed in each comment. Keep in mind that MyCodes such as [b] and [/b] that make text stylized are counted too. Enter <strong>0</strong> for unlimited.";
$l['mp_myprofile_comments_ignore'] = "Disallow Users on Ignore List To Comment";
$l['mp_myprofile_comments_ignore_desc'] = "Turn this option ON if you want to disallow users that are on the ignore list of another user to comment on his profile, this is very useful to avoid harassment between users.";
$l['mp_myprofile_comments_edit_time'] = "Comment Edit Time";
$l['mp_myprofile_comments_edit_time_desc'] = "Select the amount of minutes that the comment authors are allowed to edit their comments. Select 0 for unlimited.";
$l['mp_myprofile_comments_status_enabled'] = "Private/Public Status Enabled?";
$l['mp_myprofile_comments_status_enabled_desc'] = "Set this option to Yes if you want to enable private/public user status. A private status is only viewable by the author, the users themselves and the moderators.";
$l['mp_myprofile_comments_allow_img'] = "Allow Images On Comments";
$l['mp_myprofile_comments_allow_img_desc'] = "Set this to Yes if you want to enable users to include images in their comments.";
$l['mp_myprofile_comments_allow_video'] = "Allow Videos On Comments";
$l['mp_myprofile_comments_allow_video_desc'] = "Set this to Yes if you want to enable users to include videos in their comments.";
$l['mp_myprofile_comments_allow_smilies'] = "Allow Smilies On Comments";
$l['mp_myprofile_comments_allow_smilies_desc'] = "Set this to Yes if you want to enable users to include smilies in their comments.";
$l['mp_myprofile_comments_allow_mycode'] = "Allow MyCode On Comments";
$l['mp_myprofile_comments_allow_mycode_desc'] = "Set this to Yes if you want to enable users to include MyCode in their comments. MyCode corresponds to [b], [i] etc.";
$l['mp_myprofile_comments_filter_badwords'] = "Filter Bad Words On Comments";
$l['mp_myprofile_comments_filter_badwords_desc'] = "Set this to Yes if you want to filter bad words on comments.";
$l['mp_myprofile_comments_allow_html'] = "Allow HTML On Comments";
$l['mp_myprofile_comments_allow_html_desc'] = "Set this to Yes if you want to enable users to include HTML code in their comments. <strong>Please do NOT activate this if you don't know what it means, it could lead to a very serious security issue.</strong>";
$l['mp_myprofile_comments_show_wysiwyg'] = "Show WYSIWYG Editor On Comments";
$l['mp_myprofile_comments_show_wysiwyg_desc'] = "Set this to Yes if you want to show the WYSIWYG editor on comments, or to No if you want to show an empty text area.";
$l['mp_myprofile_comments_closed_on_banned'] = "Close Comment Forms On Banned Users?";
$l['mp_myprofile_comments_closed_on_banned_desc'] = "Set this to Yes to close new comment submission on banned users.";
$l['mp_myprofile_comments_show_stats'] = "Show Comment Count On User Statistics";
$l['mp_myprofile_comments_show_stats_desc'] = "Set this to Yes to show a comment counter on user statistics.";


//// last visitors
$l['mp_myprofile_visitors'] = "MyProfile Visitors";
$l['mp_myprofile_visitors_desc'] = "Enables you to show the last visitors on each user's profile.";
$l['mp_myprofile_visitors_enabled'] = "MyProfile Last Visitors Enabled";
$l['mp_myprofile_visitors_enabled_desc'] = "Set to Yes if you want to enable last visitors, or No to disable it.";
$l['mp_myprofile_visitors_record'] = "Number Of Visitors To Retrieve";
$l['mp_myprofile_visitors_record_desc'] = "Select the number of visitors you want to retrieve on each user's profile.";

// acp
$l['mp_options_can_use_image_background'] = "Can use image background?";
$l['mp_options_can_manage_comments'] = "Can manage comments? (Can edit and delete all comments)";
$l['mp_options_can_send_comments'] = "Can send comments?";
$l['mp_options_can_edit_own_comments'] = "Can edit own comments?";
$l['mp_options_can_delete_own_comments'] = "Can delete own comments?";
