<?php
define("IN_MYBB", 1);
define("NO_ONLINE", 1);
require_once "global.php";
if(!$mybb->usergroup['cancp'])
{
    error_no_permission();
}
// Make sure both tables actually exist.
if(!$db->table_exists("profilecomments") || !$db->table_exists("myprofilecomments"))
{
    error("One or more tables that are required do not exist.");
}
// Count how many comments there are
$countquery = $db->simple_select("profilecomments", "COUNT(mid) as total");
$total = $db->fetch_field($countquery, "total");
// 500 per page should be sufficient to avoid time outs.
$perpage = 500;
if($mybb->input['page'])
{
    $page = intval($mybb->input['page']);
}
else
{
    $page = 1;
}
if(!$page || $page < 0)
{
    $page = 1;
}
$pages = ceil($total / $perpage);
if($page > $pages)
{
    echo "Comments converted successfully.  <a href=\"index.php\">Back To Forum</a>";
    exit;
}
$start = $page * $perpage - $perpage;
$commentquery = $db->simple_select("profilecomments", "*", "", array("order_by" => "mid", "order_dir" => "ASC", "limit" => $perpage, "limit_start" => $start));
$commentsinserted = 0;
while($comment = $db->fetch_array($commentquery))
{
    // Make sure to sanitize all data just in case.
    $newcomment = array(
    "userid" => (int)$comment['user'],
    "cuid" => (int)$comment['sender'],
    "message" => $db->escape_string($comment['text']),
    "approved" => 1,
    "isprivate" => 0,
    "time" => (int)$comment['date']
    );
    $db->insert_query("myprofilecomments", $newcomment);
    ++$commentsinserted;
}
$nextpage = $page +1;
$remaining = $total - $perpage;
$url = $_SERVER['PHP_SELF'] . "?page=" . $nextpage;
echo "Inserted " . $commentsinserted . " comments.  There are " . $remaining . " comments remaining.
<meta http-equiv=\"refresh\" content=\"2;URL={$url}\" />";
?>
