<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__."/../../func/bc-config.php");

function handle_create_payment_link($connection_server, $select_vendor_table, $select_user_table) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create-payment-link') {
        $item_description = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["item_description"])));
        $amount = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["amount"])));
        $currency = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["currency"])));

        $payment_link_data = array(
            "customer" => array(
                "first_name" => $select_user_table['fullname'],
                "last_name" => "",
                "email" => $select_user_table['email'],
                "phone_number" => $select_user_table['phone'],
                "billing_address" => array(
                    "line1" => "",
                    "city" => "",
                    "state" => "",
                    "country" => "NG",
                    "zip_code" => ""
                ),
                "ip_address" => $_SERVER['REMOTE_ADDR']
            ),
            "description" => $item_description,
            "currency" => $currency,
            "amount" => $amount * 100,
            "direction" => "incoming",
            "payment_method" => array(
                "type" => "bank_account"
            ),
            "reference" => "plink_".substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyz"), 0, 10),
            "order" => array(
                "identifier" => "ORD-".substr(str_shuffle("1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10),
                "items" => array(
                    array(
                        "name" => $item_description,
                        "type" => "digital"
                    )
                )
            )
        );

        $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server, "SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));
        $payment_link_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/payment-sessions", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($payment_link_data)), true);

        if (isset($payment_link_response["data"]["links"]["payment_url"])) {
            $payment_link = $payment_link_response["data"]["links"]["payment_url"];
            $_SESSION["product_purchase_response"] = "Payment link created successfully: " . $payment_link;
        } else {
            $_SESSION["product_purchase_response"] = "Failed to create payment link: " . ($payment_link_response["error"]["message"] ?? "Unknown error");
        }
        header("Location: ".$_SERVER["REQUEST_URI"]);
        exit();
    }
}
?>
