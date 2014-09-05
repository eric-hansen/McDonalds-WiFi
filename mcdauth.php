<?php
/**
 * Try connecting to Google.  If the HTTP code != 200, we assume that McDonald's WiFi needs (re)validated.
 * So, we then fetch the redirect URL via the Location HTTP header, and then work on that instead of requiring
 * it to be manually passed.
 */
$ch = curl_init("http://www.google.com");

curl_setopt_array(
    $ch,
    array( CURLOPT_HEADER => 1, CURLOPT_RETURNTRANSFER => 1)
);

$resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if($http_code != 302){
    die("There is no need to revalidate WiFi session.\n");
}

list($headers, $body) = explode("\r\n\r\n", $resp);
$header_lines = explode("\r\n", $headers);

$location = explode(": ", $header_lines[1]);
$url = $location[1];

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

/**
 * TODO: Replace "mcd10331.det" with whatever the user is trying to connect to.
 */
$ch = curl_init("http://nmd.mcd10331.det.wayport.net/add-ins/mcd2013/mcd_cp_redir.adp");

curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => $opts
));

$post = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if($http_code == 200)
    echo("Successfully validated McDonalds WiFi session." . PHP_EOL);
else
    echo("Unable to validate McDonalds WiFi session.  Please try running again." . PHP_EOL);
?>
