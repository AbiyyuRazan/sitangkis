<?php
// admin/logout.php
require_once __DIR__ . '/../config/app.php';
session_destroy();
redirect(BASE_URL . '/login.php');
