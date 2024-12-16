<?php
require '../src/CurrencyConverter.php';

// Error handler function
function sendError($code, $message, $format = 'json') {
    $error = [
        'code' => $code,
        'message' => $message
    ];

    if ($format == 'xml') {
        // Convert the error array to XML format
        $xml = new SimpleXMLElement('<error/>');
        $xml->addChild('code', $code);
        $xml->addChild('message', $message);
        header('Content-Type: application/xml');
        echo $xml->asXML();
    } else {
        // Return the error as JSON
        header('Content-Type: application/json');
        echo json_encode($error);
    }
    exit; // Stop further execution after sending an error
}

$action = $_GET['action'] ?? null;
$converter = new CurrencyConverter();

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$amount = $_GET['amnt'] ?? null;
$format = $_GET['format'] ?? 'json';
$currency = $_GET['cur'] ?? null;

// Check for required parameters
if (!$from || !$to || !$amount) {
    sendError(1000, 'Required parameter is missing', $format);
}

// Check if the "from" and "to" parameters are valid currencies
$validCurrencies = ['AUD', 'BRL', 'CAD', 'CHF', 'CNY', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'INR', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'RUB', 'SEK', 'SGD', 'THB', 'TRY', 'USD', 'ZAR'];
if (!in_array($from, $validCurrencies) || !in_array($to, $validCurrencies)) {
    sendError(1200, 'Currency type not recognized', $format);
}

// Validate the amount (should be a decimal number)
if (!is_numeric($amount) || strpos($amount, '.') === false) {
    sendError(1300, 'Currency amount must be a decimal number', $format);
}

// Validate the format (should be json or xml)
if ($format !== 'json' && $format !== 'xml') {
    sendError(1400, 'Format must be xml or json', $format);
}

// Check for unrecognized parameters
$validParams = ['from', 'to', 'amnt', 'format', 'action', 'cur'];
foreach ($_GET as $key => $value) {
    if (!in_array($key, $validParams)) {
        sendError(1100, 'Parameter not recognized', $format);
    }
}

// Handle PUT request to update currency information
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $action === 'put') {
    parse_str(file_get_contents("php://input"), $putData); // Capture data from the PUT body

    $currency = $putData['cur'] ?? null;
    $newRate = $putData['rate'] ?? null;

    if (!$currency || !$newRate) {
        sendError(2100, 'Currency code in wrong format or is missing', $format);
    }

    if ($currency === 'USD') { // Assuming USD is the base currency and cannot be updated
        sendError(2400, 'Cannot update base currency', $format);
    }

    try {
        // Fetch the current exchange rate for the currency from the API
        $apiRate = $converter->getConversionRate($currency, 'USD'); // Example: Get the rate of the currency against USD

        // Check if the rate is available from the API
        if (!$apiRate) {
            sendError(2300, 'No rate listed for this currency', $format);
        }

        // Update the currency data in the database (excluding the rate since it's fetched from the API)
        // Assuming you are storing currency general information like code, name, and locations
        $updateQuery = "UPDATE currencies SET currency_name = ?, locations = ? WHERE currency_code = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sss", $putData['name'], $putData['locations'], $currency);
        $stmt->execute();

        // Return the response (old rate from the API and new rate from the request)
        $response = [
            'message' => 'Currency updated successfully',
            'old_rate' => $apiRate,  // This will be the current rate fetched from the API
            'new_rate' => $newRate   // New rate if provided in the PUT request
        ];

        // Return the response in the requested format (JSON or XML)
        if ($format === 'xml') {
            $xml = new SimpleXMLElement('<response/>');
            $xml->addChild('message', $response['message']);
            $xml->addChild('old_rate', $response['old_rate']);
            $xml->addChild('new_rate', $response['new_rate']);
            header('Content-Type: application/xml');
            echo $xml->asXML();
        } else {
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    } catch (Exception $e) {
        sendError(2500, 'Error in service', $format);
    }
    exit;
}

// Handle POST request to add new currency
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'post') {
    $postData = json_decode(file_get_contents("php://input"), true); // Parse POST data

    $currencyCode = $postData['cur'] ?? null;
    $currencyName = $postData['name'] ?? null;
    $locations = $postData['locations'] ?? null;
    $rate = $postData['rate'] ?? null;

    if (!$currencyCode || !$currencyName || !$locations || !$rate) {
        sendError(2100, 'Currency code in wrong format or is missing', $format);
    }

    try {
        // Add the new currency to the database (excluding the rate since it's fetched from the API)
        $addQuery = "INSERT INTO currencies (currency_code, currency_name, locations) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($addQuery);
        $stmt->bind_param("sss", $currencyCode, $currencyName, $locations);
        $stmt->execute();

        // Optionally, fetch the rate from the API
        $apiRate = $converter->getConversionRate($currencyCode, 'USD'); // Example: Get the rate of the new currency against USD

        if (!$apiRate) {
            sendError(2300, 'No rate listed for this currency', $format);
        }

        // Return the response
        $response = ['message' => 'Currency added successfully', 'rate' => $apiRate];

        // Return the response in the requested format (JSON or XML)
        if ($format === 'xml') {
            $xml = new SimpleXMLElement('<response/>');
            $xml->addChild('message', $response['message']);
            $xml->addChild('rate', $response['rate']);
            header('Content-Type: application/xml');
            echo $xml->asXML();
        } else {
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    } catch (Exception $e) {
        sendError(2500, 'Error in service', $format);
    }
    exit;
}

// Handle DELETE request to delete currency
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'del') {
    parse_str(file_get_contents("php://input"), $delData); // Capture data from the DELETE body

    $currencyCode = $delData['cur'] ?? null;

    if (!$currencyCode) {
        sendError(2100, 'Currency code in wrong format or is missing', $format);
    }

    try {
        // Delete the currency from the database
        $deleteQuery = "DELETE FROM currencies WHERE currency_code = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("s", $currencyCode);
        $stmt->execute();

        // Return the response
        $response = ['message' => 'Currency deleted successfully'];

        // Return the response in the requested format (JSON or XML)
        if ($format === 'xml') {
            $xml = new SimpleXMLElement('<response/>');
            $xml->addChild('message', $response['message']);
            header('Content-Type: application/xml');
            echo $xml->asXML();
        } else {
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    } catch (Exception $e) {
        sendError(2500, 'Error in service', $format);
    }
    exit;
}

// If none of the above actions are triggered, perform the conversion
try {
    // Perform the conversion
    $result = $converter->convert($from, $to, $amount);

    if (!$result) {
        sendError(1500, 'Error in service', $format);
    }

    // Output in the requested format (JSON or XML)
    if ($format === 'xml') {
        header('Content-Type: application/xml');
        echo $converter->formatXML($result);
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
} catch (Exception $e) {
    sendError(1500, 'Error in service', $format);
}
?>
