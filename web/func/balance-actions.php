<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__."/../../func/bc-config.php");

function get_juicyway_balance($currency, $connection_server, $select_vendor_table, $select_user_table) {
    // API endpoint to fetch balances
    $url = "https://api.spendjuice.com/balances";

    // Fetch Juicyway API keys from the database
    $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server, "SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));

    // Set up the authorization header
    $headers = ["Authorization: ".$juicyway_keys["secret_key"]];

    // Execute the API request
    $response = executeApiRequest("GET", $url, $headers, "");

    // Decode the JSON response
    $balances_data = json_decode($response, true);

    // Find the balance for the specified currency
    if (isset($balances_data['data'])) {
        foreach ($balances_data['data'] as $balance) {
            if ($balance['currency'] === $currency) {
                return $balance;
            }
        }
    }

    // Return a default balance if not found
    return [
        'available_balance' => 0,
        'ledger_balance' => 0,
        'currency' => $currency
    ];
}

function get_juicyway_transactions($currency, $connection_server, $select_vendor_table) {
    // API endpoint to fetch transactions, with currency filtering
    $url = "https://api.spendjuice.com/transactions?currency=" . urlencode($currency);

    // Fetch Juicyway API keys from the database
    $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server, "SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));

    // Set up the authorization header
    $headers = ["Authorization: ".$juicyway_keys["secret_key"]];

    // Execute the API request
    $response = executeApiRequest("GET", $url, $headers, "");

    // Decode the JSON response
    $transactions_data = json_decode($response, true);

    // Return the transactions if available
    if (isset($transactions_data['data'])) {
        return $transactions_data['data'];
    }

    // Return an empty array if no transactions are found
    return [];
}
?>
