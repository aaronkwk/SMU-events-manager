<?php
require_once 'config.php';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => SENDBIRD_API_HOST . '/v3/applications',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Api-Token: ' . SENDBIRD_API_TOKEN],
    CURLOPT_TIMEOUT => 30,
]);

$resp = curl_exec($ch);
$errno = curl_errno($ch);
$error = curl_error($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "cURL errno: $errno\n";
echo "cURL error: $error\n";
echo "HTTP code: $http\n";
echo "Response: $resp\n";
?>