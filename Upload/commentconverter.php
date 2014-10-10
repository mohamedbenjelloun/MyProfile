<?php

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 https://github.com/dragonexpert
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
