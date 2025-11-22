<?php
$exam_service_provider_alter_code = array(
    "waec" => array("result_checker" => "1"),
    "neco" => array("result_checker" => "2"),
    "nabteb" => array("result_checker" => "3")
);

if (array_key_exists($product_name, $exam_service_provider_alter_code) && isset($exam_service_provider_alter_code[$product_name][$card_type])) {
    $card_type_id = $exam_service_provider_alter_code[$product_name][$card_type];
    $curl_url = "https://" . $api_detail["api_base_url"] . "/api/v1/exam-card/buy";
    $curl_request = curl_init($curl_url);
    curl_setopt($curl_request, CURLOPT_POST, true);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
    $curl_http_headers = array("Authorization: Bearer " . $api_detail["api_key"], "Content-Type: application/json");
    curl_setopt($curl_request, CURLOPT_HTTPHEADER, $curl_http_headers);
    $curl_postfields_data = json_encode(array("card_type_id" => $card_type_id, "quantity" => $quantity), true);
    curl_setopt($curl_request, CURLOPT_POSTFIELDS, $curl_postfields_data);
    $curl_result = curl_exec($curl_request);
    $curl_json_result = json_decode($curl_result, true);

    if (curl_errno($curl_request)) {
        $api_response = "failed";
        $api_response_text = curl_error($curl_request);
        $api_response_description = "Curl Error";
        $api_response_status = 3;
    } else {
        if (isset($curl_json_result["status"]) && $curl_json_result["status"] === true && $curl_json_result["code"] == "000") {
            $api_response = "successful";
            $api_response_reference = $curl_json_result["reference"];
            $api_response_text = $curl_json_result["message"];
            $cards = [];
            foreach ($curl_json_result["cards"] as $card) {
                $cards[] = "PIN: " . $card["pin"] . ", Serial No: " . $card["serial_no"];
            }
            $api_response_description = "Transaction Successful | " . implode(" | ", $cards);
            $api_response_status = 1;
        } else {
            $api_response = "failed";
            $api_response_text = isset($curl_json_result["message"]) ? $curl_json_result["message"] : "Unknown Error";
            $api_response_description = "Transaction Failed";
            $api_response_status = 3;
        }
    }
    curl_close($curl_request);
} else {
    //Service not available
    $api_response = "failed";
    $api_response_text = "";
    $api_response_description = "Service not available";
    $api_response_status = 3;
}
?>