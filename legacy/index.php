<?php
$page_title = 'Dashboard';
include 'includes/header.php';
check_login();

// one query per status so the numbers are always right
$statuses = array('open', 'pending', 'closed');
$counts = array();
foreach ($statuses as $s) {
    $row = db_fetch_one("SELECT COUNT(*) AS c FROM tickets WHERE status = '$s' AND deleted = 0");
    $counts[$s] = $row['c'];
}

$mine = db_fetch_all("SELECT * FROM tickets WHERE assigned_to = " . $_SESSION['user_id'] . " AND status != 'closed' AND deleted = 0 ORDER BY id DESC");
?>
<h2>Dashboard</h2>
<table class="stats">
  <tr>
  <?php foreach ($counts as $s => $c) { ?>
    <td><div class="stat"><?php echo status_badge($s); ?><br /><big><?php echo $c; ?></big></div></td>
  <?php } ?>
  </tr>
</table>

<h3>My open tickets (<?php echo count($mine); ?>)</h3>
<table class="list" width="100%" cellpadding="4">
  <tr><th>#</th><th>Subject</th><th>Customer</th><th>Priority</th><th>Due</th><th>Comments</th></tr>
<?php foreach ($mine as $t) { ?>
  <tr class="<?php echo is_overdue($t['due_date'], $t['status']) ? 'overdue' : ''; ?>">
    <td><?php echo $t['id']; ?></td>
    <td><a href="ticket.php?id=<?php echo $t['id']; ?>"><?php echo $t['subject']; ?></a></td>
    <td><?php echo get_customer_name($t['customer_id']); ?></td>
    <td><?php echo priority_label($t['priority']); ?></td>
    <td><?php echo $t['due_date']; ?></td>
    <td><?php echo count_comments($t['id']); ?></td>
  </tr>
<?php } ?>
</table>
<?php include 'includes/footer.php'; ?>
