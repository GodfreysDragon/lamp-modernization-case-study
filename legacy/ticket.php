<?php
include 'includes/header.php';
check_login();

$id = $_GET['id'];
$ticket = db_fetch_one("SELECT * FROM tickets WHERE id = $id");
if (!$ticket) {
    echo '<p class="error">Ticket not found</p>';
    include 'includes/footer.php';
    exit;
}
$comments = db_fetch_all("SELECT * FROM comments WHERE ticket_id = $id ORDER BY created ASC");
?>
<h2>#<?php echo $ticket['id']; ?> - <?php echo $ticket['subject']; ?></h2>
<p>
  <b>Customer:</b> <?php echo get_customer_name($ticket['customer_id']); ?><br />
  <b>Assigned to:</b> <?php echo get_user_name($ticket['assigned_to']); ?><br />
  <b>Priority:</b> <?php echo priority_label($ticket['priority']); ?><br />
  <b>Due:</b> <?php echo $ticket['due_date']; ?>
  <?php if (is_overdue($ticket['due_date'], $ticket['status'])) echo '<span class="overdue-flag">OVERDUE</span>'; ?><br />
  <b>Status:</b> <?php echo status_badge($ticket['status']); ?>
</p>
<div class="ticket-body"><?php echo nl2br($ticket['body']); ?></div>
<p><a href="ticket_edit.php?id=<?php echo $ticket['id']; ?>">Edit ticket</a></p>

<h3>Comments</h3>
<ul id="comments">
<?php foreach ($comments as $c) { ?>
  <li class="<?php echo $c['is_internal'] ? 'internal' : ''; ?>">
    <b><?php echo get_user_name($c['user_id']); ?></b> <small><?php echo $c['created']; ?></small>
    <?php if ($c['is_internal']) echo '<small>(internal)</small>'; ?><br />
    <?php echo nl2br($c['body']); ?>
  </li>
<?php } ?>
</ul>

<form id="comment-form">
  <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>" />
  <textarea name="body" rows="4" cols="60"></textarea><br />
  <label><input type="checkbox" name="is_internal" value="1" /> Internal note</label>
  <input type="submit" value="Add comment" />
  <span id="comment-status"></span>
</form>
<?php include 'includes/footer.php'; ?>
