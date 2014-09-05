<?php
// The URL could be different (plus this is for a specific McDonalds)
$url = isset($argv[1]) ? $argv[1] : "http://nmd.mcd10331.det.wayport.net/index.adp?MacAddr=24%3aFD%3a52%3aB0%3a79%3aD9&IpAddr=192%2e168%2e6%2e128&Ip6Addr=&vsgpId=&vsgId=69865&UserAgent=&ProxyHost=&TunnelIfId=152015&VlanId=21";

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
