<?php
include_once dirname(__FILE__) . '/../config.php';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name) or die('Could not connect: ' . mysqli_connect_error());

function db_query($sql)
{
    global $conn;
    $result = mysqli_query($conn, $sql) or die('Query failed: ' . mysqli_error($conn) . '<br>SQL: ' . $sql);
    return $result;
}

function db_fetch_all($sql)
{
    $rows = array();
    $result = db_query($sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function db_fetch_one($sql)
{
    $result = db_query($sql);
    return mysqli_fetch_assoc($result);
}
