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

// plugin's info()
$l['mp_myprofile'] = "MyProfile";
$l['mp_myprofile_desc'] = "Enhances default users' profiles with comments, last visits buddy lists and more.";

// settings
//// comments
$l['mp_myprofile_comments'] = "MyProfile Comments";
$l['mp_myprofile_comments_desc'] = "Here you can enhance your users' profiles with a powerful comment system.";
$l['mp_myprofile_comments_enabled'] = "MyProfile Comments Enabled";
$l['mp_myprofile_comments_enabled_desc'] = "Set to Yes if you want to enable profile comments, or No to disable them.";
$l['mp_myprofile_comments_ajax_enabled'] = "AJAX enabled";
$l['mp_myprofile_comments_ajax_enabled_desc'] = "Set to Yes to enable requests on AJAX, this allows the user to edit / store / retrieve comments without having to reload the page on every action performed. If you don't know what to set this to, leave it to Yes. <strong>Please notice that some actions will still be performed on AJAX</strong>.";
$l['mp_myprofile_comments_notification'] = "Notification System";
$l['mp_myprofile_comments_notification_desc'] = "Please select the notification system that MyProfile Comments should use to inform users when they receive new comments. If you choose <strong>MyAlerts or Alert bar</strong>, it will first look for the MyAlerts plugin if installed, if not, it will use the Alert bar. If you choose <em>MyAlerts</em>, it will only use it if the plugin is installed.";
$l['mp_myprofile_comments_notification_myalerts_alertbar'] = "MyAlerts or Alert bar";
$l['mp_myprofile_comments_notification_myalerts'] = "MyAlerts";
$l['mp_myprofile_comments_notification_alertbar'] = "Alert bar";
$l['mp_myprofile_comments_notification_none'] = "None";
$l['mp_myprofile_comments_perpage'] = "Number Of Comments Per Page";
$l['mp_myprofile_comments_perpage_desc'] = "Choose the number of comments you want to enable per page.";
$l['mp_myprofile_comments_min_length'] = "Comment Minimum Length In Characters";
$l['mp_myprofile_comments_min_length_desc'] = "Enter the minimum number of characters that are allowed in comments.";
$l['mp_myprofile_comments_max_length'] = "Comment Maximum Length In Characters";
$l['mp_myprofile_comments_max_length_desc'] = "Enter the maximum number of characters that are allowed in each comment. Keep in mind that MyCodes such as [b] and [/b] that make text stylized are counted too.";
$l['mp_myprofile_comments_ignore'] = "Disallow Users on Ignore List To Comment";
$l['mp_myprofile_comments_ignore_desc'] = "Turn this option ON if you want to disallow users that are on the ignore list of another user to comment on his profile, this is very useful to avoid harassment between users.";
$l['mp_myprofile_comments_edit_time'] = "Comment Edit Time";
$l['mp_myprofile_comments_edit_time_desc'] = "Select the amount of <strong>minutes</strong> that the comment authors are allowed to edit their comments. Select 0 for unlimited.";
$l['mp_myprofile_comments_flood_time'] = "Comment Flood Time";
$l['mp_myprofile_comments_flood_time_desc'] = "Set the amount of <strong>seconds</strong> that comment authors should wait before being able to make another comment.";
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

//// buddylist
$l['mp_myprofile_buddylist'] = "MyProfile Buddy List";
$l['mp_myprofile_buddylist_desc'] = "Enables you to show a buddy list box on each user's profile.";
$l['mp_myprofile_buddylist_enabled']  = "MyProfile Buddy List Enabled";
$l['mp_myprofile_buddylist_enabled_desc'] = "Set to Yes if you want to enable buddy list box on users profiles, or No to disable it.";
$l['mp_myprofile_buddylist_record'] = "Number Of Buddies To Retrieve On Each Page";
$l['mp_myprofile_buddylist_record_desc'] = "Select the number of buddies you want to retrieve on each user's profile. Each line is constitued of 4 buddies, so selecting 8 for example will display a maximum of 2 lines.";
$l['mp_myprofile_buddylist_avatar_max_dimensions'] = "Avatar Maximum Dimensions";
$l['mp_myprofile_buddylist_avatar_max_dimensions_desc'] = "Set the maximum avatar dimensions, in the form <b>HEIGHTxWIDTH</b>, where HEIGHT and WIDTH are respectively the maximum height and width of the avatar.";
 
//// permissions
$l['mp_myprofile_referredby'] = "MyProfile ReferredBy";
$l['mp_myprofile_referredby_desc'] = "Enables your users to show who referred them in their profiles.";
$l['mp_myprofile_referredby_enabled'] = "MyProfile ReferredBy Enabled";
$l['mp_myprofile_referredby_enabled_desc'] = "Set to Yes if you want to enable the ReferredBy feature on users profiles, or No to disable it.";

// acp
$l['mp_options_can_use_image_background'] = "Can use image background?";
$l['mp_options_can_manage_comments'] = "Can manage comments? (Can edit and delete all comments)";
$l['mp_options_can_send_comments'] = "Can send comments?";
$l['mp_options_can_edit_own_comments'] = "Can edit own comments?";
$l['mp_options_can_delete_own_comments'] = "Can delete own comments?";
