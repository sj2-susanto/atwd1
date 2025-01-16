<?php
require 'data.php';

// Load the JSON data
$jsonData = file_get_contents(DB_FILE);
$exchangeData = json_decode($jsonData, true);

// Error_hash to hold error numbers and messages
define ('ERROR_HASH', array(
    1000 => 'Required parameter is missing',
    2000 => 'Action not recognized or is missing',
    2200 => 'Currency code not found for update',
    2500 => 'Error in service'
));

// Extract HTTP method and URI
$method = $_SERVER['REQUEST_METHOD'];

// Extract query parameters
$cur = strtoupper($_GET['cur'] ?? ''); // Ensure currency code is uppercase
$action = strtolower($_GET['action'] ?? '');

// Check if it's a DELETE request
if ($method === 'DELETE') {

    if ($action === 'del') {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$cur || !array_key_exists($cur, $exchangeData['currencies'])) {
            echo json_encode(['error' => ERROR_HASH[2200]]);
            exit();
        }
        
        unset($exchangeData['currencies'][$cur]);
        file_put_contents(DB_FILE, json_encode($exchangeData, JSON_PRETTY_PRINT));
        
        echo json_encode(['message' => "Currency '$cur' deleted successfully."]);
        exit();
    } else {
        error_log("DELETE request received with unsupported action: $action");
        echo json_encode(['error' => 'Action not recognized or is missing.']);
        exit();
    }
}

// Exit the script
//exit();
