<?php
// functions.php

// Format price with Nepali Rupee symbol
function simple_price($price) {
    return 'Rs ' . number_format($price, 0);
}

// Format date nicely
function format_date($date) {
    return date('F j, Y \a\t g:i A', strtotime($date));
}

// Calculate time left for auction
function get_time_left($endDate) {
    $endDate = new DateTime($endDate);
    $now = new DateTime();
    $interval = $now->diff($endDate);
    
    if ($endDate < $now) {
        return 'Auction ended';
    } else if ($interval->d > 0) {
        return $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
    } else if ($interval->h > 0) {
        return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
    } else {
        return 'Less than 1 hour';
    }
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Get current user ID
function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Sanitize output
function safe_output($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>