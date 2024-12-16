INDEX.PHP

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

try {
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

CURRENCYCONVERTER.PHP

<?php

class CurrencyConverter
{
    private $db;

    public function __construct()
    {
        // Connect to the database
        $this->db = new mysqli('localhost', 'root', '', 'currency_converter');
        if ($this->db->connect_error) {
            die('Database connection failed: ' . $this->db->connect_error);
        }
    }

    // Fetch currency details from the database
    public function getCurrencyDetails($currencyCode)
    {
        $stmt = $this->db->prepare("SELECT currency_name, locations FROM currencies WHERE code = ?");
        $stmt->bind_param("s", $currencyCode);
        $stmt->execute();
        $stmt->bind_result($currencyName, $locations);
        $stmt->fetch();
        $stmt->close();

        if ($currencyName) {
            return [
                'currency_name' => $currencyName,
                'locations' => explode(',', $locations)
            ];
        }
        return null;
    }

    // Fetch currency conversion rate from freecurrencyapi.com
    public function getConversionRate($from, $to)
    {
        $apiKey = 'fca_live_2GLHNKOD8uNRk5F4mNQhXbr9lLXlIOf6jrSNhXxY'; // Replace with your API key
        $url = "https://api.freecurrencyapi.com/v1/latest?apikey=$apiKey&base_currency=$from";
        
        // Fetch the JSON response from the API
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (isset($data['data'][$to])) {
            return $data['data'][$to];
        }
        return null;
    }

    // Convert currency and format the result
    public function convert($from, $to, $amount)
    {
        $conversionRate = $this->getConversionRate($from, $to);

        if (!$conversionRate) {
            return null;
        }

        // Get currency details from the database
        $fromDetails = $this->getCurrencyDetails($from);
        $toDetails = $this->getCurrencyDetails($to);

        if (!$fromDetails || !$toDetails) {
            return null;
        }

        $convertedAmount = $amount * $conversionRate;

        // Return the conversion details
        return [
            'from' => [
                'code' => $from,
                'curr' => $fromDetails['currency_name'],
                'loc' => implode(', ', $fromDetails['locations']),
                'amnt' => $amount
            ],
            'to' => [
                'code' => $to,
                'curr' => $toDetails['currency_name'],
                'loc' => implode(', ', $toDetails['locations']),
                'amnt' => $convertedAmount
            ],
            'rate' => $conversionRate,
            'at' => date('d M Y H:i')
        ];
    }

    // Format the conversion result as XML
    public function formatXML($data)
    {
        if (!$data) {
            return null;
        }

        $xml = new SimpleXMLElement('<conv/>');
        $xml->addChild('at', $data['at']);
        $xml->addChild('rate', $data['rate']);

        $from = $xml->addChild('from');
        $from->addChild('code', $data['from']['code']);
        $from->addChild('curr', $data['from']['curr']);
        $from->addChild('loc', $data['from']['loc']);
        $from->addChild('amnt', $data['from']['amnt']);

        $to = $xml->addChild('to');
        $to->addChild('code', $data['to']['code']);
        $to->addChild('curr', $data['to']['curr']);
        $to->addChild('loc', $data['to']['loc']);
        $to->addChild('amnt', $data['to']['amnt']);

        return $xml->asXML();
    }
}
?>

ERROR 1000 - http://localhost/atwd1/assignment/?from=USD&to=EUR
ERROR 1100 - http://localhost/atwd1/assignment/?from=USD&to=EUR&amnt=100&format=json&extra=xyz
ERROR 1200 - http://localhost/atwd1/assignment/?from=USD&to=XYZ&amnt=100&format=json
ERROR 1300 - http://localhost/atwd1/assignment/?from=USD&to=EUR&amnt=100&format=json
ERROR 1400 - http://localhost/atwd1/assignment/?from=USD&to=EUR&amnt=100.50&format=html
ERROR 1500 - http://localhost/atwd1/assignment/?from=USD&to=EUR&amnt=100.50&format=json


