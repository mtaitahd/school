<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: login.php');
    exit;
}
header('Location: dashboard.php?claim=1');
exit;


