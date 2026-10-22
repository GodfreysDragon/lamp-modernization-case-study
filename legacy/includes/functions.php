<?php
// Shared helpers. Pure functions are at the top so the cron scripts can reuse them.

function priority_label($p)
{
    if ($p == 1) return 'Low';
    if ($p == 2) return 'Normal';
    if ($p == 3) return 'High';
    if ($p == 4) return 'URGENT';
    return 'Normal';
}

// SLA: low = 5 days, normal = 3 days, high = 1 day, urgent = same day
function calc_due_date($priority, $from = null)
{
    if ($from == null) $from = time();
    if ($priority == 1) $days = 5;
    else if ($priority == 2) $days = 3;
    else if ($priority == 3) $days = 1;
    else $days = 0;
    return date('m/d/Y', $from + ($days * 86400));
}

function is_overdue($due_date, $status, $today = null)
{
    if ($today == null) $today = date('m/d/Y');
    if ($status == 'closed') return false;
    if ($due_date == '') return false;
    // compare dates
    return $due_date < $today;
}

function status_badge($status)
{
    $colors = array('open' => '#c00', 'pending' => '#e90', 'closed' => '#090');
    $color = isset($colors[$status]) ? $colors[$status] : '#999';
    return '<span class="badge" style="background:' . $color . '">' . ucfirst($status) . '</span>';
}

function short_text($text, $len = 60)
{
    if (strlen($text) > $len) return substr($text, 0, $len) . '...';
    return $text;
}

// ---- the functions below need the database ----

function check_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function is_admin()
{
    return $_SESSION['is_admin'] == 1;
}

function get_user_name($id)
{
    if (!$id) return '<i>Unassigned</i>';
    $row = db_fetch_one("SELECT full_name FROM users WHERE id = $id");
    return $row['full_name'];
}

function get_customer_name($id)
{
    $row = db_fetch_one("SELECT name, company FROM customers WHERE id = $id");
    return $row['name'] . ' (' . $row['company'] . ')';
}

function count_comments($ticket_id)
{
    $row = db_fetch_one("SELECT COUNT(*) AS c FROM comments WHERE ticket_id = $ticket_id");
    return $row['c'];
}
