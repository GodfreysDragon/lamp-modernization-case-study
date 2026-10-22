<?php
// HelpDesk Pro configuration
// TODO: move these out of the repo (2010)
$db_host = getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost';
$db_user = 'root';
$db_pass = 'hunter2';
$db_name = 'helpdesk';

$site_name = 'HelpDesk Pro';
$per_page = 20;

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
ini_set('display_errors', 1);
