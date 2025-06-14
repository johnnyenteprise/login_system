<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$test_email = 'aseinjohn1@gmail.com'; // Replace with your real email
$subject = "Test Email from Your Website";
$body = "If you received this, your email system is working!";

if (sendEmail($test_email, $subject, $body)) {
    echo "Email sent! Check your inbox (and spam folder).";
} else {
    echo "Failed to send. Check server error logs for details.";
}
?>
