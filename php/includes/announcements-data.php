<?php
$kyh_urgent = $database->fetchOne(
    "SELECT * FROM announcements WHERE is_urgent = 1 ORDER BY created_at DESC LIMIT 1"
);

$kyh_standard = $database->fetchAll(
    "SELECT * FROM announcements WHERE is_urgent = 0 ORDER BY created_at DESC"
);
