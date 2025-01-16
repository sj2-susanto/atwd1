<?php
/*
 * index.php
 * 
 * Copyright 2024 user <user>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 */

require 'data.php'; // Include if needed; ensure it doesn't output anything

// Load the JSON data
$jsonData = file_get_contents(DB_FILE);
$exchangeData = json_decode($jsonData, true);

define ('ERROR_HASH', array(
    1000 => 'Required parameter is missing',
    1100 => 'Parameter not recognized',
    1200 => 'Currency type not recognized',
    1300 => 'Currency amount must be a decimal number',
    1400 => 'Format must be xml or json',
    1500 => 'Error in Service',
    2000 => 'Action not recognized or is missing',
    2100 => 'Currency code in wrong format or is missing',
    2200 => 'Currency code not found for update',
    2300 => 'No rate listed for currency',
    2400 => 'Cannot update base currency',
    2500 => 'Error in service'
));

// Helper function to convert an array to XML
function arrayToXml($data, &$xmlData) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $subnode = $xmlData->addChild($key);
            arrayToXml($value, $subnode);
        } else {
            $xmlData->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

// Start output buffering to prevent accidental output
ob_start();

try {
    $currencies = $exchangeData['currencies'] ?? [];
    $action = strtolower($_GET['action'] ?? '');
    $from = strtoupper($_GET['from']);

    if ($action == 'del') {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!array_key_exists($from, $exchangeData['currencies'])) {
            echo json_encode(['error' => ERROR_HASH[2200]]);
            exit();
        }
        
        unset($exchangeData['currencies'][$from]);
        file_put_contents(DB_FILE, json_encode($exchangeData, JSON_PRETTY_PRINT));
        
        echo json_encode(['message' => "Currency '$from' deleted successfully."]);
        exit();
		
    } elseif ($action == 'put') {
        header('Content-Type: application/json; charset=utf-8');

        $rate = $_GET['rate'] ?? null;
        $currencyName = $_GET['currency_name'] ?? null;

        if (!$from || !$rate || !$currencyName) {
            echo json_encode(['error' => ERROR_HASH[1000]]);
            exit();
        }

        if (!is_numeric($rate) || $rate <= 0) {
            echo json_encode(['error' => ERROR_HASH[1300]]);
            exit();
        }

        $exchangeData['currencies'][$from] = [
            'rate' => (float)$rate,
            'currency_name' => $currencyName,
            'at' => ['date' => date('Y-m-d H:i:s')]
        ];

        file_put_contents(DB_FILE, json_encode($exchangeData, JSON_PRETTY_PRINT));

        echo json_encode(['message' => "Currency '$from' updated/added successfully."]);
        exit();
		
	} elseif ($action == 'post') {
        header('Content-Type: application/json; charset=utf-8');

        $newCurrency = json_decode(file_get_contents('php://input'), true);

        if (!isset($newCurrency['code'], $newCurrency['rate'], $newCurrency['currency_name'])) {
            echo json_encode(['error' => ERROR_HASH[1000]]);
            exit();
        }

        $code = strtoupper($newCurrency['code']);
        $rate = $newCurrency['rate'];
        $currencyName = $newCurrency['currency_name'];

        if (!is_numeric($rate) || $rate <= 0) {
            echo json_encode(['error' => 'Invalid rate value.']);
            exit();
        }

        $exchangeData['currencies'][$code] = [
            'rate' => (float)$rate,
            'currency_name' => $currencyName,
            'at' => ['date' => date('Y-m-d H:i:s')]
        ];

        file_put_contents(DB_FILE, json_encode($exchangeData, JSON_PRETTY_PRINT));

        echo json_encode(['message' => "Currency '$code' added successfully."]);
        exit();	
    } else {
        $to = strtoupper($_GET['to']);
        $amount = (float)($_GET['amnt']);
        $format = strtolower($_GET['format'] ?? 'xml');

        if (!$from || !$to || !$amount) {
            throw new Exception(ERROR_HASH[1000], 1000);
        }

        $validParams = ['from', 'to', 'amnt', 'format', 'action'];
        foreach ($_GET as $key => $value) {
            if (!in_array($key, $validParams)) {
                throw new Exception(ERROR_HASH[1100], 1100);
            }
        }

        if (!isset($currencies[$from]['rate']) || !isset($currencies[$to]['rate'])) {
            throw new Exception(ERROR_HASH[1200], 1200);
        }

        if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            throw new Exception(ERROR_HASH[1300], 1300);
        }

        if (!in_array($format, ['json', 'xml'])) {
            throw new Exception(ERROR_HASH[1400], 1400);
        }

        $fromRate = $currencies[$from]['rate'];
        $toRate = $currencies[$to]['rate'];
        $convertedAmount = ($amount / $fromRate) * $toRate;

        $fromDate = $currencies[$from]['at']['date'] ?? 'Unknown date';

        $countries = $exchangeData['countries'] ?? [];

        function getLoc($code, $countries) {
            $gl = array_filter($countries, fn($v) => $v['currency_code'] == $code);
            $gl = array_map(fn($v) => $v['country_name'], $gl);
            return implode(', ', $gl);
        }

        $response = [
            'at' => $fromDate,
            'rate' => round($toRate / $fromRate, 6),
            'from' => [
                'code' => $from,
                'curr' => $currencies[$from]['currency_name'],
                'loc' => getLoc($from, $countries),
                'amnt' => $amount
            ],
            'to' => [
                'code' => $to,
                'curr' => $currencies[$to]['currency_name'],
                'loc' => getLoc($to, $countries),
                'amnt' => round($convertedAmount, 2)
            ]
        ];

        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
        } else {
            header('Content-Type: application/xml; charset=utf-8');
            $xmlData = new SimpleXMLElement('<response/>');
            arrayToXml($response, $xmlData);
            echo $xmlData->asXML();
        }

        exit();
    }

    throw new Exception(ERROR_HASH[1500], 1500);
} catch (Exception $e) {
    $errorResponse = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
    $format = strtolower($_GET['format'] ?? 'json');
    if ($format === 'xml') {
        header('Content-Type: application/xml; charset=utf-8');
        $xmlData = new SimpleXMLElement('<response/>');
        arrayToXml($errorResponse, $xmlData);
        echo $xmlData->asXML();
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($errorResponse);
    }
    exit();
}

ob_end_flush();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <title>untitled</title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="generator" content="Geany 2.0" />
</head>

<body>
     <h1>HELLO SAMMY</h1>
</body>

</html>
