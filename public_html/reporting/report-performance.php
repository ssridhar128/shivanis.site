<?php
require_once __DIR__ . '/auth.php';
requireSection('performance');
header('Location: table.php?type=performance');
exit;
