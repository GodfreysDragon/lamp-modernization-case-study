<?php
$page_title = 'Tickets';
include 'includes/header.php';
check_login();

$where = "WHERE deleted = 0";
if ($_GET['status']) {
    $where .= " AND status = '" . $_GET['status'] . "'";
}
if ($_GET['q']) {
    $where .= " AND (subject LIKE '%" . $_GET['q'] . "%' OR body LIKE '%" . $_GET['q'] . "%')";
}

$page = $_GET['page'] ? $_GET['page'] : 1;
$start = ($page - 1) * $per_page;

$total = db_fetch_one("SELECT COUNT(*) AS c FROM tickets $where");
$tickets = db_fetch_all("SELECT * FROM tickets $where ORDER BY id DESC LIMIT $start, $per_page");
?>
<h2>Tickets</h2>
<form method="get" action="tickets.php">
  Search: <input type="text" name="q" value="<?php echo $_GET['q']; ?>" />
  Status: <select name="status">
    <option value="">-- any --</option>
    <option value="open" <?php if ($_GET['status'] == 'open') echo 'selected'; ?>>Open</option>
    <option value="pending" <?php if ($_GET['status'] == 'pending') echo 'selected'; ?>>Pending</option>
    <option value="closed" <?php if ($_GET['status'] == 'closed') echo 'selected'; ?>>Closed</option>
  </select>
  <input type="submit" value="Filter" />
</form>

<table class="list" width="100%" cellpadding="4">
  <tr><th>#</th><th>Subject</th><th>Customer</th><th>Assigned</th><th>Priority</th><th>Due</th><th>Status</th><th>Comments</th></tr>
<?php foreach ($tickets as $t) { ?>
  <tr class="<?php echo is_overdue($t['due_date'], $t['status']) ? 'overdue' : ''; ?>">
    <td><?php echo $t['id']; ?></td>
    <td><a href="ticket.php?id=<?php echo $t['id']; ?>"><?php echo short_text($t['subject']); ?></a></td>
    <td><?php echo get_customer_name($t['customer_id']); ?></td>
    <td><?php echo get_user_name($t['assigned_to']); ?></td>
    <td><?php echo priority_label($t['priority']); ?></td>
    <td><?php echo $t['due_date']; ?></td>
    <td>
      <select class="status-change" data-id="<?php echo $t['id']; ?>">
        <?php foreach (array('open', 'pending', 'closed') as $s) { ?>
          <option value="<?php echo $s; ?>" <?php if ($t['status'] == $s) echo 'selected'; ?>><?php echo ucfirst($s); ?></option>
        <?php } ?>
      </select>
    </td>
    <td><?php echo count_comments($t['id']); ?></td>
  </tr>
<?php } ?>
</table>

<p>
<?php for ($i = 1; $i <= ceil($total['c'] / $per_page); $i++) { ?>
  <a href="tickets.php?page=<?php echo $i; ?>&q=<?php echo $_GET['q']; ?>&status=<?php echo $_GET['status']; ?>"<?php if ($i == $page) echo ' class="current"'; ?>><?php echo $i; ?></a>
<?php } ?>
</p>
<?php include 'includes/footer.php'; ?>
