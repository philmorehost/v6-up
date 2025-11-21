<?php
    $data_service_provider_alter_code = array("mtn" => "mtn", "airtel" => "airtel", "glo" => "glo", "9mobile" => "9mobile");
    if(in_array($product_name, array_keys($data_service_provider_alter_code))){
        if($product_name == "mtn"){
            $clubkonnect_isp_code = "01";
            $web_data_size_array = array("110mb_awoof_1day" => "100.01","230mb_awoof_1day" => "200.01","500mb_awoof_1day" => "350.01","1gb_1.5mins_awoof_1day" => "500.01","2.5gb_awoof_2days" => "900.01","3.2gb_awoof_2days" => "1000.01","1gb_weekly" => "800.01","11gb_weekly" => "3500.01","2gb_monthly" => "1500.02","2.7gb_10mins_monthly" => "2000.01","3.5gb_monthly" => "2500.02","7gb_monthly" => "3500.02","10gb_10mins_monthly" => "4500.01","12.5gb_monthly" => "5500.01","16.5gb_plus_10mins_monthly" => "6500.01","20gb_monthly" => "7500.01","25gb_monthly" => "9000.01","36gb_30days" => "11000.01","75gb_30days" => "18000.01","165gb_30days" => "35000.01","150gb_60days" => "40000.01","480gb_90days" => "90000.03");
		}else{
            if($product_name == "airtel"){
                $clubkonnect_isp_code = "04";
                $web_data_size_array = array("1gb_awoof_2days" => "499.91","1.5gb_awoof_2days" => "599.91","2gb_awoof_2days" => "749.91","3gb_awoof_2days" => "999.91","5gb_awoof_2days" => "1499.91","500mb_7days" => "499.92","1gb_7days" => "799.91","1.5gb_7days" => "799.92","3.5gb_7days" => "1499.92","6gb_7days" => "2499.91","10gb_7days" => "2999.91","18gb_7days" => "4999.91","2gb_30days" => "1499.93","3gb_30days" => "1999.91","4gb_30days" => "2499.92","8gb_30days" => "2999.92","10gb_30days" => "3999.91","13gb_30days" => "4999.92","18gb_30days" => "5999.91","25gb_30days" => "7999.91","35gb_30days" => "9999.91","60gb_30days" => "14999.91","100gb_30days" => "19999.91","160gb_30days" => "29999.91","210gb_30days" => "39999.91","300gb_30days" => "49999.91","350gb_30days" => "59999.91");
            }else{
                if($product_name == "glo"){
                    $clubkonnect_isp_code = "02";
                    $web_data_size_array = array("875mb_awoof_weekend_sun" => "200.01","2.5gb_awoof_weekend_sat-sun" => "500.03","125mb_awoof_1day" => "100.01","2gb_awoof_1day" => "500.02","260mb_awoof_2days" => "200.01","6gb_7days" => "1500.02","1.5gb_14days" => "500.01","2.6gb_30days" => "1000.01","5gb_30days" => "1500.01","6.15gb_30days" => "2000.01","7.5gb_30days" => "2500.01","10gb_30days" => "3000.01","12.5gb_30days" => "4000.01","16gb_30days" => "5000.01","28gb_30days" => "8000.01","38gb_30days" => "10000.01","64gb_30days" => "15000.01","107gb_30days" => "20000.01","165gb_30days" => "30000.01","220gb_30days" => "36000.01","320gb_30days" => "50000.01","380gb_30days" => "60000.01","475gb_30days" => "75000.01");
                }else{
                    if($product_name == "9mobile"){
                        $clubkonnect_isp_code = "03";
                        $web_data_size_array = array("100mb_awoof_1day" => "100.01","180mb_awoof_1day" => "150.01","250mb_awoof_1day" => "200.01","450mb_awoof_1day" => "350.01","650mb_awoof_3days" => "500.01","1.75gb_7days" => "1500.01","650mb_14days" => "600.01","1.1gb_30days" => "1000.01","1.4gb_30days" => "1200.01","2.44gb_30days" => "2000.01","3.17gb_30days" => "2500.01","3.91gb_30days" => "3000.01","5.10_30days" => "4000.01","6.5gb_30days" => "5000.01","16gb_30days" => "12000.01","24.3gb_30days" => "18500.01","26.5gb_30days" => "20000.01","39gb_60days" => "30000.01","78gb_90days" => "60000.01","190gb_180days" => "150000.01");
                    }
                }
            }
        }
        if(in_array($quantity, array_keys($web_data_size_array))){
        	$explode_clubkonnect_apikey = array_filter(explode(":",trim($api_detail["api_key"])));
            $curl_url = "https://www.nellobytesystems.com/APIDatabundleV1.asp?UserID=".$explode_clubkonnect_apikey[0]."&APIKey=".$explode_clubkonnect_apikey[1]."&MobileNetwork=".$clubkonnect_isp_code."&DataPlan=".$web_data_size_array[$quantity]."&MobileNumber=".$phone_no;
            $curl_request = curl_init($curl_url);
            curl_setopt($curl_request, CURLOPT_HTTPGET, true);
            curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
            $curl_result = curl_exec($curl_request);
            $curl_json_result = json_decode($curl_result, true);
            

            if(curl_errno($curl_request)){
                $api_response = "failed";
                $api_response_text = 1;
                $api_response_description = "";
                $api_response_status = 3;
            }
            
            if(in_array($curl_json_result["statuscode"],array(200, 201, 299))){
            	$api_response = "successful";
            	$api_response_reference = $curl_json_result["orderid"];
            	$api_response_text = $curl_json_result["status"];
            	$api_response_description = "Transaction Successful | You have successfully shared ".strtoupper(str_replace(["_","-"]," ",$quantity))." Data to 234".substr($phone_no, "1", "11");
            	$api_response_status = 1;
            }
            
            if(in_array($curl_json_result["statuscode"],array(100, 300))){
            	$api_response = "pending";
            	$api_response_reference = $curl_json_result["orderid"];
            	$api_response_text = $curl_json_result["status"];
            	$api_response_description = "Transaction Pending | You have successfully shared ".strtoupper(str_replace(["_","-"]," ",$quantity))." Data to 234".substr($phone_no, "1", "11");
            	$api_response_status = 2;
            }
            
            if(!in_array($curl_json_result["statuscode"],array(100, 300, 200, 201, 299))){
            	$api_response = "failed";
            	$api_response_text = $curl_json_result["status"];
            	$api_response_description = "Transaction Failed | ".strtoupper(str_replace(["_","-"]," ",$quantity))." Data shared to 234".substr($phone_no, "1", "11")." failed";
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