<?php

@date_default_timezone_set("GMT");



if (file_exists('rates.xml')) {
    copy('rates.xml', 'rates'.'_'.time().'.xml');
    $xml = simplexml_load_file('rates.xml');
    foreach($xml->rate as $r) {
        if ((string) $r['live'] == '1') {
            $live[] = (string) $r['code'];
        }
    }
}

else {
# currency code array
    $live = array(
        'AUD', 'BRL', 'CAD','CHF',
        'CNY', 'DKK', 'EUR','GBP',
        'HKD', 'HUF', 'INR','JPY',
        'MXN', 'MYR', 'NOK','NZD',
        'PHP', 'RUB', 'SEK','SGD',
        'THB', 'TRY', 'USD','ZAR'
    );
}

# pull the rates json file 
$json_rates = file_get_contents("API KEY")
			  or die("Error: Cannot load JSON file from fixer");

#decode the json to a php object
$rates = json_decode($json_rates);

# calculate the GBP ratio
$gbp_rate = 1/$rates->rates->GBP;

# start and initialize the writer
$writer = new XMLWriter();
$writer->openURI('rates.xml');
$writer->startDocument("1.0", "UTF-8");
$writer->startElement("rates");
$writer->writeAttribute('base', 'GBP');
$writer->writeAttribute('ts', $rates->timestamp);
foreach ($rates->rates as $code=>$rate) {
	$writer->startElement("rate");
    $writer->writeAttribute('code', $code);

    if ($code=='GBP') {
        $writer->writeAttribute('rate', '1.00');
    }
    else {
        $writer->writeAttribute('rate', $rate * $gbp_rate);
    }

    if (in_array($code, $live)) {
        $writer->writeAttribute('live', '1');
    }
    else {
        $writer->writeAttribute('live', '0');
    }
    $writer->endElement();
}
$writer->endElement();
$writer->endDocument();
$writer->flush();
echo "All done ....!";
exit;
?>