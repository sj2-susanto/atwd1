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

    // Update conversion rate in the database
    public function updateConversionRate($from, $to, $newRate)
    {
        $stmt = $this->db->prepare("INSERT INTO conversion_rates (from_currency, to_currency, rate) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rate = ?");
        $stmt->bind_param("ssds", $from, $to, $newRate, $newRate);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
?>
