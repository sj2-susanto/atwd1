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
//require 'currency-mod.php'; // for PUT, POST, DELETE functionalities

// Load the JSON data
//$jsonFilePath = 'currency-rates.json'; // Update this path if needed
$jsonData = file_get_contents(DB_FILE);
$exchangeData = json_decode($jsonData, true);

// Error_hash to hold error numbers and messages
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
	2500 => 'Error in service'));
	
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
    // Ensure it's a GET request
    // if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        // header('Content-Type: application/json', true, 405);
        // echo json_encode(['error' => 'Method not allowed. Use GET.']);
        // exit();
    // }
	$currencies = $exchangeData['currencies'] ?? [];
	
    $action = strtolower($_GET['action'] ?? '');
	$from = strtoupper($_GET['from']);
	//echo $action;
	if ($action == 'del') {
		//echo "aaa";
		//$cur = strtoupper($_GET['cur'] ?? '');
		header('Content-Type: application/json; charset=utf-8');
        
        if (!array_key_exists($from, $exchangeData['currencies'])) {
            echo json_encode(['error' => ERROR_HASH[2200]]);
            exit();
        }
        
        unset($exchangeData['currencies'][$from]);
		//$exchangeData['countries'] = array_filter($exchangeData['countries'], fn($v) => $v['currency_code'] == $from);
        file_put_contents(DB_FILE, json_encode($exchangeData, JSON_PRETTY_PRINT));
        
        echo json_encode(['message' => "Currency '$from' deleted successfully."]);
        exit();
	} else {
		// Get query parameters
		$to = strtoupper($_GET['to']);
		$amount = (float)($_GET['amnt']);
		$format = strtolower($_GET['format'] ?? 'xml');

		
		// Validate error 1000
		if (!$from || !$to || !$amount) {
			throw new Exception(ERROR_HASH[1000], 1000);
		}
		// Validate error 1100
		$validParams = ['from', 'to', 'amnt', 'format'];
		foreach ($_GET as $key => $value) {
		if (!in_array($key, $validParams)) {
			throw new Exception(ERROR_HASH[1100], 1100);
		}
		}
		// Validate error 1200
		if (!isset($currencies[$from]['rate']) || !isset($currencies[$to]['rate'])) {
			throw new Exception(ERROR_HASH[1200], 1200);
		}
		// Validate error 1300
		if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
			throw new Exception(ERROR_HASH[1300], 1300);
		}
		// Validate error 1400
		if (!in_array($format, ['json', 'xml'])) {
			throw new Exception(ERROR_HASH[1400], 1400);
		}

		// Perform conversion
		$fromRate = $currencies[$from]['rate'];
		$toRate = $currencies[$to]['rate'];
		$convertedAmount = ($amount / $fromRate) * $toRate;
		
		// Extract dates
		$fromDate = $currencies[$from]['at']['date'] ?? 'Unknown date';
		
		// Countries
		$countries = $exchangeData['countries'] ?? [];
		
		// Functionality to get location from a currency code.
		function getLoc($code, $countries) {
			$gl = array_filter($countries, fn($v) => $v['currency_code'] == $code);
			$gl = array_map(fn($v) => $v['country_name'], $gl);
			return implode(', ', $gl);
		}

		// Prepare response
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
				'loc' =>  getLoc($to, $countries),
				'amnt' => round($convertedAmount, 2)
			]
			
		];
		//$response = ['conv' => $response];

		// Output response in the requested format
		if ($format === 'json') {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($response);
		} else {
			header('Content-Type: application/xml; charset=utf-8');
			$xmlData = new SimpleXMLElement('<response/>');
			arrayToXml($response, $xmlData);
			echo $xmlData->asXML();
		}

		// End execution to avoid unintended HTML output
		exit();
	}
    // Validate error 1500
	throw new Exception(ERROR_HASH[1500], 1500);
} catch (Exception $e) {
    // Handle errors
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

    // End execution to avoid unintended HTML output
    exit();
}

// Clean output buffer
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
