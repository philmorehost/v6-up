<?php
session_start();
include_once("../../func/bc-config.php");

if(!isset($_SESSION["user_session"])){
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = $select_user_table['id'];
$vendor_id = $select_vendor_table['id'];
$amount = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["amount"])));
$currency = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["currency"])));
$description = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["description"])));

if (empty($amount) || empty($currency) || empty($description)) {
    echo json_encode(["error" => "All fields are required."]);
    exit();
}

$reference = "pl_" . substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyz"), 0, 15);

$stmt = $connection_server->prepare("INSERT INTO sas_payment_links (user_id, vendor_id, amount, currency, description, reference) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iidsss", $user_id, $vendor_id, $amount, $currency, $description, $reference);

if ($stmt->execute()) {
    $payment_link = $web_http_host . "/payment.php?ref=" . $reference;
    echo json_encode(["payment_link" => $payment_link]);
} else {
    echo json_encode(["error" => "Failed to create payment link."]);
}
?>
