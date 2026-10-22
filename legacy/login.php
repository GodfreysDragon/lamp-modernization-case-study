<?php
session_start();
include_once 'includes/db.php';

if ($_POST['username']) {
    $sql = "SELECT * FROM users WHERE username = '" . $_POST['username'] . "' AND password = '" . md5($_POST['password']) . "'";
    $user = db_fetch_one($sql);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['is_admin'] = $user['is_admin'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password for ' . $_POST['username'];
    }
}
$page_title = 'Login';
include 'includes/header.php';
?>
<h2>Please log in</h2>
<?php if ($error) echo '<p class="error">' . $error . '</p>'; ?>
<form method="post" action="login.php">
  <table>
    <tr><td>Username:</td><td><input type="text" name="username" value="<?php echo $_POST['username']; ?>" /></td></tr>
    <tr><td>Password:</td><td><input type="password" name="password" /></td></tr>
    <tr><td></td><td><input type="submit" value="Login" /></td></tr>
  </table>
</form>
<?php include 'includes/footer.php'; ?>
