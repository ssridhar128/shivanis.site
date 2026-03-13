<?php
require_once __DIR__ . '/auth.php';
requireSection('behavioral');
header('Location: table.php?type=activity');
exit;
