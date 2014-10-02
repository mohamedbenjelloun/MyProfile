<?php
define("IN_MYBB", 1);
define("THIS_SCRIPT", "comments.php");
require_once "global.php";
require_once "inc/class_parser.php";
if(!$db->table_exists("myprofilecomments") || !$mybb->settings['mpcommentsenabled']) // Disable the page if comments are disabled
{
    error_no_permission();
}
if(!$mybb->input['uid1'] || !$mybb->input['uid2'])
{
    error_no_permission();
}
$uid1 = (int) $mybb->input['uid1'];
$uid2 = (int) $mybb->input['uid2'];
$approved = 0;
if($mybb->usergroup['canmanagecomments'])
{
    $approved = -1;
    $isprivate = -1;
}
// Now count the comments between the users
if($uid1==$mybb->user['uid'])
{
    $countquery = $db->simple_select("myprofilecomments", "COUNT(cid) as commentcount", "cuid=$uid1 AND userid=$uid2 OR cuid=$uid2 AND userid=$uid1 AND approved>$approved");
}
else if($uid2 == $mybb->user['uid'])
{
    $countquery = $db->simple_select("myprofilecomments", "COUNT(cid) as commentcount", "cuid=$uid2 AND userid=$uid1 OR cuid=$uid1 AND userid=$uid2 AND approved>$approved");
}
else
{
    $countquery = $db->simple_select("myprofilecomments", "COUNT(cid) as commentcount", "cuid=$uid1 AND userid=$uid2 AND approved>$approved AND isprivate>$isprivate OR cuid=$uid2 AND userid=$uid1 AND approved>$approved AND isprivate>$isprivate");
}
$total = $db->fetch_field($countquery, "commentcount");
if($total == 0)
{
    eval("\$comments = \"".$templates->get("myprofile_comments_no_comment")."\";");
    eval("\$commentpage = \"".$templates->get("myprofile_comments_page")."\";");
    output_page($commentpage);
}
// Calculate our current page
if($mybb->input['page'])
{
    $page = (int) $mybb->input['page'];
}
else
{
    $page = 1;
}
if($page < 1)
{
    $page = 1;
}
$perpage = (int) $mybb->settings['mpcomments_per_page'];
if(!$perpage) // Fallback in case they somehow screw things up
{
    $perpage = 10;
}
$pages = ceil($total / $perpage);
if($page > $pages)
{
    $page = $pages;
}
$pagination = multipage($total, $perpage, $page, "comments.php?uid1=$uid1&amp;uid2=$uid2");
$start = $page * $perpage - $perpage;
// Finally fetch the comments
if($mybb->user['uid'] == $uid1)
{
    $query = $query = $db->query("SELECT c.*, u.username, u.usergroup, u.displaygroup
        FROM " . TABLE_PREFIX . "myprofilecomments c
        LEFT JOIN " . TABLE_PREFIX . "users u ON(c.cuid=u.uid)
        WHERE c.cuid=$uid1 AND c.userid=$uid2 OR c.cuid=$uid2 AND c.userid=$uid1 AND c.approved>$approved
        ORDER BY `c.time` ASC
        LIMIT $start , $perpage");
}
else if($mybb->user['uid'] == $uid2)
{
    $query = $db->query("SELECT c.*, u.username, u.usergroup, u.displaygroup
        FROM " . TABLE_PREFIX . "myprofilecomments c
        LEFT JOIN " . TABLE_PREFIX . "users u ON(c.cuid=u.uid)
        WHERE c.cuid=$uid2 AND c.userid=$uid1 OR c.cuid=$uid1 AND c.userid=$uid2 AND c.approved>$approved
        ORDER BY `c.time` ASC
        LIMIT $start , $perpage");
}
else
{
    $query = $db->query("SELECT c.*, u.username, u.usergroup, u.displaygroup
    FROM " . TABLE_PREFIX . "myprofilecomments c
    LEFT JOIN " . TABLE_PREFIX . "users u ON(c.cuid=u.uid)
    WHERE c.cuid=$uid1 AND c.userid=$uid2 AND c.approved>$approved AND c.isprivate>$isprivate OR 
    c.cuid=$uid2 AND c.userid=$uid1 AND c.approved>$approved AND c.isprivate>$isprivate
    ORDER BY `c.time` ASC
    LIMIT $start , $perpage");
}
// Sett up the parser options
$parser_options = array(
    "allow_html" => (int) $mybb->settings["mpcommentsallowhtml"],
	"allow_mycode" => (int) $mybb->settings["mpcommentsallowmycode"],
	"allow_smilies" => (int) $mybb->settings["mpcommentsallowsmilies"],
	"allow_imgcode" => (int) $mybb->settings["mpcommentsallowimg"],
	"allow_videocode" => (int) $mybb->settings["mpcommentsallowvideo"],
	"filter_badwords" => (int) $mybb->settings["mpcommentsfilterbadwords"]
    );
while($comment = $db->fetch_array($query))
{
    $formattedname = format_name($comment['username'], $comment['usergroup'], $comment['displaygroup']);
    $profile_link = build_profile_link($formattedname, $comment['cuid']);
    $message = $parser->parse_message($message, $parser_options);
    eval("\$comments .= \"".$templates->get("myprofile_comments_comment")."\";");
}
eval("\$commentpage = \"".$templates->get("myprofile_comments_page")."\";");
output_page($commentpage);
?>
