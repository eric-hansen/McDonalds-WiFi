<?php
/**
 * Try connecting to Google.  If the HTTP code != 200, we assume that McDonald's WiFi needs (re)validated.
 * So, we then fetch the redirect URL via the Location HTTP header, and then work on that instead of requiring
 * it to be manually passed.
 */
require_once "vendor/autoload.php";

$notify = new EricHansen\Notifier\Notifier();

$notify_opts = array(
    "title" => "McDonalds WiFi Authentication",
    "msg" => ""
);

$ch = curl_init("http://www.google.com");

curl_setopt_array(
    $ch,
    array( CURLOPT_HEADER => 1, CURLOPT_RETURNTRANSFER => 1)
);

$resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if($http_code != 302){
    $notify_opts['msg'] = "There is no need to revalidate WiFi session.";
    $notify->Notify($notify_opts);
    exit(1);
}

list($headers, $body) = explode("\r\n\r\n", $resp);
$header_lines = explode("\r\n", $headers);

$location = explode(": ", $header_lines[1]);
$url = $location[1];

$url_parse = parse_url($url);

// This is used for the final step
$mcd_url = $url_parse['scheme'] . "://" . $url_parse['host'];

$ch = curl_init($url);

// Force cURL to use GET and return the body since we need to parse the HTML.
curl_setopt_array(
    $ch,
    array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HTTPGET => 1
    )
);

$html = curl_exec($ch);

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if($http_code != 200){
    die("Unable to retrieve HTML for McDonald's WiFi.  Please open up your browser and provide the URL." . PHP_EOL);
}

$doc = new DOMDocument();
@$doc->loadHTML($html);

// Get all the inputs.  Since the site's HTML is (overly) simplistic, the only form is for establishing a new session
$inputs = $doc->getElementsByTagName("input");

// Will be used to POST the information to establish a new connection
$opts = array();

foreach($inputs as $input){
    // Store the attributes in a var to make the code slightly more readable
    $attrs = $input->attributes;

    $opts[$attrs->getNamedItem("name")->nodeValue] = $attrs->getNamedItem("value")->nodeValue;
}

$ch = curl_init($mcd_url . "/add-ins/mcd2013/mcd_cp_redir.adp");

curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => $opts
));

$post = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if($http_code == 200)
    $notify_opts["msg"] = "Successfully validated McDonalds WiFi session.";
else
    $notify_opts["msg"] = "Unable to validate McDonalds WiFi session.  Please try running again.";

$notify->Notify($notify_opts);
?>
