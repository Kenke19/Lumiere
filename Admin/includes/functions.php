<?php
require_once 'db.php'; // Your config file with PAYSTACK_SECRET_KEY constant

function verifyPaystackPayment($reference) {
    $secretKey = PAYSTACK_SECRET_KEY;
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/$reference",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $secretKey",
            "Cache-Control: no-cache",
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    if (!$response) {
        return false;
    }

    $result = json_decode($response, true);

    if ($result['status'] && $result['data']['status'] === 'success') {
        return $result['data'];
    }
    return false;
}
