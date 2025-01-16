<?php
require 'data.php'; /* Values to be modified are mainly in this file! */


// Currency rate database creation.
// function tablesCreation($database) {
    // $currencies = '
    // CREATE TABLE currencies (
        // currency_code VARCHAR(3) PRIMARY KEY,
        // currency_name TEXT NOT NULL,
        // at DATETIME NOT NULL,
        // rate REAL NOT NULL
    // );
    // ';

    // $countries = '
    // CREATE TABLE countries (
        // country_id INTEGER PRIMARY KEY,
        // country_name TEXT NOT NULL,
        // currency_code VARCHAR(3),
        // FOREIGN KEY (currency_code) REFERENCES currencies(currency_code)
    // );
    // ';

    // $database->exec($currencies);
    // $database->exec($countries);
// }


// Parsing and data cleaning from the XML source.
function parseXMLISO($xml) {
    $loadedXml = simplexml_load_file($xml)->CcyTbl;
    $encode = json_encode($loadedXml);
    return json_decode($encode, true)["CcyNtry"];
}

function parseISOToDBStructure($xml) {
    $parsedXML = parseXMLISO($xml);

    /*
     * Data is formatted to correspond with the database tables.
     * With the exception of the data for rates which is taken from the API.
     * Country names are also formatted because of occurrences of special
     * characters causing issues for database insertion.
     */
    // $countries = array_map(fn($v) => ['country_name' => preg_replace('/[^A-Za-z0-9\- ]/', '', $v['CtryNm']),
                                      // 'currency_code' => $v['Ccy']],
                           // $parsedXML);
	$countries = array_map(fn($v) => ['country_name' => $v['CtryNm']),
                                      'currency_code' => $v['Ccy']],
                           $parsedXML);					   

    $currency_codes =
        array_unique(array_filter(array_map(fn($v) => $v['Ccy'], $parsedXML)));
    $currencies = array_intersect_key($parsedXML, $currency_codes);
    $currencies = array_map(fn($v) => ['currency_code' => $v['Ccy'],
                                       'currency_name' => $v['CcyNm']],
                            $currencies);

    return ['currencies' => $currencies,
            'countries' => $countries];
}


// The full dataset for initial database insertions from the ISO XML and Currency API.
function completeDataInitialisation($tables, $api) {
    $rates = $api['rates'];

    $currencies_raw = $tables['currencies'];
$currencies = array_map(fn($v) => $v += ['at' => new DateTime("@{$api['timestamp']}"),
                                             'rate' => $rates[$v['currency_code']]],
                            $currencies_raw);
	$currencies = array_column($currencies, NULL, 'currency_code');
	$currencies = array_map(fn($v) => array_diff_key($v, array_flip((array) ['currency_code'])), $currencies);
    /* $currencies = array_map(fn($v) => $v += ['at' => 1519296206,
                                             'rate' => 1],
                            $currencies_raw);
 */
    return array_replace($tables, ['currencies' => $currencies]);
}


// Insert the data to the database tables.
// function tablesInsertion($database, $tables) {
    // $database->exec('BEGIN');

    // foreach ($tables['currencies'] as $i => $v) {
        // $insert = "
        // INSERT INTO currencies(currency_code, currency_name, at, rate)
        // VALUES (
            // '{$v['currency_code']}',
            // '{$v['currency_name']}',
            // datetime('{$v['at']}', 'unixepoch', 'localtime'),
            // '{$v['rate']}'
        // )
        // ";

        // $database->query($insert);
    // }

    // foreach ($tables['countries'] as $i => $v) {
        // $insert = "
        // INSERT INTO countries(country_id, country_name, currency_code)
        // VALUES (
            // '{$i}',
            // '{$v['country_name']}',
            // '{$v['currency_code']}'
        // )
        // ";

        // $database->query($insert);
    // }

    // $database->exec('COMMIT');
// }


// Check if tables exist.
// function tablesExist($database) {
    // $check = fn($t) => "
    // SELECT name FROM sqlite_master
    // WHERE type='table'
    // AND name='$t'
    // ";

    // $check_currencies = $database->querySingle($check('currencies'));
    // $check_countries = $database->querySingle($check('countries'));

    // return !is_null($check_currencies) && !is_null($check_countries);
// }


// Main functionality.
// if (!tablesExist(DB)) {
    // echo "Database tables not found...\n";

    // $data = @parseISOToDBStructure(XML_ISO); // Suppress NULL warnings.
    // $api = fetchCurrencyApi($API_SRC, $BASE);
    ////$api = 'aaa';
    // $data = completeDataInitialisation($data, $api);

    // echo "Initialising tables from scratch...\n";

    // tablesCreation(DB);
    // tablesInsertion(DB, $data);

    // echo "Tables initialisation successful!\n";
// } else {
    // echo "Database tables found!\n";
// }
if (!file_get_contents(DB_FILE)) {
	echo "JSON file not found...\n";
	
	$data = @parseISOToDBStructure(XML_ISO); // Suppress NULL warnings.
    $api = fetchCurrencyApi($API_SRC, $BASE);
	$data = completeDataInitialisation($data, $api);
	$data_json = json_encode($data);
	
	echo "Initialising JSON data from scratch...\n";
	
	file_put_contents(DB_FILE, $data_json);
} else {
	echo "JSON file found!\n";
}