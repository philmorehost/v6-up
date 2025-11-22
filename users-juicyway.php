<?php session_start();
	include(__DIR__."/func/bc-connect.php");
	include(__DIR__."/func/bc-func.php");

	$catch_incoming_request = json_decode(file_get_contents("php://input"),true);

	//Select Vendor Table
	$select_vendor_table = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_vendors WHERE website_url='".$_SERVER["HTTP_HOST"]."' LIMIT 1"));
	if(($select_vendor_table == true) && ($select_vendor_table["website_url"] == $_SERVER["HTTP_HOST"]) && ($select_vendor_table["status"] == 1)){
		$juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server,"SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));

        $checksum = $catch_incoming_request['checksum'];
        $event = $catch_incoming_request['event'];
        $data = json_encode($catch_incoming_request['data']);
        $message = "$event|$data";
        $expected = hash_hmac('sha256', $message, $juicyway_keys["secret_key"]);

        if (!hash_equals($expected, $checksum)) {
            // Invalid signature
            http_response_code(401);
            exit();
        }

		$transaction_id = $catch_incoming_request["data"]["id"];
		$juicyway_verify_transaction = json_decode(executeApiRequest("GET","https://api.spendjuice.com/payments/".$transaction_id,["Authorization: ".$juicyway_keys["secret_key"]],""),true);

		if($juicyway_verify_transaction["data"]["status"] == "succeeded"){
			$customer_email = $juicyway_verify_transaction["data"]["customer"]["email"];
			$amount_paid = $juicyway_verify_transaction["data"]["amount"] / 100;
			$amount_deposited = $amount_paid;
			$transaction_id = $juicyway_verify_transaction["data"]["id"];
			$payment_method = $juicyway_verify_transaction["data"]["payment_method"]["type"];

			$vendor_id = trim($select_vendor_table["id"]);

			$check_vendor_user_exists = mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='$vendor_id' && email='$customer_email'");
			if(mysqli_num_rows($check_vendor_user_exists) == 1){
				$get_logged_user_details = mysqli_fetch_array($check_vendor_user_exists);
				$user_session = $get_logged_user_details["username"];

				$select_transaction_history = mysqli_query($connection_server,"SELECT * FROM sas_transactions WHERE (api_reference='$transaction_id')");

				if(mysqli_num_rows($select_transaction_history) == 0){
					$reference = substr(str_shuffle("12345678901234567890"), 0, 15);
					chargeUser("credit", $user_session, "Wallet Credit", $reference, $transaction_id, $amount_paid, $amount_deposited, "Juicyway Wallet Credit - ".str_replace("_"," ",$payment_method), strtoupper("WEB"), $_SERVER["HTTP_HOST"], "1");
				}
			}
		}
	}
?>
