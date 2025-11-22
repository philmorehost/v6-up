<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__."/../../func/bc-config.php");

function handle_initiate_transfer($connection_server, $select_vendor_table, $select_user_table, $banks) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'initiate-transfer') {
        $account_name = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["account_name"])));
        $account_number = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["account_number"])));
        $bank_name = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["bank_name"])));
        $bank_code = "";
        foreach ($banks as $bank) {
            if ($bank["name"] == $bank_name) {
                $bank_code = $bank["code"];
                break;
            }
        }
        $amount = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["amount"])));
        $description = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["description"])));
        $pin = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["pin"])));

        $beneficiary_data = array(
            "type" => "bank_account",
            "currency" => "NGN",
            "account_name" => $account_name,
            "account_number" => $account_number,
            "bank_name" => $bank_name,
            "bank_code" => $bank_code,
            "rail" => "nuban"
        );

        $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server, "SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));
        $create_beneficiary_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/beneficiaries", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($beneficiary_data)), true);

        if (isset($create_beneficiary_response["data"]["id"])) {
            $beneficiary_id = $create_beneficiary_response["data"]["id"];

            $transfer_data = array(
                "amount" => $amount * 100,
                "beneficiary" => array(
                    "id" => $beneficiary_id,
                    "type" => "bank_account"
                ),
                "description" => $description,
                "destination_currency" => "NGN",
                "pin" => $pin,
                "reference" => "pmt_user_".substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyz"), 0, 10),
                "source_currency" => "NGN",
                "fee_charged_to" => "sender"
            );

            $initiate_transfer_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/payouts", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($transfer_data)), true);

            if (isset($initiate_transfer_response["data"]["id"])) {
                $_SESSION["product_purchase_response"] = "Transfer initiated successfully.";
            } else {
                $_SESSION["product_purchase_response"] = "Failed to initiate transfer: " . ($initiate_transfer_response["error"]["message"] ?? "Unknown error");
            }
        } else {
            $_SESSION["product_purchase_response"] = "Failed to create beneficiary: " . ($create_beneficiary_response["error"]["message"] ?? "Unknown error");
        }
        header("Location: ".$_SERVER["REQUEST_URI"]);
        exit();
    }
}
?>
