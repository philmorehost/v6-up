<?php
session_start();
include_once("../../func/bc-config.php");

if(!isset($_SESSION["user_session"])){
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = $select_user_table['id'];
$currency = isset($_GET['currency']) ? $_GET['currency'] : 'NGN';

$stmt = mysqli_prepare($connection_server, "SELECT * FROM sas_juicyway_transfers WHERE user_id = ? AND (source_currency = ? OR destination_currency = ? OR crypto_currency = ?) ORDER BY id DESC");
mysqli_stmt_bind_param($stmt, "isss", $user_id, $currency, $currency, $currency);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$transactions = [];
while($row = mysqli_fetch_assoc($result)){
    $transactions[] = $row;
}

echo json_encode($transactions);
?>