<?php session_start();
    include("../func/bc-admin-config.php");
        
    if(isset($_POST["update-key"])){
        $api_id = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["api-id"])));
        $apikey = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["api-key"])));
        $apistatus = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["api-status"])));
        
        if(!empty($api_id) && is_numeric($api_id)){
            if(!empty($apikey)){
                if(is_numeric($apistatus) && in_array($apistatus, array("0", "1"))){
                    $select_api_lists = mysqli_query($connection_server, "SELECT * FROM sas_apis WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='$api_id' && api_type='shared-data'");
                    if(mysqli_num_rows($select_api_lists) == 1){
                        mysqli_query($connection_server, "UPDATE sas_apis SET api_key='$apikey', status='$apistatus' WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='$api_id' && api_type='shared-data'");
                        //APIkey Updated Successfully
                        $json_response_array = array("desc" => "APIkey Updated Successfully");
                        $json_response_encode = json_encode($json_response_array,true);
                    }else{
                        //API Doesnt Exists
                        $json_response_array = array("desc" => "API Doesnt Exists");
                        $json_response_encode = json_encode($json_response_array,true);
                    }
                }else{
                    //Invalid API Status
                    $json_response_array = array("desc" => "Invalid API Status");
                    $json_response_encode = json_encode($json_response_array,true);
                }
            }else{
                //Apikey Field Empty
                $json_response_array = array("desc" => "Apikey Field Empty");
                $json_response_encode = json_encode($json_response_array,true);
            }
        }else{
            //Invalid Apikey Website
            $json_response_array = array("desc" => "Invalid Apikey Website");
            $json_response_encode = json_encode($json_response_array,true);
        }
        $json_response_decode = json_decode($json_response_encode,true);
        $_SESSION["product_purchase_response"] = $json_response_decode["desc"];
        header("Location: ".$_SERVER["REQUEST_URI"]);
    } 

    include_once("../func/bc-product-actions.php");
    handle_product_actions($connection_server, $get_logged_admin_details);

    if(isset($_POST["install-product"])){
        $product_names_str = mysqli_real_escape_string($connection_server, trim(strip_tags(strtolower($_POST["product-name"]))));
        $products_array = array_filter(explode(',', $product_names_str));
        $product_varieties = array(
            "mtn" => array("75mb_1day","110mb_1day","230mb_1day" ,"500mb","750mb_Free_1hour_TT/YT/IG_3days","1gb_1.5mins_1day","1.5gb","2.5gb_1day","2.5gb_2days","1gb_7days","2gb_2days","1.5gb_7days","3.2gb_2days","6gb_7days","11gb_7days","7gb_30days","10gb_10mins+2gb_YouTube_30days","12.5gb_30days","16.5gb_10Minutes_30days","40gb_60days","36gb_30days","75gb_30days","90gb_60days","165gb_30days","150gb_60days","200gb_30days","200gb_60days","250gb_30days","200gb_30days","480gb_90days"),
            "airtel" => array("150mb_1day","300mb_2days","600mb_2days","1gb_(Social)3days","1.5gb_2days","2gb_2days","3gb_7days","5gb_2days","7gb_7days","10gb_30days"),
            "glo" => array("750mb_1day","1.5gb_1day","2.5gb_2days","10gb_7days"),
            "9mobile" => array()
        );
        $account_level_table_name_arrays = array("sas_smart_parameter_values", "sas_agent_parameter_values", "sas_api_parameter_values");
        install_product($connection_server, $get_logged_admin_details, "shared-data", "sas_shared_data_status", $products_array, $product_varieties, $account_level_table_name_arrays);
    }

    if(isset($_GET['action']) && $_GET['action'] == 'toggle_status') {
        $api_id = mysqli_real_escape_string($connection_server, $_GET['api_id']);
        $product_id = mysqli_real_escape_string($connection_server, $_GET['product_id']);
        $val_1 = mysqli_real_escape_string($connection_server, $_GET['val_1']);
        $current_status = mysqli_real_escape_string($connection_server, $_GET['current_status']);
        $new_status = $current_status == 1 ? 0 : 1;

        $account_level_tables = ["sas_smart_parameter_values", "sas_agent_parameter_values", "sas_api_parameter_values"];

        foreach ($account_level_tables as $table) {
            $update_sql = "UPDATE `$table` SET `status` = '$new_status' WHERE `vendor_id`='".$get_logged_admin_details["id"]."' AND `api_id`='$api_id' AND `product_id`='$product_id' AND `val_1`='$val_1'";
            mysqli_query($connection_server, $update_sql);
        }

        $_SESSION["product_purchase_response"] = "Product status updated successfully.";
        header("Location: SharedData.php");
        exit();
    }

    if(isset($_POST["update-price"])){
        $api_id_array = $_POST["api-id"];
        $product_id_array = $_POST["product-id"];
        $product_code_1_array = $_POST["product-code-1"];
        $product_days_array = $_POST["product-days"];
        $smart_price_array = $_POST["smart-price"];
        $agent_price_array = $_POST["agent-price"];
        $api_price_array = $_POST["api-price"];
        $account_level_table_name_arrays = array("sas_smart_parameter_values", "sas_agent_parameter_values", "sas_api_parameter_values");
        if(count($api_id_array) == count($product_id_array)){
            foreach($api_id_array as $index => $api_id){
                $api_id = $api_id_array[$index];
                $product_id = $product_id_array[$index];
                $product_code_1 = $product_code_1_array[$index];
                $product_days = $product_days_array[$index];
                $smart_price = $smart_price_array[$index];
                $agent_price = $agent_price_array[$index];
                $api_price = $api_price_array[$index];
                $get_selected_api_list = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_apis WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='$api_id'"));
                $select_api_list_with_api_type = mysqli_query($connection_server, "SELECT * FROM sas_apis WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_type='".$get_selected_api_list["api_type"]."'");
                if(mysqli_num_rows($select_api_list_with_api_type) > 0){
                    while($refined_api_id = mysqli_fetch_assoc($select_api_list_with_api_type)){
                        $smart_product_pricing_table = mysqli_query($connection_server, "SELECT * FROM ".$account_level_table_name_arrays[0]." WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_id='".$refined_api_id["id"]."' && product_id='$product_id' && val_1='$product_code_1'");                          
                        if(mysqli_num_rows($smart_product_pricing_table) == 0){
                            mysqli_query($connection_server, "INSERT INTO ".$account_level_table_name_arrays[0]." (vendor_id, api_id, product_id, val_1, val_2, val_3) VALUES ('".$get_logged_admin_details["id"]."', '".$refined_api_id["id"]."', '$product_id', '$product_code_1', '$smart_price', '$product_days')");
                        }else{
                            mysqli_query($connection_server, "UPDATE ".$account_level_table_name_arrays[0]." SET vendor_id='".$get_logged_admin_details["id"]."', api_id='".$refined_api_id["id"]."', product_id='$product_id', val_1='$product_code_1', val_2='$smart_price', val_3='$product_days' WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_id='".$refined_api_id["id"]."' && product_id='$product_id' && val_1='$product_code_1'");
                        }
                        
                        $agent_product_pricing_table = mysqli_query($connection_server, "SELECT * FROM ".$account_level_table_name_arrays[1]." WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_id='".$refined_api_id["id"]."' && product_id='$product_id' && val_1='$product_code_1'");                          
                        if(mysqli_num_rows($agent_product_pricing_table) == 0){
                            mysqli_query($connection_server, "INSERT INTO ".$account_level_table_name_arrays[1]." (vendor_id, api_id, product_id, val_1, val_2, val_3) VALUES ('".$get_logged_admin_details["id"]."', '".$refined_api_id["id"]."', '$product_id', '$product_code_1', '$agent_price', '$product_days')");
                        }else{
                            mysqli_query($connection_server, "UPDATE ".$account_level_table_name_arrays[1]." SET vendor_id='".$get_logged_admin_details["id"]."', api_id='".$refined_api_id["id"]."', product_id='$product_id', val_1='$product_code_1', val_2='$agent_price', val_3='$product_days' WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_id='".$refined_api_id["id"]."' && product_id='$product_id' && val_1='$product_code_1'");
                        }
                        
                        $api_product_pricing_table = mysqli_query($connection_server, "SELECT * FROM ".$account_level_table_name_arrays[2]." WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_id='".$refined_api_id["id"]."' && product_id='$product_id' && val_1='$product_code_1'");                            
                        if(mysqli_num_rows($api_product_pricing_table) == 0){
                            mysqli_query($connection_server, "INSERT INTO ".$account_level_table_name_arrays[2]." (vendor_id, api_id, product_id, val_1, val_2, val_3) VALUES ('".$get_logged_admin_details["id"]."', '".$refined_api_id["id"]."', '$product_id', '$product_code_1', '$api_price', '$product_days')");
                        }else{
                            mysqli_query($connection_server, "UPDATE ".$account_level_table_name_arrays[2]." SET vendor_id='".$get_logged_admin_details["id"]."', api_id='".$refined_api_id["id"]."', product_id='$product_id', val_1='$product_code_1', val_2='$api_price', val_3='$product_days' WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_id='".$refined_api_id["id"]."' && product_id='$product_id' && val_1='$product_code_1'");
                        }
                    }
                }
            }
            //Price Updated Successfully
            $json_response_array = array("desc" => "Price Updated Successfully");
            $json_response_encode = json_encode($json_response_array,true);
        }else{
            //Product Connection Error
            $json_response_array = array("desc" => "Product Connection Error");
            $json_response_encode = json_encode($json_response_array,true);
        }
        $json_response_decode = json_decode($json_response_encode,true);
        $_SESSION["product_purchase_response"] = $json_response_decode["desc"];
        header("Location: ".$_SERVER["REQUEST_URI"]);
    }
    
    $csv_price_level_array = [];
    $csv_price_level_array[] = "product_name,smart_level,agent_level,api_level,days";
    
