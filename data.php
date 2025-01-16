<?php
/*
 * Greetings Sammy,
 * Before you start your journey, here are some notes for you:
 * - Modify the lines containing comments prefixed with '#'.
 * - Delete this comment after you completed your journey.
 * Now you may begin...
 */


// Set the base for currency rate.
$BASE = 'EUR'; # EUR used as a sample, you better keep it as is

// Set API source.
$api_url = 'https://data.fixer.io/api/latest?access_key=';
$api_key = '5fa39b1095f9371818a8313dc4ca2503'; # Put API key in here
$API_SRC = "$api_url$api_key";

// Fetch API from the given source with the according base currency.
function fetchCurrencyAPI($src, $base, $symbols = NULL) {
    /* Symbols are used to limit the output to only the listed currencies */
    $full_src = is_null($symbols) ?
                    "$src&base=$base" :
                    "$src&base=$base&symbols=$symbols";
    $response = file_get_contents($full_src);
    $data = json_decode($response, true);
    return $data;
}

// Set ISO 4217 currency codes dataset source
const XML_ISO = 'list-one.xml';

// Set currency rate database source
/*
 * An SQL database is used because it involves the mutability of creation,
 * modification, and deletion of data. Whereas XML is more suitable for
 * immutability (which this data is not).
 *
 * SQLite3 will be used due to its simplicity of usage especially for
 * microservices.
 */
//const DB = new SQLite3('currency-rates.sqlite');
//DB->enableExceptions(true);


const DB_FILE = 'currency-rates.json'; 
