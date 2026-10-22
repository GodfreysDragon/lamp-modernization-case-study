<?php
session_start();
include_once dirname(__FILE__) . '/db.php';
include_once dirname(__FILE__) . '/functions.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo $site_name; ?><?php if ($page_title) echo ' - ' . $page_title; ?></title>
<link rel="stylesheet" href="css/style.css" type="text/css" />
<script type="text/javascript" src="http://code.jquery.com/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="http://code.jquery.com/ui/1.9.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css" />
<script type="text/javascript" src="js/app.js"></script>
</head>
<body>
<div id="wrapper">
<div id="header">
  <h1><a href="index.php"><?php echo $site_name; ?></a></h1>
  <?php if ($_SESSION['user_id']) { ?>
  <div id="nav">
    <a href="index.php">Dashboard</a> |
    <a href="tickets.php">Tickets</a> |
    <a href="ticket_edit.php">New Ticket</a> |
    <a href="customers.php">Customers</a> |
    Logged in as <b><?php echo $_SESSION['full_name']; ?></b>
    (<a href="logout.php">logout</a>)
  </div>
  <?php } ?>
</div>
<div id="content">
<?php if ($_GET['msg']) { ?>
  <div class="msg"><?php echo $_GET['msg']; ?></div>
<?php } ?>
