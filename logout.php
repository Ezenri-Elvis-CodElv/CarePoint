<?php
require_once __DIR__ . '/includes/functions.php';

unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['tracked'], $_SESSION['recent_bookings']);
session_regenerate_id(true);
flash('success', 'You have been logged out successfully.');
redirect('index.php');
