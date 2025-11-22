<?php
session_start();
include_once("../../func/bc-config.php");

if(!isset($_SESSION["user_session"])){
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = $select_user_table['id'];
$currency = isset($_GET['currency']) ? $_GET['currency'] : 'NGN';

// Fetch the balance from the new sas_user_wallets table
$stmt = mysqli_prepare($connection_server, "SELECT balance FROM sas_user_wallets WHERE user_id = ? AND currency = ?");
mysqli_stmt_bind_param($stmt, "is", $user_id, $currency);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0){
    $wallet = mysqli_fetch_assoc($result);
    $balance = $wallet['balance'];
} else {
    // If the user doesn't have a wallet for this currency, create one.
    $stmt_insert = mysqli_prepare($connection_server, "INSERT INTO sas_user_wallets (user_id, currency, balance) VALUES (?, ?, 0.00)");
    mysqli_stmt_bind_param($stmt_insert, "is", $user_id, $currency);
    mysqli_stmt_execute($stmt_insert);
    $balance = 0.00;
}

echo json_encode(["balance" => $balance, "currency" => $currency]);
?>
