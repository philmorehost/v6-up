<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__."/../../func/bc-config.php");

function get_virtual_account($currency, $connection_server, $select_vendor_table, $select_user_table) {
    // API endpoint to fetch virtual accounts
    $url = "https://api.spendjuice.com/virtual-accounts?currency=" . urlencode($currency);

    // Fetch Juicyway API keys from the database
    $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server, "SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));

    // Set up the authorization header
    $headers = ["Authorization: ".$juicyway_keys["secret_key"]];

    // Execute the API request
    $response = executeApiRequest("GET", $url, $headers, "");

    // Decode the JSON response
    $accounts_data = json_decode($response, true);

    // Return the first account if available
    if (isset($accounts_data['data'][0])) {
        return $accounts_data['data'][0];
    }

    // Return null if no account is found
    return null;
}
?>
