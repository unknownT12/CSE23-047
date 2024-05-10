<?php
// PayPal API credentials
$clientId = 'YOUR_PAYPAL_CLIENT_ID';
$clientSecret = 'YOUR_PAYPAL_CLIENT_SECRET';
$paymentMethod = 'paypal'; // or 'credit_card' for direct credit card payments

// Set API endpoint and authentication
$apiEndpoint = 'https://api-m.sandbox.paypal.com/v2/'; // sandbox environment
$authToken = getAuthToken($clientId, $clientSecret);

// Function to get an access token
function getAuthToken($clientId, $clientSecret) {
  $authTokenUrl = $apiEndpoint . 'oauth2/token';
  $headers = array(
    'Content-Type: application/x-www-form-urlencoded',
    'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
  );
  $data = array('grant_type' => 'client_credentials');
  $response = json_decode(curlRequest($authTokenUrl, $headers, $data), true);
  return $response['access_token'];
}

// Function to make a CURL request
function curlRequest($url, $headers, $data) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $paymentData = array(
    'intent' => 'sale',
    'payer' => array(
      'payment_method' => $paymentMethod
    ),
    'redirect_urls' => array(
      'return_url' => 'https://example.com/success', // redirect after payment
      'cancel_url' => 'https://example.com/cancel' // redirect on cancel
    ),
    'transactions' => array(
      array(
        'amount' => array(
          'currency' => 'USD', // adjust currency as needed
          'value' => $_POST['amount'] // get amount from form
        ),
        'description' => 'Payment for ' . $_POST['name'] // get name from form
      )
    )
  );

  $paymentUrl = $apiEndpoint . 'payments/payment';
  $headers = array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $authToken
  );
  $response = json_decode(curlRequest($paymentUrl, $headers, json_encode($paymentData)), true);

  if ($response['state'] == 'approved') {
    // Payment successful, update database and redirect
    header('Location: https://example.com/success');
    exit;
  } else {
    // Payment failed, display error message
    echo 'Payment failed: ' . $response['message'];
  }
}

?>