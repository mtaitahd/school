<?php
/** Catch bad /learner/learner/activities.php URLs and redirect to the correct page */
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '../activities.php' . ($query !== '' ? '?' . $query : '');
header('Location: ' . $target, true, 301);
exit;



