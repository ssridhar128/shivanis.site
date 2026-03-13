<?php
require_once __DIR__ . '/auth.php';
requireSection('static');
header('Location: table.php?type=static');
exit;
