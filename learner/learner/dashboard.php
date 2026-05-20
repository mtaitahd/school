<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '../dashboard.php' . ($query !== '' ? '?' . $query : '');
header('Location: ' . $target, true, 301);
exit;



