<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__."/../../func/bc-config.php");

function handle_convert_currency($connection_server, $select_vendor_table) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'convert-currency') {
        $from_currency = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["from_currency"])));
        $to_currency = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["to_currency"])));
        $amount = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["amount"])));

        $conversion_data = array(
            "amount" => $amount * 100,
            "from" => $from_currency,
            "to" => $to_currency
        );

        $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server, "SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));
        $conversion_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/exchange/convert", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($conversion_data)), true);

        if (isset($conversion_response["data"]["converted_amount"])) {
            $converted_amount = $conversion_response["data"]["converted_amount"] / 100;
            $_SESSION["product_purchase_response"] = "Successfully converted " . $amount . " " . $from_currency . " to " . $converted_amount . " " . $to_currency . ".";
        } else {
            $_SESSION["product_purchase_response"] = "Failed to convert currency: " . ($conversion_response["error"]["message"] ?? "Unknown error");
        }
        header("Location: ".$_SERVER["REQUEST_URI"]);
        exit();
    }
}
?>
