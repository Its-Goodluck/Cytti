<?php
// Test script to simulate signup form submission
require_once 'config.php';
require_once 'config.php';
require_once 'db_connect.php';
require_once 'functions.php';

// Simulate POST data for valid signup
$_POST = [
    'username' => 'testuser123',
    'email' => 'test@example.com',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'terms' => 'on'
];

// Include the signup_process.php to test
include 'signup_process.php';
?>
