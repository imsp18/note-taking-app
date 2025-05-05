<?php
require_once 'config/database.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Redirect to index page with signup tab active
header('Location: index.php#signup');
exit;