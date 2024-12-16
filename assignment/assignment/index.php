<?php
@date_default_timezone_set("GMT");

# params array
define('PARAMS', array('to', 'from', 'amnt', 'format'));

# error_hash to hold error numbers and messages
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

if (!isset($_GET['format']) || empty($_GET['format'])) {
	$_GET['format'] = 'xml';
}

# ensure PARAM values match the keys in $GET
if (count(array_intersect(PARAMS, array_keys($_GET))) < 4) {
    echo generate_error(1000, $_GET['format']);
    exit();
}

# ensure no extra params
if (count($_GET) > 4) {
	echo generate_error(1100, $_GET['format']);
	exit();
}

# validate parameter values

# load the rates file as a simple xml object
$xml=simplexml_load_file('rates.xml');

# xpath the codes of the rates which are live
$rates = $xml->xpath("//rate[@live='1']/@code");

# create a php array of these codes
foreach ($rates as $key=>$val) {$codes[] =(string) $val;}

# $to and $from are not recognized currencies
if (!in_array($_GET['to'], $codes) || !in_array($_GET['from'], $codes)) {
    echo generate_error(1200, $_GET['format']);
	exit;
}

# $amnt is not a two digit decimal value (can be integer)
if (!preg_match('/^\d+(\.\d{1,2})?$/', $_GET['amnt'])) {
	echo generate_error(1300, $_GET['format']);
	exit;
}

# set a constant array holding format vals
define('FRMTS', array('xml', 'json'));

# check for allowed format values
if (!in_array( $_GET['format'], FRMTS)) {
	echo generate_error(1400);
	exit;
}

# do coversion

# get the to and from rates
$fr = $xml->xpath("//rate[@code='" . $_GET['from'] . "']/@rate")[0]['rate'];
$tr = $xml->xpath("//rate[@code='" . $_GET['to'] . "']/@rate")[0]['rate'];

# if to and from are the same - set rate to 1.00
if ($_GET['from']==$_GET['to']) {
	$rate = 1.00;
	$conv =  $_GET['amnt'];
}
else {
	# calculate relative conversion rate
	$rate = floatval($tr) / floatval($fr);

	# calculate the conversion
	$conv = $rate * $_GET['amnt'];
}

# build an array to send to the response function

$curr = simplexml_load_file('currencies.xml');

#get the timestamp (ts) from the rates file & format it
$resp['date_time'] = date('d M Y H:i', (string) $xml->xpath("/rates/@ts")[0]);

# get the rate
$resp['rate'] = $rate;

$resp['from_code'] = $_GET['from'];
$resp['from_curr'] = (string) $curr->xpath("//currency/cname[../ccode='". $_GET['from'] . "']")[0];
$resp['from_loc'] = (string) $curr->xpath("//currency/cntry[../ccode='". $_GET['from'] . "']")[0];
$resp['from_amnt'] = $_GET['amnt'];

$resp['to_code'] = $_GET['to'];
$resp['to_curr'] = (string) $curr->xpath("//currency/cname[../ccode='". $_GET['to'] . "']")[0];
$resp['to_loc'] = (string) $curr->xpath("//currency/cntry[../ccode='". $_GET['to'] . "']")[0];
$resp['to_amnt'] = $conv;

# make the response xml
$response = response_xml($resp);

# print the xml header and content
if ($_GET['format']=='xml') {
	header('Content-Type: text/xml');
	$dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadXML ('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $response);
    $dom->formatOutput = true;
	echo (string) $dom->saveXML();
}
else {
	$json = simplexml_load_string('<conv>'.$response.'</conv>');
	header('Content-Type: application/json');
	echo json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
exit;

# function to return xml or json errors
function generate_error($eno, $format='xml') {

	$msg = ERROR_HASH["$eno"];

	if ($format=='json') {
		$json = array('conv' => array("code" => "$eno", "msg" => "$msg"));

		$out = header('Content-Type: application/json');
		$out .= json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

	}
	else {
		$xml =  '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= '<conv><error>';
		$xml .= '<code>' . $eno . '</code>';
		$xml .= '<msg>' . $msg . '</msg>';
		$xml .= '</error></conv>';

		$out = header('Content-type: text/xml');
		$out .= $xml;
	}
	return $out;
}

function response_xml (&$resp) {

	$resp['from_loc'] = trim(preg_replace('/\s+/', ' ', $resp['from_loc']));
	$resp['to_loc'] = trim(preg_replace('/\s+/', ' ', $resp['to_loc']));

	$resp_xml = <<<__xml
     <conv>
       <at>{$resp['date_time']}</at>
       <rate>{$resp['rate']}</rate>
       <from>
          <code>{$resp['from_code']}</code>
          <curr>{$resp['from_curr']}</curr>
          <loc>{$resp['from_loc']}</loc>
          <amnt>{$resp['from_amnt']}</amnt>
       </from>
       <to>
          <code>{$resp['to_code']}</code>
          <curr>{$resp['to_curr']}</curr>
          <loc>{$resp['to_loc']}</loc>
          <amnt>{$resp['to_amnt']}</amnt>
       </to>
     </conv>
__xml;

return $resp_xml;

}

?>