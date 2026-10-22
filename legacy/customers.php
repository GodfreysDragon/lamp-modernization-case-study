<?php
$page_title = 'Customers';
include 'includes/header.php';
check_login();

if ($_GET['delete']) {
    db_query("DELETE FROM customers WHERE id = " . $_GET['delete']);
    header('Location: customers.php?msg=Customer deleted');
    exit;
}

if ($_POST['name']) {
    db_query("INSERT INTO customers (name, company, email, phone, notes, created) VALUES ('" . $_POST['name'] . "', '" . $_POST['company'] . "', '" . $_POST['email'] . "', '" . $_POST['phone'] . "', '" . $_POST['notes'] . "', NOW())");
    header('Location: customers.php?msg=Customer added');
    exit;
}

$customers = db_fetch_all("SELECT * FROM customers ORDER BY name");
?>
<h2>Customers</h2>
<table class="list" width="100%" cellpadding="4">
  <tr><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th>Open tickets</th><th></th></tr>
<?php foreach ($customers as $c) {
    $open = db_fetch_one("SELECT COUNT(*) AS c FROM tickets WHERE customer_id = " . $c['id'] . " AND status != 'closed' AND deleted = 0"); ?>
  <tr>
    <td><?php echo $c['name']; ?></td>
    <td><?php echo $c['company']; ?></td>
    <td><?php echo $c['email']; ?></td>
    <td><?php echo $c['phone']; ?></td>
    <td><?php echo $open['c']; ?></td>
    <td><?php if (is_admin()) { ?><a href="customers.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('Delete?');">delete</a><?php } ?></td>
  </tr>
<?php } ?>
</table>

<h3>Add customer</h3>
<form method="post">
  Name: <input type="text" name="name" />
  Company: <input type="text" name="company" />
  Email: <input type="text" name="email" />
  Phone: <input type="text" name="phone" /><br />
  Notes: <textarea name="notes" rows="2" cols="60"></textarea>
  <input type="submit" value="Add" />
</form>
<?php include 'includes/footer.php'; ?>
