<?php
include 'includes/header.php';
check_login();

$id = $_GET['id'];

if ($_POST['subject']) {
    $subject = addslashes($_POST['subject']);
    $body = addslashes($_POST['body']);
    $priority = $_POST['priority'];

    // work out due date (copied from the old cron script, should match calc_due_date)
    if ($priority == 1) $due = date('m/d/Y', strtotime('+5 days'));
    elseif ($priority == 2) $due = date('m/d/Y', strtotime('+3 days'));
    elseif ($priority == 3) $due = date('m/d/Y', strtotime('+1 day'));
    elseif ($priority == 4) $due = date('m/d/Y', strtotime('+1 day')); // urgent

    $assigned = $_POST['assigned_to'] ? $_POST['assigned_to'] : 'NULL';

    if ($id) {
        db_query("UPDATE tickets SET subject = '$subject', body = '$body', priority = $priority, due_date = '$due',
                  customer_id = " . $_POST['customer_id'] . ", assigned_to = $assigned, updated = NOW() WHERE id = $id");
    } else {
        db_query("INSERT INTO tickets (customer_id, assigned_to, subject, body, status, priority, due_date, created, updated)
                  VALUES (" . $_POST['customer_id'] . ", $assigned, '$subject', '$body', 'open', $priority, '$due', NOW(), NOW())");
        $id = mysqli_insert_id($conn);
    }
    header('Location: ticket.php?id=' . $id . '&msg=Ticket saved');
    exit;
}

$ticket = array('subject' => '', 'body' => '', 'priority' => 2, 'customer_id' => '', 'assigned_to' => '');
if ($id) {
    $ticket = db_fetch_one("SELECT * FROM tickets WHERE id = $id");
}
$users = db_fetch_all("SELECT id, full_name FROM users ORDER BY full_name");
$customer_label = $ticket['customer_id'] ? get_customer_name($ticket['customer_id']) : '';
?>
<h2><?php echo $id ? 'Edit ticket #' . $id : 'New ticket'; ?></h2>
<form method="post" onsubmit="return validateTicketForm();">
  <table>
    <tr><td>Customer:</td><td>
      <input type="text" id="customer-search" value="<?php echo $customer_label; ?>" size="40" />
      <input type="hidden" name="customer_id" id="customer_id" value="<?php echo $ticket['customer_id']; ?>" />
    </td></tr>
    <tr><td>Subject:</td><td><input type="text" name="subject" id="subject" size="60" value="<?php echo $ticket['subject']; ?>" /></td></tr>
    <tr><td>Description:</td><td><textarea name="body" rows="8" cols="60"><?php echo $ticket['body']; ?></textarea></td></tr>
    <tr><td>Priority:</td><td><select name="priority">
      <?php for ($p = 1; $p <= 4; $p++) { ?>
        <option value="<?php echo $p; ?>" <?php if ($ticket['priority'] == $p) echo 'selected'; ?>><?php echo priority_label($p); ?></option>
      <?php } ?>
    </select></td></tr>
    <tr><td>Assign to:</td><td><select name="assigned_to">
      <option value="">-- nobody --</option>
      <?php foreach ($users as $u) { ?>
        <option value="<?php echo $u['id']; ?>" <?php if ($ticket['assigned_to'] == $u['id']) echo 'selected'; ?>><?php echo $u['full_name']; ?></option>
      <?php } ?>
    </select></td></tr>
    <tr><td></td><td><input type="submit" value="Save" /></td></tr>
  </table>
</form>
<?php include 'includes/footer.php'; ?>