?>
<!DOCTYPE html>
<head>
    <title>Shared Data API | <?php echo $get_all_super_admin_site_details["site_title"]; ?></title>
    <meta charset="UTF-8" />
    <meta name="description" content="<?php echo substr($get_all_super_admin_site_details["site_desc"], 0, 160); ?>" />
    <meta http-equiv="Content-Type" content="text/html; " />
    <meta name="theme-color" content="black" />
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="stylesheet" href="<?php echo $css_style_template_location; ?>">
    <link rel="stylesheet" href="/cssfile/bc-style.css">
    <meta name="author" content="BeeCodes Titan">
    <meta name="dc.creator" content="BeeCodes Titan">
    
  <!-- Vendor CSS Files -->
  <link href="../assets-2/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets-2/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets-2/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../assets-2/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="../assets-2/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="../assets-2/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="../assets-2/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="../assets-2/css/style.css" rel="stylesheet">

</head>
<body>
    <?php include("../func/bc-admin-header.php"); ?>
    <div class="pagetitle">
      <h1>SHARED DATA API</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Shared Data</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="col-12">
        <div class="card info-card px-5 py-5">
          <div class="row mb-3">
          
            <span style="user-select: auto;" class="h4 fw-bold">API SETTING</span><br>
            <form method="post" action="">
                <select style="text-align: center;" id="" name="api-id" onchange="getWebApikey(this);" class="form-control mb-1" required/>
                    <?php
                        //All SHARED DATA API
                        $get_api_lists = mysqli_query($connection_server, "SELECT * FROM sas_apis WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_type='shared-data'");
                        if(mysqli_num_rows($get_api_lists) >= 1){
                            echo '<option value="" default hidden selected>Choose API</option>';
                            while($api_details = mysqli_fetch_assoc($get_api_lists)){
                                if(empty(trim($api_details["api_key"]))){
                                    $apikey_status = "( Empty Key )";
                                }else{
                                    $apikey_status = "";
                                }
                                
                                echo '<option value="'.$api_details["id"].'" api-key="'.$api_details["api_key"].'" api-status="'.$api_details["status"].'">'.strtoupper($api_details["api_base_url"]).' '.$apikey_status.'</option>';
                            }
                        }else{
                            echo '<option value="" default hidden selected>No API</option>';
                        }
                    ?>
                </select><br/>
                <select style="text-align: center;" id="web-apikey-status" name="api-status" onchange="" class="form-control mb-1" required/>
                    <option value="" default hidden selected>Choose API Status</option>
                    <option value="1" >Enabled</option>
                    <option value="0" >Disabled</option>
                </select><br/>
                <input style="text-align: center;" id="web-apikey-input" name="api-key" onkeyup="" type="text" value="" placeholder="Api Key" class="form-control mb-1" required/><br/>
                <button name="update-key" type="submit" style="user-select: auto;" class="btn btn-primary col-12 mb-1" >
                    UPDATE KEY
                </button><br>
                <div style="text-align: center;" class="container">
                    <span id="product-status-span" class="h5" style="user-select: auto;"></span>
                </div><br/>
            </form>
          </div>
        </div>
        
        <div class="card info-card px-5 py-5">
          <div class="row mb-3">
            <span style="user-select: auto;" class="h4 fw-bold">PRODUCT INSTALLATION</span><br>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="select-all-checkbox">
                <label class="form-check-label" for="select-all-checkbox">
                    Select All
                </label>
            </div>
            <div id="product-image-container" style="text-align: center; user-select: auto;">
                <img alt="Airtel" id="airtel-lg" product-name-array="mtn,airtel,glo,9mobile" src="/asset/airtel.png" onclick="tickProduct(this, 'airtel', 'api-product-name', 'install-product', 'png');" class="col-2 rounded-5 border m-1  "/>
                <img alt="MTN" id="mtn-lg" product-name-array="mtn,airtel,glo,9mobile" src="/asset/mtn.png" onclick="tickProduct(this, 'mtn', 'api-product-name', 'install-product', 'png');" class="col-2 rounded-5 border m-1 "/>
                <img alt="Glo" id="glo-lg" product-name-array="mtn,airtel,glo,9mobile" src="/asset/glo.png" onclick="tickProduct(this, 'glo', 'api-product-name', 'install-product', 'png');" class="col-2 rounded-5 border m-1 "/>
                <img alt="9mobile" id="9mobile-lg" product-name-array="mtn,airtel,glo,9mobile" src="/asset/9mobile.png" onclick="tickProduct(this, '9mobile', 'api-product-name', 'install-product', 'png');" class="col-2 rounded-5 border m-1 "/>
            </div><br/>
            <form method="post" action="">
                <input id="api-product-name" name="product-name" type="text" placeholder="Product Name" hidden readonly required/>
                <select style="text-align: center;" id="" name="api-id" onchange="" class="form-control mb-1" required/>
                    <?php
                        //All SHARED DATA API
                        $get_api_lists = mysqli_query($connection_server, "SELECT * FROM sas_apis WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_type='shared-data'");
                        if(mysqli_num_rows($get_api_lists) >= 1){
                            echo '<option value="" default hidden selected>Choose API</option>';
                            while($api_details = mysqli_fetch_assoc($get_api_lists)){
                                if(empty(trim($api_details["api_key"]))){
                                    $apikey_status = "( Empty Key )";
                                }else{
                                    $apikey_status = "";
                                }
                                
                                echo '<option value="'.$api_details["id"].'">'.strtoupper($api_details["api_base_url"]).' '.$apikey_status.'</option>';
                            }
                        }else{
                            echo '<option value="" default hidden selected>No API</option>';
                        }
                    ?>
                </select><br/>
                <div style="text-align: center;" class="container">
                    <span id="user-status-span" class="h5" style="user-select: auto;">SHARED DATA STATUS</span>
                </div><br/>
                <select style="text-align: center;" id="" name="item-status" onchange="" class="form-control mb-1" required/>
                    <option value="" default hidden selected>Choose SHARED DATA Status</option>
                    <option value="1" >Enabled</option>
                    <option value="0" >Disabled</option>
                </select><br/>
                <button id="install-product" name="install-product" type="submit" style="pointer-events: none; user-select: auto;" class="btn btn-primary col-12 mb-1" >
                    INSTALL PRODUCT
                </button><br>
            </form>
          </div>
        </div>
        
        <div class="card info-card px-5 py-5">
          <div class="row mb-3">
            <span style="user-select: auto;" class="h4 fw-bold">INSTALLED SHARED DATA STATUS</span><br>
            <div style="user-select: auto; cursor: grab;" class="overflow-auto mt-1">
              <table style="" class="table table-responsive table-striped table-bordered" title="Horizontal Scroll: Shift + Mouse Scroll Button">
                  <thead class="thead-dark">
                    <tr>
                        <th>Product Name</th><th>API Route</th><th>Status</th><th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                        $item_name_array = array("mtn", "airtel", "glo", "9mobile");
                        foreach($item_name_array as $products){
                            $items_statement .= "product_name='$products' OR ";
                        }
                        $items_statement = "(".trim(rtrim($items_statement," OR ")).")";
                        $select_item_lists = mysqli_query($connection_server, "SELECT * FROM sas_shared_data_status WHERE vendor_id='".$get_logged_admin_details["id"]."' && $items_statement");
                        if(mysqli_num_rows($select_item_lists) >= 1){
                            while($list_details = mysqli_fetch_assoc($select_item_lists)){
                                $select_api_lists = mysqli_query($connection_server, "SELECT * FROM sas_apis WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='".$list_details["api_id"]."' && api_type='shared-data'");
                                if(mysqli_num_rows($select_api_lists) == 1){
                                    $api_details = mysqli_fetch_array($select_api_lists);
                                    $api_route_web = strtoupper($api_details["api_base_url"]);
                                }else{
                                    if(mysqli_num_rows($select_api_lists) == 0){
                                        $api_route_web = "Invalid API Website";
                                    }else{
                                        $api_route_web = "Duplicated API Website";
                                    }
                                }
                                if(strtolower(itemStatus($list_details["status"])) == "enabled"){
                                    $item_status = '<span style="color: green;">'.itemStatus($list_details["status"]).'</span>';
                                }else{
                                    $item_status = '<span style="color: grey;">'.itemStatus($list_details["status"]).'</span>';
                                }
                                
                                echo 
                                '<tr>
                                    <td>'.strtoupper(str_replace(["-","_"], " ", $list_details["product_name"])).'</td><td>'.$api_route_web.'</td><td>'.$item_status.'</td>
                                    <td>'.render_action_buttons($list_details["product_name"], "shared-data", $list_details["status"]).'</td>
                                </tr>';
                            }
                        }
                    ?>
                  </tbody>
                </table>
            </div>
          </div>
        </div><br/>
        
        <div class="card info-card px-5 py-5">
          <div class="row mb-3">
            <span style="user-select: auto;" class="h4 fw-bold">SHARED DATA DISCOUNT</span><br>
            <div style="user-select: auto; cursor: grab;" class="overflow-auto mt-1">
              <table style="" class="table table-responsive table-striped table-bordered" title="Horizontal Scroll: Shift + Mouse Scroll Button">
                <thead class="thead-dark">
                  <tr>
                      <th>Digit</th><th>Mode</th><th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                    <input style="text-align: center;" id="price-upgrade-input" name="" onkeyup="" type="text" value="" placeholder="Amount/Percent" class="form-control mb-1" required/>
                  </td>
                  <td>
                    <select style="text-align: center;" id="price-upgrade-type" name="" onchange="" class="form-control mb-1" required/>
                        <option value="" default hidden selected>Choose Update Type</option>
                        <option value="amount+" >Amount Increase</option>
                        <option value="amount-" >Amount Decrease</option>
                        <option value="percent+" >Percentage Increase</option>
                        <option value="percent-" >Percentage Decrease</option>
                    </select>
                  </td>
                  <td>
                    <button onclick="upgradeePriceDiscount();" type="button" style="user-select: auto;" class="btn btn-primary col-12 mb-1" >
                      SAVE
                    </button>
                  </td>
                  </tr>
                </tbody>
              </table>
                <form method="post" action="">
                  <table style="" class="table table-responsive table-striped table-bordered" title="Horizontal Scroll: Shift + Mouse Scroll Button">
                    <thead class="thead-dark">
                      <tr>
                          <th>Product Name</th><th>Smart Earner</th><th>Agent Vendor</th><th>API Vendor</th><th>Days</th><th>Status</th><th>Action</th>
                      </tr>
                    </thead>
                        
                        <?php
                            $item_name_array_2 = array("mtn", "airtel", "glo", "9mobile");
                            foreach($item_name_array_2 as $products){
                                $get_item_status_details = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_shared_data_status WHERE vendor_id='".$get_logged_admin_details["id"]."' && product_name='$products'"));
                                $get_api_lists = mysqli_query($connection_server, "SELECT * FROM sas_apis WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='".$get_item_status_details["api_id"]."' && api_type='shared-data'");
                                $account_level_table_name_arrays = array(1 => "sas_smart_parameter_values", 2 => "sas_agent_parameter_values", 3 => "sas_api_parameter_values");
                                $product_table = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_products WHERE vendor_id='".$get_logged_admin_details["id"]."' && product_name='$products' LIMIT 1"));
                                $product_smart_table = mysqli_query($connection_server, "SELECT * FROM ".$account_level_table_name_arrays[1]." WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_id='".$get_item_status_details["api_id"]."' && product_id='".$product_table["id"]."'");                         
                                $product_agent_table = mysqli_query($connection_server, "SELECT * FROM ".$account_level_table_name_arrays[2]." WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_id='".$get_item_status_details["api_id"]."' && product_id='".$product_table["id"]."'");                         
                                $product_api_table = mysqli_query($connection_server, "SELECT * FROM ".$account_level_table_name_arrays[3]." WHERE vendor_id='".$get_logged_admin_details["id"]."' && api_id='".$get_item_status_details["api_id"]."' && product_id='".$product_table["id"]."'");                           
                                
                                if((mysqli_num_rows($get_api_lists) == 1) && (mysqli_num_rows($product_smart_table) > 0) && (mysqli_num_rows($product_agent_table) > 0) && (mysqli_num_rows($product_api_table) > 0)){
                                    while(($product_smart_details = mysqli_fetch_assoc($product_smart_table)) && ($product_agent_details = mysqli_fetch_assoc($product_agent_table)) && ($product_api_details = mysqli_fetch_assoc($product_api_table))){
                                        $status_badge = $product_smart_details['status'] == 1 ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-danger">Disabled</span>';
                                        $toggle_button_text = $product_smart_details['status'] == 1 ? 'Disable' : 'Enable';
                                        $toggle_button_class = $product_smart_details['status'] == 1 ? 'btn-warning' : 'btn-success';
                                        $toggle_link = "SharedData.php?action=toggle_status&api_id={$product_smart_details['api_id']}&product_id={$product_smart_details['product_id']}&val_1=" . urlencode($product_smart_details['val_1']) . "&current_status={$product_smart_details['status']}";

                                        echo 
                                            '<tr style="background-color: transparent !important;">
                                                <td style="">
                                                    '.strtoupper($products." SHARED DATA ".str_replace(["_","-"]," ",$product_smart_details["val_1"])).'
                                                    <input style="text-align: center;" name="api-id[]" type="text" value="'.$product_smart_details["api_id"].'" hidden readonly required/>
                                                    <input style="text-align: center;" name="product-id[]" type="text" value="'.$product_smart_details["product_id"].'" hidden readonly required/>
                                                    <input style="text-align: center;" name="product-code-1[]" type="text" value="'.$product_smart_details["val_1"].'" hidden readonly required/>
                                                </td>
                                                <td>
                                                    <input style="text-align: center;" id="'.strtolower(trim($products)).'_shared_data_'.str_replace(["_","-"],"_",$product_smart_details["val_1"]).'_smart_level" name="smart-price[]" type="text" value="'.$product_smart_details["val_2"].'" placeholder="Price" pattern="[0-9.]{1,}" title="Amount Must Be A Digit" class="product-price form-control mb-1" required/>
                                                </td>
                                                <td>
                                                    <input style="text-align: center;" id="'.strtolower(trim($products)).'_shared_data_'.str_replace(["_","-"],"_",$product_smart_details["val_1"]).'_agent_level" name="agent-price[]" type="text" value="'.$product_agent_details["val_2"].'" placeholder="Price" pattern="[0-9.]{1,}" title="Amount Must Be A Digit" class="product-price form-control mb-1" required/>
                                                </td>
                                                <td>
                                                    <input style="text-align: center;" id="'.strtolower(trim($products)).'_shared_data_'.str_replace(["_","-"],"_",$product_smart_details["val_1"]).'_api_level" name="api-price[]" type="text" value="'.$product_api_details["val_2"].'" placeholder="Price" pattern="[0-9.]{1,}" title="Amount Must Be A Digit" class="product-price form-control mb-1" required/>
                                                </td>
                                                <td>
                                                    <input style="text-align: center;" id="'.strtolower(trim($products)).'_shared_data_'.str_replace(["_","-"],"_",$product_smart_details["val_1"]).'_days" name="product-days[]" type="text" value="'.$product_api_details["val_3"].'" placeholder="Days" pattern="[0-9.]{1,}" title="Days Must Be A Digit" class="form-control mb-1" required/>
                                                </td>
                                                <td>'.$status_badge.'</td>
                                                <td><a href="'.$toggle_link.'" class="btn btn-sm '.$toggle_button_class.'">'.$toggle_button_text.'</a></td>
                                            </tr>'; 
                                            $csv_price_level_array[] = strtolower(trim($products)).'_shared_data_'.str_replace(["_","-"],"_",$product_smart_details["val_1"]).",".$product_smart_details["val_2"].",".$product_agent_details["val_2"].",".$product_api_details["val_2"].",".$product_api_details["val_3"];
                                    }
                                }else{
                                    
                                }
                            }
                        ?>
                      </tbody>
                    </table>
                    <button id="" name="update-price" type="submit" style="user-select: auto;" class="btn btn-primary col-12 mb-1" >
                        UPDATE PRICE
                    </button><br>
                </form>
            </div>
          </div>
        </div><br/>
        
        <div class="card info-card px-5 py-5">
          <div class="row mb-3">
            <span style="user-select: auto;" class="h4 fw-bold">FILL PRICE TABLE USING CSV</span><br>
            <div style="user-select: auto; cursor: grab;" class="container col-12 border rounded-2 px-5 py-3 lh-lg py-5">
                <form method="post" enctype="multipart/form-data" action="">
                    <input style="text-align: center;" id="csv-chooser" type="file" accept="" class="form-control mb-1" required/><br/>
                    <button onclick="getCSVDetails('5');" type="button" style="user-select: auto;" class="btn btn-primary col-12 mb-1" >
                        PROCESS
                    </button>
                </form>
            </div><br/>
            
            <a onclick='downloadFile(`<?php echo implode("\n",$csv_price_level_array); ?>`, "shared-data.csv");' style="text-decoration: underline; user-select: auto;" class="h5 text-danger mt-3">Download Price CSV</a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php include("../func/bc-admin-footer.php"); ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function () {
                    // We need to pass a reference to one of the images so the function can find the container
                    const anyImage = document.querySelector("img[product-name-array]");
                    if (anyImage) {
                        tickProduct(this, 'all', 'api-product-name', 'install-product', 'png');
                    }
                });
            }
        });
    </script>
</body>
</html>