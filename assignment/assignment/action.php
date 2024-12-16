<?php

# params array
define('PARAMS', array(
    'to',
    'from',
    'amnt',
    'format'
));
# error_hash to hold error numbers and messages
define('ERROR_HASH', array(
    1000 => 'Required parameter is missing',
    1100 => 'Parameter not recognized',
    1200 => 'Currency type not recognized',
    1300 => 'Currency amount must be a whole number greater than 0',
    1400 => 'Format must be xml or json',
    1500 => 'Error in Service',
    2000 => 'Action not recognized or is missing',
    2100 => 'Currency code in wrong format or is missing',
    2200 => 'Currency code not found for update',
    2300 => 'No rate listed for currency',
    2400 => 'Cannot update base currency',
    2500 => 'Error in service',
    2600 => 'currency already in service'
));


# gets JSON response from AJAX stores it in $array
$json = $_POST['json'];
$json = json_decode($json);
$array = (array)$json;

# variables $currency and $action set from array who's values were passed trough from AJAX
$currency = $array['currency'];


# loads the rates.xml file
$xml = simplexml_load_file('rates.xml');

# grabs live the live currencies codes
$rates2 = $xml->xpath("//rate/@code");

# creates $codes array to store all currency codes in the fixer xml file
foreach ($rates2 as $key => $val)
{
    $codes2[] = (string)$val;
}

$rates = $xml->xpath("//rate[@live='0']/@code");
# creates $codes array to store all the currency codes that are live
foreach ($rates as $key => $val)
{
    $notLivecodes[] = (string)$val;
}

$timeStamp = date('d M Y H:i', (string)$xml->xpath("/rates/@ts") [0]);

$live = $xml->xpath("//rate[@code='" . $currency . "']/@live");
$rate2 = $xml->xpath("//rate[@code='" . $currency . "']/@rate");

# load currencies.xml file into $xml variable
$xml2 = simplexml_load_file("currencies.xml");

$cntryCname = $xml2->xpath("/currencies/currency [ccode = '" . $currency . "']/cname");
$cntryInfo = $xml2->xpath("/currencies/currency [ccode = '" . $currency . "']/cntry");



if (!isset($array['action']))
{
		$action = "xxx";
    echo generate_error(2000, $action);
    exit();

} else{
	$action = $array['action'];
}


if (!isset($array['currency']))
{
    echo generate_error(2100,$action);
    exit();

} else{
	$location = $cntryInfo[0];
	$currName = $cntryCname[0];

	$preUpdate = $rate2[0]['rate'];
}

if (!isset($array['action']) && !isset($array['currency']))
{
    echo generate_error(2000,$action);
    exit();

}

if ($currency == "GBP")
{
    # if currency selected is gbp, error 2400: cannot update base currency
    echo generate_error(2400,$action);
    exit();
}

if (!in_array($currency, $codes2))
{
    # if currency selected is not in fixers list of currencies, error 2300: no rate found for currency
    echo generate_error(2300,$action);
    exit();

}

function generate_error($eno, $action)
{
    # function generates error xml responses printed on screen via ajax when sucessfully called
    # $msg = grabs description from corresponding error number. As defined
    $msg = ERROR_HASH["$eno"];

    $doc = new DOMDocument('1.0', 'UTF-8');
    # for nice formatted output
    $doc->formatOutput = true;

    $actions = $doc->createElement("action");
    $actions->setAttribute('Type', $action);
    $actions = $doc->appendChild($actions);

    # error is a child of action
    $error = $doc->createElement('error');
    $error = $actions->appendChild($error);

    # code is a child of error
    $errorCode = $doc->createElement('code', $eno);
    $errorCode = $error->appendChild($errorCode);

    # msg is a child of error
    $message = $doc->createElement('msg', $msg);
    $message = $error->appendChild($message);

    # saves for AJAX output
    echo $doc->saveXML();

}

