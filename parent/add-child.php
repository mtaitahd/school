<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: login');
    exit;
}
header('Location: dashboard?claim=1');
exit;


