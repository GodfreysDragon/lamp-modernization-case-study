<?php
// Called from js/app.js when the status dropdown changes.
// NOTE: there is no check_login() here. Anyone who can reach the URL can change any ticket.
include_once '../includes/db.php';

db_query("UPDATE tickets SET status = '" . $_POST['status'] . "', updated = NOW() WHERE id = " . $_POST['id']);

echo 'OK';