function generate_post_success($action, $timeStamp, $currency, $location, $preUpdate, $currName, $update)
{
    # function generates the "post" xml printed on screen via ajax response when sucessfully called
    $doc = new DOMDocument('1.0', 'UTF-8');
    # for formatted output
    $doc->formatOutput = true;

    $actions = $doc->createElement("action");
    $actions->setAttribute('Type', $action);
    $actions = $doc->appendChild($actions);

    # at is a child of action tag
    $at = $doc->createElement('at', $timeStamp);
    $at = $actions->appendChild($at);

    # rate is a child of action tag
    $rate = $doc->createElement('rate', $update);
    $rate = $actions->appendChild($rate);

    # old_rate is a child of action tag
    $oldRate = $doc->createElement('old_rate', $preUpdate);
    $oldRate = $actions->appendChild($oldRate);

    # curr is a child of action tag
    $curr = $doc->createElement('curr');
    $curr = $actions->appendChild($curr);

    # code is a child of curr tag
    $code = $doc->createElement('code', $currency);
    $code = $curr->appendChild($code);

    # name is a child of curr tag
    $name = $doc->createElement('name', $currName);
    $name = $curr->appendChild($name);

    # loc is a child of curr tag
    $locations = $doc->createElement('loc', $location);
    $locations = $curr->appendChild($locations);

    # saves for AJAX output
    echo $doc->saveXML();

}

function generate_put_success($action, $timeStamp, $currency, $location, $preUpdate, $currName)
{
    # function generates the "put" xml printed on screen via ajax response when sucessfully called


    $doc = new DOMDocument('1.0', 'UTF-8');
    # for formatted output
    $doc->formatOutput = true;

    $actions = $doc->createElement("action");
    $actions->setAttribute('Type', $action);
    $actions = $doc->appendChild($actions);

    # at is a child of action tag
    $at = $doc->createElement('at', $timeStamp);
    $at = $actions->appendChild($at);

    # rate is a child of action tag
    $rate = $doc->createElement('rate', $preUpdate);
    $rate = $actions->appendChild($rate);

    # curr is a child of action tag
    $curr = $doc->createElement('curr');
    $curr = $actions->appendChild($curr);

    # code is a child of curr tag
    $code = $doc->createElement('code', $currency);
    $code = $curr->appendChild($code);

    # name is a child of curr tag
    $name = $doc->createElement('name', $currName);
    $name = $curr->appendChild($name);

    # loc is a child of curr tag
    $locations = $doc->createElement('loc', $location);
    $locations = $curr->appendChild($locations);

    # saves for AJAX output
    echo $doc->saveXML();

}

function generate_del_success($action, $timeStamp, $currency)
{
    # function generates the "del" xml printed on screen via ajax response when sucessfully called


    $doc = new DOMDocument('1.0', 'UTF-8');
    # for formatted output
    $doc->formatOutput = true;

    $actions = $doc->createElement("action");
    $actions->setAttribute('Type', $action);
    $actions = $doc->appendChild($actions);

    # at is s child of action tag
    $at = $doc->createElement('at', $timeStamp);
    $at = $actions->appendChild($at);

    # code is a child of action tag
    $code = $doc->createElement('code', $currency);
    $code = $actions->appendChild($code);

    # saves for AJAX output
    echo $doc->saveXML();
}

if (in_array($currency, $codes2) && $action == "post")
{

    # pull the rates json file
    $json_rates = file_get_contents("API KEY") or die("Error: Cannot load JSON file from fixer");
    # decode the json to a php object
    $rates = json_decode($json_rates);
    $gbp_rate = 1 / $rates
        ->rates->GBP;

    foreach ($rates->rates as $code => $rate)
    {

        if ($code == $currency)
        {


					if (in_array($currency, $notLivecodes))
					{
						# if currency is not live - cannot pdate rate
							echo generate_error(2200, $action);
							exit();

					}

            $update = ($rate * $gbp_rate);
            $rate2[0]['rate'] = $update;

            echo generate_post_success($action, $timeStamp, $currency, $location, $preUpdate, $currName, $update);

            $xml->asXML('rates.xml');

        }

    }

}

if (in_array($currency, $codes2) && $action == "put")
{

    if (!in_array($currency, $notLivecodes))
    {
        echo generate_error(2600, $action);
        exit();
    }

    # changes selected currency to live in rates.xml
    $live[0]['live'] = 1;
    # saves rates.xml
    echo generate_put_success($action, $timeStamp, $currency, $location, $preUpdate, $currName);

    $xml->asXML('rates.xml');

}

if (in_array($array['currency'], $codes2) && $action == "del")
{

    if (in_array($currency, $notLivecodes))
    {

        echo generate_error(2200,$action);
        exit();

    }

    echo generate_del_success($action, $timeStamp, $currency);
    $live[0]['live'] = 0;
    $xml->asXML('rates.xml');
}

?>