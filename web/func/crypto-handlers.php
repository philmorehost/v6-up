<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__."/../../func/bc-config.php");

function handle_initiate_crypto_payment($connection_server, $select_vendor_table, $select_user_table) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'initiate-crypto-payment') {
        $first_name = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["first_name"])));
        $last_name = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["last_name"])));
        $email = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["email"])));
        $phone_number = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["phone_number"])));
        $amount = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["amount"])));
        $currency = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["currency"])));
        $description = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["description"])));

        $crypto_data = array(
            "customer" => array(
                "first_name" => $first_name,
                "last_name" => $last_name,
                "email" => $email,
                "phone_number" => $phone_number,
                "billing_address" => array(
                    "line1" => "",
                    "city" => "",
                    "state" => "",
                    "country" => "NG",
                    "zip_code" => ""
                ),
                "ip_address" => $_SERVER['REMOTE_ADDR']
            ),
            "description" => $description,
            "currency" => $currency,
            "amount" => $amount * 1000000,
            "direction" => "incoming",
            "payment_method" => array(
                "type" => "crypto_address"
            ),
            "reference" => "crypto-tx-".substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyz"), 0, 10),
            "order" => array(
                "identifier" => "ORD-".substr(str_shuffle("1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10),
                "items" => array(
                    array(
                        "name" => "Digital Product",
                        "type" => "digital"
                    )
                )
            )
        );

        $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server, "SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));
        $initiate_crypto_payment_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/payment-sessions", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($crypto_data)), true);

        if (isset($initiate_crypto_payment_response["data"]["payment"]["payment_method"]["address"])) {
            $address = $initiate_crypto_payment_response["data"]["payment"]["payment_method"]["address"];
            $chain = $initiate_crypto_payment_response["data"]["payment"]["payment_method"]["chain"];
            $crypto_currency = $initiate_crypto_payment_response["data"]["payment"]["payment_method"]["currency"];
            $_SESSION["product_purchase_response"] = "Crypto payment initiated. Please send ".$crypto_currency." to the following address on the ".$chain." network: ".$address;
        } else {
            $_SESSION["product_purchase_response"] = "Failed to initiate crypto payment: " . ($initiate_crypto_payment_response["error"]["message"] ?? "Unknown error");
        }
        header("Location: ".$_SERVER["REQUEST_URI"]);
        exit();
    }
}
?>
