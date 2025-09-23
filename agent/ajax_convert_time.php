<?php
// This script receives a date and a timezone, converts it to Malaysia time,
// and returns the result as JSON for our form to display.

require_once __DIR__ . "/../includes/config.php";

// Set the content type to JSON
header('Content-Type: application/json');

// Get data from the request
$date_string = trim($_GET['date'] ?? '');
$customer_timezone = trim($_GET['tz'] ?? '');

// Basic validation
if (empty($date_string) || empty($customer_timezone)) {
    echo json_encode(['error' => 'Missing required parameters.']);
    exit;
}

try {
    // Use PHP's robust DateTime and DateTimeZone objects for accurate conversion
    $malaysia_tz = new DateTimeZone(TIMEZONE);
    $customer_tz = new DateTimeZone($customer_timezone);

    $date_obj = new DateTime($date_string, $customer_tz);
    $date_obj->setTimezone($malaysia_tz);
    
    // Format the converted time into a user-friendly string
    $converted_string = $date_obj->format('M j, Y, g:i A');

    // Send the successful response back to the JavaScript
    echo json_encode(['success' => true, 'convertedTime' => $converted_string]);

} catch (Exception $e) {
    // Handle any errors during date/time processing
    echo json_encode(['success' => false, 'error' => 'Invalid date or timezone provided.']);
}
