<?php
session_start();
include_once '../includes/db.php';
include_once '../includes/functions.php';

if (!$_SESSION['user_id']) die('Not logged in');

$body = addslashes($_POST['body']);
$internal = $_POST['is_internal'] ? 1 : 0;
db_query("INSERT INTO comments (ticket_id, user_id, body, is_internal, created) VALUES (" . $_POST['ticket_id'] . ", " . $_SESSION['user_id'] . ", '$body', $internal, NOW())");
db_query("UPDATE tickets SET updated = NOW() WHERE id = " . $_POST['ticket_id']);

// send back the new <li> so the page can append it
echo '<li class="' . ($internal ? 'internal' : '') . '"><b>' . $_SESSION['full_name'] . '</b> <small>' . date('Y-m-d H:i:s') . '</small>'
    . ($internal ? ' <small>(internal)</small>' : '') . '<br />' . nl2br($_POST['body']) . '</li>';
