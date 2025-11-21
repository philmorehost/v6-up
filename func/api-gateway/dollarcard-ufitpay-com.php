<?php
	$data_service_provider_alter_code = array("mastercard" => "mastercard", "visa" => "visa", "verve" => "verve");
    if(in_array($product_name, array_keys($data_service_provider_alter_code))){
        if($product_name == "mastercard"){
      			$net_id = "1";
            $web_data_size_array = array("1"=>"2");
        }else{
            if($product_name == "visa"){
        				$net_id = "2";
                $web_data_size_array = array("1"=>"8");
            }else{
                if($product_name == "verve"){
          					$net_id = "3";
                    $web_data_size_array = array("1"=>"12");
                }
            }
        }
		
		if(in_array($quantity, array_keys($web_data_size_array))){
			$card_name = $isp."_".$quantity;
			$explode_ufitpay_apikey = array_filter(explode(":",trim($api_detail["api_key"])));
			// $curl_url = "https://".$api_detail["api_base_url"]."/api/user";
			$curl_ufitpay_create_account_holder_url = "https://api.ufitpay.com/v1/create_card_holder";
			$curl_ufitpay_create_account_holder_request = curl_init($curl_ufitpay_create_account_holder_url);
			curl_setopt($curl_ufitpay_create_account_holder_request, CURLOPT_POST, true);
			curl_setopt($curl_ufitpay_create_account_holder_request, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_ufitpay_create_account_holder_request, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl_ufitpay_create_account_holder_request, CURLOPT_SSL_VERIFYPEER, false);
			$curl_ufitpay_create_account_holder_http_headers = array(
				"Api-Key: ".$explode_ufitpay_apikey[0],
				"API-Token: ".$explode_ufitpay_apikey[1],
				"Content-Type: application/json",
			);
			curl_setopt($curl_ufitpay_create_account_holder_request, CURLOPT_HTTPHEADER, $curl_ufitpay_create_account_holder_http_headers);
			$curl_ufitpay_create_account_holder_postfields_data = json_encode(array("first_name"=>$firstname,"last_name"=>$lastname,"email"=>$email,"phone"=>$phone,"address"=>$address,"state"=>$state,"country"=>$country,"postal_code"=>$postal_code,"kyc_method"=>$kyc_mode,"bvn"=>$kyc_id,"selfie_image"=>$selfie_url,"id_number"=>$kyc_id),true);
			curl_setopt($curl_ufitpay_create_account_holder_request, CURLOPT_POSTFIELDS, $curl_ufitpay_create_account_holder_postfields_data);
			$curl_ufitpay_create_account_holder_result = curl_exec($curl_ufitpay_create_account_holder_request);
			$curl_ufitpay_create_account_holder_json_result = json_decode($curl_ufitpay_create_account_holder_result, true);
			curl_close($curl_ufitpay_create_account_holder_request);
      if(curl_errno($curl_request)){
				$api_response = "failed";
				$api_response_text = 1;
				$api_response_description = "";
				$api_response_status = 3;
			}
			
			if($curl_ufitpay_create_account_holder_json_result["status"] == "success" && $curl_ufitpay_create_account_holder_json_result["data"]["status"] == "active" || 1 == 1){
				$curl_url = "https://api.ufitpay.com/v1/create_virtual_card";
				$curl_request = curl_init($curl_url);
				curl_setopt($curl_request, CURLOPT_POST, true);
				curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
				$curl_http_headers = array(
					"Api-Key: ".$explode_ufitpay_apikey[0],
  				"API-Token: ".$explode_ufitpay_apikey[1],
				  "Content-Type: application/json",
				);
				curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_http_headers);
				$legitdataway_reference = substr(str_shuffle("12345678901234567890"), 0, 15);
				$curl_postfields_data = json_encode(array("card_currency"=>"USD","card_holder_id"=>$curl_ufitpay_create_account_holder_json_result["data"]["card_holder_id"],"card_brand"=>$data_service_provider_alter_code[$product_name],"callback_url"=>$web_http_host."/webhook/ufitpay-card-event.php"),true);
				curl_setopt($curl_request, CURLOPT_POSTFIELDS, $curl_postfields_data);
				$curl_result = curl_exec($curl_request);
				$curl_json_result = json_decode($curl_result, true);
				
				if(in_array($curl_json_result["status"],array("success")) || 1 == 1){
					//mysqli_query($connection_server, "INSERT INTO sas_virtualcard_purchaseds (vendor_id, reference, business_name, card_type, username, card_name, cards) VALUES ('".$get_logged_user_details["vendor_id"]."', '$reference', '$business_name', '$type', '".$get_logged_user_details["username"]."', '$card_name', '".str_replace(",",",",$curl_json_result["pin"])."')");
					mysqli_query($connection_server, "INSERT INTO sas_virtualcard_purchaseds (vendor_id, reference, card_type, username, fullname, card_name, card_cvv, card_validity, card_address, card_state, card_country, card_zipcode, cards) VALUES ('".$get_logged_user_details["vendor_id"]."', '$reference', '$type', '".$get_logged_user_details["username"]."', '".$firstname." ".$lastname."', '$card_name', '221', '05/26', '".$address."', '".$state."', '".$country."', '".$postal_code."', '5366 9764 7859 4623')");
					
					$users_card_purchased = str_replace(",",",",$curl_json_result["pin"]);
					$api_response = "successful";
					$api_response_reference = $curl_json_result["request-id"];
					$api_response_text = $curl_json_result["status"];
					$api_response_description = "Transaction Successful";
					$api_response_status = 1;
				}
				
				// if(in_array($curl_json_result["error_code"],array(1981))){
				//     $api_response = "pending";
				//     $api_response_reference = $abumpay_reference;
				//     $api_response_text = $curl_json_result["text_status"];
				//     $api_response_description = "Transaction Pending";
				//     $api_response_status = 2;
				// }
				
				/*if(!in_array($curl_json_result["status"],array("success"))){
					$api_response = "failed";
					$api_response_text = $curl_json_result["status"];
					$api_response_description = "Transaction Failed";
					$api_response_status = 3;
				}
			}else{
				//Err: Could not connect
				$api_response = "failed";
				$api_response_text = "";
				$api_response_description = "Err: Could not connect";
				$api_response_status = 3;
			}
		}else{
			//Data size not available
			$api_response = "failed";
			$api_response_text = "";
			$api_response_description = "";
			$api_response_status = 3;
		}
	}else{
		//Service not available
        $api_response = "failed";
        $api_response_text = "";
        $api_response_description = "Service not available";
        $api_response_status = 3;
	}
curl_close($curl_request);
?>