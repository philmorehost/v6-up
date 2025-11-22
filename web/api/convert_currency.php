<?php
session_start();
include_once("../../func/bc-config.php");

if(!isset($_SESSION["user_session"])){
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = $select_user_table['id'];
$from_currency = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["from_currency"])));
$to_currency = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["to_currency"])));
$amount = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["amount"])));

if (empty($from_currency) || empty($to_currency) || empty($amount)) {
    echo json_encode(["error" => "All fields are required."]);
    exit();
}

if ($from_currency === $to_currency) {
    echo json_encode(["error" => "Cannot convert to the same currency."]);
    exit();
}

// Fetch the user's balance for the "from" currency
$stmt = mysqli_prepare($connection_server, "SELECT balance FROM sas_user_wallets WHERE user_id = ? AND currency = ?");
mysqli_stmt_bind_param($stmt, "is", $user_id, $from_currency);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $from_wallet = mysqli_fetch_assoc($result);
    if ($from_wallet['balance'] < $amount) {
        echo json_encode(["error" => "Insufficient funds."]);
        exit();
    }
} else {
    echo json_encode(["error" => "You do not have a wallet for the 'from' currency."]);
    exit();
}

// Get Juicyway API keys
$juicyway_keys_query = mysqli_query($connection_server, "SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'");
$juicyway_keys = mysqli_fetch_assoc($juicyway_keys_query);

$conversion_data = array(
    "amount" => $amount,
    "from" => $from_currency,
    "to" => $to_currency
);

$conversion_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/exchange/convert", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($conversion_data)), true);

if(!isset($conversion_response["data"]["converted_amount"])){
    echo json_encode(["error" => "Failed to get conversion rate from Juicyway."]);
    exit();
}

$converted_amount = $conversion_response["data"]["converted_amount"];

// Update the user's wallet balances
$new_from_balance = $from_wallet['balance'] - $amount;
$stmt_update_from = mysqli_prepare($connection_server, "UPDATE sas_user_wallets SET balance = ? WHERE user_id = ? AND currency = ?");
mysqli_stmt_bind_param($stmt_update_from, "dis", $new_from_balance, $user_id, $from_currency);
mysqli_stmt_execute($stmt_update_from);

$stmt_to = mysqli_prepare($connection_server, "SELECT balance FROM sas_user_wallets WHERE user_id = ? AND currency = ?");
mysqli_stmt_bind_param($stmt_to, "is", $user_id, $to_currency);
mysqli_stmt_execute($stmt_to);
$result_to = mysqli_stmt_get_result($stmt_to);

if (mysqli_num_rows($result_to) > 0) {
    $to_wallet = mysqli_fetch_assoc($result_to);
    $new_to_balance = $to_wallet['balance'] + $converted_amount;
    $stmt_update_to = mysqli_prepare($connection_server, "UPDATE sas_user_wallets SET balance = ? WHERE user_id = ? AND currency = ?");
    mysqli_stmt_bind_param($stmt_update_to, "dis", $new_to_balance, $user_id, $to_currency);
    mysqli_stmt_execute($stmt_update_to);
} else {
    $stmt_insert_to = mysqli_prepare($connection_server, "INSERT INTO sas_user_wallets (user_id, currency, balance) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt_insert_to, "isd", $user_id, $to_currency, $converted_amount);
    mysqli_stmt_execute($stmt_insert_to);
}

echo json_encode(["success" => true]);
?>
