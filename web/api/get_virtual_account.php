<?php
session_start();
include_once("../../func/bc-config.php");

if(!isset($_SESSION["user_session"])){
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = $select_user_table['id'];
$currency = isset($_GET['currency']) ? $_GET['currency'] : 'NGN';

// Get Juicyway API keys
$juicyway_keys_query = mysqli_query($connection_server, "SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'");
$juicyway_keys = mysqli_fetch_assoc($juicyway_keys_query);

$customer_data = array(
    "first_name" => $select_user_table['firstname'],
    "last_name" => $select_user_table['lastname'],
    "email" => $select_user_table['email'],
    "phone_number" => $select_user_table['phone_number'],
    "billing_address" => array(
        "line1" => "N/A",
        "city" => "N/A",
        "state" => "N/A",
        "country" => "NG",
        "zip_code" => "N/A"
    ),
    "ip_address" => $_SERVER['REMOTE_ADDR']
);

$payment_session_data = array(
    "customer" => $customer_data,
    "description" => "Wallet Deposit",
    "currency" => $currency,
    "amount" => 0, // Amount is not known at this point
    "direction" => "incoming",
    "payment_method" => array(
        "type" => "bank_account"
    ),
    "reference" => "deposit_".substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyz"), 0, 10),
    "order" => array(
        "identifier" => "ORD-DEPOSIT-".substr(str_shuffle("1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10),
        "items" => array(
            array(
                "name" => "Wallet Deposit",
                "type" => "digital"
            )
        )
    )
);

$response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/payment-sessions", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($payment_session_data)), true);

if(isset($response["data"]["payment"]["payment_method"])){
    echo json_encode($response["data"]["payment"]["payment_method"]);
} else {
    echo json_encode(["error" => "Failed to generate virtual account.", "details" => $response]);
}
?>