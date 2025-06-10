<?php

session_start();

date_default_timezone_set('Asia/Manila');
$db = new mysqli("localhost", "root", "", "amsbs");

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

?>