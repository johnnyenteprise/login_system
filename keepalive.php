<?php
require_once 'includes/config.php';

// This will automatically validate and extend the session
if (validateSession()) {
    echo json_encode(['status' => 'active']);
} else {
    http_response_code(401);
    echo json_encode(['status' => 'expired']);
}
?>