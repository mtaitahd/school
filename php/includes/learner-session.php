<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$learner_logged_in = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'learner';
