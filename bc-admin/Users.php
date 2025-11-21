<?php session_start();
    include("../func/bc-admin-config.php");

    if(isset($_POST["import-users"])){
        if(isset($_FILES['user-csv']) && $_FILES['user-csv']['error'] == 0){
            $file_tmp = $_FILES['user-csv']['tmp_name'];

            $file = fopen($file_tmp, "r");
            fgetcsv($file); // Skip header row

            while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                $fullname = explode(" ", $column[1]);
                $firstname = $fullname[0] ?? '';
                $lastname = $fullname[1] ?? '';
                $othername = $fullname[2] ?? '';

                $username = $column[2];
                $level = array_search(strtolower($column[3]), array_map('strtolower', array(1 => "Smart Earner", 2 => "Agent Vendor", 3 => "API Vendor")));
                $balance = $column[4];
                $phone = $column[5];
                $address = $column[6];
                $referral_username = $column[7];
                $api_status = (strtolower($column[8]) == 'enabled') ? 1 : 0;
                $api_key = $column[9];
                $security_answer = $column[10];
                $reg_date = date("Y-m-d H:i:s", strtotime($column[11]));

                $referral_id = 0;
                if($referral_username != "Not Referred"){
                    $get_referral = mysqli_fetch_array(mysqli_query($connection_server, "SELECT id FROM sas_users WHERE username = '$referral_username' AND vendor_id = '".$get_logged_admin_details["id"]."'"));
                    if($get_referral){
                        $referral_id = $get_referral['id'];
                    }
                }

                // For simplicity, let's assume email is the same as username and set a default password
                $email = $username;
                $password = password_hash("password123", PASSWORD_DEFAULT);

                $firstname = mysqli_real_escape_string($connection_server, $firstname);
                $lastname = mysqli_real_escape_string($connection_server, $lastname);
                $othername = mysqli_real_escape_string($connection_server, $othername);
                $username = mysqli_real_escape_string($connection_server, $username);
                $email = mysqli_real_escape_string($connection_server, $email);
                $level = mysqli_real_escape_string($connection_server, $level);
                $balance = mysqli_real_escape_string($connection_server, $balance);
                $phone = mysqli_real_escape_string($connection_server, $phone);
                $address = mysqli_real_escape_string($connection_server, $address);
                $api_key = mysqli_real_escape_string($connection_server, $api_key);
                $security_answer = mysqli_real_escape_string($connection_server, $security_answer);

                $sql = "INSERT INTO sas_users (vendor_id, firstname, lastname, othername, username, email, password, account_level, balance, phone_number, home_address, referral_id, api_status, api_key, security_answer, reg_date, status) VALUES ('".$get_logged_admin_details["id"]."', '$firstname', '$lastname', '$othername', '$username', '$email', '$password', '$level', '$balance', '$phone', '$address', '$referral_id', '$api_status', '$api_key', '$security_answer', '$reg_date', '1')";
                mysqli_query($connection_server, $sql);
            }

            fclose($file);
            $_SESSION["product_purchase_response"] = "Users imported successfully.";
        }else{
            $_SESSION["product_purchase_response"] = "Error uploading file.";
        }
        header("Location: ".$_SERVER["REQUEST_URI"]);
        exit();
    }

    if(isset($_POST["export-users"])){
        $status = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["status"])));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=users.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('S/N', 'Fullname', 'Username ID', 'Level', 'Balance', 'Phone number', 'Address', 'Referral', 'API Status', 'APIKey', 'Security Answer', 'Reg Date'));

        $sql = "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."'";
        if($status != 'all'){
            $sql .= " && status='$status'";
        }
        $result = mysqli_query($connection_server, $sql);
        $sn = 1;
        while($row = mysqli_fetch_assoc($result)){
            $fullname = $row['firstname'] . ' ' . $row['lastname'] . ' ' . $row['othername'];
            $referral_username = "Not Referred";
            if(!empty($row["referral_id"]) && is_numeric($row["referral_id"])){
                $get_user_referral_details = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='".$row["referral_id"]."'"));
                $referral_username = $get_user_referral_details["username"];
            }

            $api_status = ($row['api_status'] == 1) ? 'Enabled' : 'Disabled';

            fputcsv($output, array(
                $sn++,
                $fullname,
                $row['username'],
                accountLevel($row['account_level']),
                $row['balance'],
                $row['phone_number'],
                $row['home_address'],
                $referral_username,
                $api_status,
                $row['api_key'],
                $row['security_answer'],
                formDate($row['reg_date'])
            ));
        }
        fclose($output);
        exit();
    }
    
    if(isset($_GET["account-status"])){
        $status = mysqli_real_escape_string($connection_server, trim(strip_tags($_GET["account-status"])));
        $account_user = mysqli_real_escape_string($connection_server, trim(strip_tags($_GET["account-username"])));
        $statusArray = array(1, 2, 3);
        if(is_numeric($status)){
            if(in_array($status, $statusArray)){
            	$send_mail_to_user = false;
            	$get_user_details = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && username='$account_user' LIMIT 1"));
            	
                if($status == 1){
                    $alter_user_account_details = alterUser($account_user, "status", $status);
                    if($alter_user_account_details == "success"){
                    	$send_mail_to_user = true;
                        $json_response_array = array("desc" => ucwords($account_user." account activated successfully"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }else{
                        $json_response_array = array("desc" => ucwords($get_payment_order["username"]." account cannot be activated"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }
                }
                
                if($status == 2){
                    $alter_user_account_details = alterUser($account_user, "status", $status);
                    if($alter_user_account_details == "success"){
                    	$send_mail_to_user = true;
                        $json_response_array = array("desc" => ucwords($account_user." account deactivated successfully"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }else{
                        $json_response_array = array("desc" => ucwords($get_payment_order["username"]." account cannot be deactivated"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }
                }

                if($status == 3){
                    $alter_user_account_details = alterUser($account_user, "status", $status);
                    if($alter_user_account_details == "success"){
                    	$send_mail_to_user = true;
                        $json_response_array = array("desc" => ucwords($account_user." account deleted successfully"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }else{
                        $json_response_array = array("desc" => ucwords($get_payment_order["username"]." account cannot be deleted"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }
                }
                
                if($send_mail_to_user == true){
                	// Email Beginning
                	$log_template_encoded_text_array = array("{firstname}" => $get_user_details["firstname"], "{lastname}" => $get_user_details["lastname"], "{account_status}" => accountStatus($status));
                	$raw_log_template_subject = getUserEmailTemplate('user-account-status','subject');
                	$raw_log_template_body = getUserEmailTemplate('user-account-status','body');
                	foreach($log_template_encoded_text_array as $array_key => $array_val){
                		$raw_log_template_subject = str_replace($array_key, $array_val, $raw_log_template_subject);
                		$raw_log_template_body = str_replace($array_key, $array_val, $raw_log_template_body);
                	}
                	sendVendorEmail($get_user_details["email"], $raw_log_template_subject, $raw_log_template_body);
                	// Email End
                }
            }else{
                //Invalid Status Code
                $json_response_array = array("desc" => "Invalid Status Code");
                $json_response_encode = json_encode($json_response_array,true);
            }
        }else{
            //Non-numeric string
            $json_response_array = array("desc" => "Non-numeric string");
            $json_response_encode = json_encode($json_response_array,true);
        }
        $json_response_decode = json_decode($json_response_encode,true);
        $_SESSION["product_purchase_response"] = $json_response_decode["desc"];
        header("Location: /bc-admin/Users.php");
    }

    if(isset($_GET["account-api-status"])){
        $status = mysqli_real_escape_string($connection_server, trim(strip_tags($_GET["account-api-status"])));
        $account_user = mysqli_real_escape_string($connection_server, trim(strip_tags($_GET["account-username"])));
        $statusArray = array(1, 2);
        $statusArrayValue = array(1 => "Activated", 2 => "Deactivated");
        
        if(is_numeric($status)){
            if(in_array($status, $statusArray)){
            	$send_mail_to_user = false;
            	$get_user_details = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && username='$account_user' LIMIT 1"));
            	
                if($status == 1){
                    $alter_user_account_details = alterUser($account_user, "api_status", $status);
                    if($alter_user_account_details == "success"){
                    	$send_mail_to_user = true;
                        $json_response_array = array("desc" => ucwords($account_user." account status activated successfully"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }else{
                        $json_response_array = array("desc" => ucwords($get_payment_order["username"]." account status cannot be activated"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }
                }
                
                if($status == 2){
                    $alter_user_account_details = alterUser($account_user, "api_status", $status);
                    if($alter_user_account_details == "success"){
                    	$send_mail_to_user = true;
                        $json_response_array = array("desc" => ucwords($account_user." account status deactivated successfully"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }else{
                        $json_response_array = array("desc" => ucwords($get_payment_order["username"]." account status cannot be deactivated"));
                        $json_response_encode = json_encode($json_response_array,true);
                    }
                }
                
                if($send_mail_to_user == true){
                	// Email Beginning
                	$log_template_encoded_text_array = array("{firstname}" => $get_user_details["firstname"], "{lastname}" => $get_user_details["lastname"], "{api_status}" => $statusArrayValue[$status]);
                	$raw_log_template_subject = getUserEmailTemplate('user-api-status','subject');
                	$raw_log_template_body = getUserEmailTemplate('user-api-status','body');
                	foreach($log_template_encoded_text_array as $array_key => $array_val){
                		$raw_log_template_subject = str_replace($array_key, $array_val, $raw_log_template_subject);
                		$raw_log_template_body = str_replace($array_key, $array_val, $raw_log_template_body);
                	}
                	sendVendorEmail($get_user_details["email"], $raw_log_template_subject, $raw_log_template_body);
                	// Email End
                }
            }else{
                //Invalid Status Code
                $json_response_array = array("desc" => "Invalid Status Code");
                $json_response_encode = json_encode($json_response_array,true);
            }
        }else{
            //Non-numeric string
            $json_response_array = array("desc" => "Non-numeric string");
            $json_response_encode = json_encode($json_response_array,true);
        }
        $json_response_decode = json_decode($json_response_encode,true);
        $_SESSION["product_purchase_response"] = $json_response_decode["desc"];
        header("Location: /bc-admin/Users.php");
    }

    if(isset($_GET["account-log"])){
        $account_log = mysqli_real_escape_string($connection_server, trim(strip_tags($_GET["account-log"])));
        if(is_numeric($account_log)){
            if($account_log >= 1){
			    $get_logged_user_query = mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='$account_log'");
                if(mysqli_num_rows($get_logged_user_query) == 1){
                    $get_user_info = mysqli_fetch_array($get_logged_user_query);
                    $_SESSION["user_session"] = $get_user_info["username"];
                    $_SESSION["admin_to_user_redirect"] = true;
                }else{
                    if(mysqli_num_rows($get_logged_user_query) < 1){
                        $json_response_array = array("desc" => "Error: User not Exists");
                        $json_response_encode = json_encode($json_response_array,true);
                    }else{
                        if(mysqli_num_rows($get_logged_user_query) > 1){
                            $json_response_array = array("desc" => "Error: Duplicate User Accounts");
                            $json_response_encode = json_encode($json_response_array,true);
                        }
                    }
                }
            }else{
                //Invalid Account ID
                $json_response_array = array("desc" => "Invalid Account ID");
                $json_response_encode = json_encode($json_response_array,true);
            }
        }else{
            //Non-numeric string
            $json_response_array = array("desc" => "Non-numeric string");
            $json_response_encode = json_encode($json_response_array,true);
        }
        $json_response_decode = json_decode($json_response_encode,true);
        $_SESSION["product_purchase_response"] = $json_response_decode["desc"];
        header("Location: /bc-admin/Users.php");
    }
    
?>
<!DOCTYPE html>
<head>
    <title>Users | <?php echo $get_all_super_admin_site_details["site_title"]; ?></title>
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

    <?php
    	//Redirect To User Page
    	if(isset($_SESSION["admin_to_user_redirect"]) && ($_SESSION["admin_to_user_redirect"] == true)){
    		echo '<script>	window.onload = function(){	window.open("'.$web_http_host.'/web/Dashboard.php","_blank");	}	</script>';
    	}
    ?>
</head>
<body>
    <?php include("../func/bc-admin-header.php"); ?>
    
    
    	<div class="pagetitle">
      <h1>USERS</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Users</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="col-12">
        <?php
            
            if(!isset($_GET["searchq"]) && isset($_GET["page"]) && !empty(trim(strip_tags($_GET["page"]))) && is_numeric(trim(strip_tags($_GET["page"]))) && (trim(strip_tags($_GET["page"])) >= 1)){
                $page_num = mysqli_real_escape_string($connection_server, trim(strip_tags($_GET["page"])));
                $offset_statement = " OFFSET ".((10 * $page_num) - 10);
            }else{
                $offset_statement = "";
            }
            
            if(isset($_GET["searchq"]) && !empty(trim(strip_tags($_GET["searchq"])))){
                $search_statement = " && (email LIKE '%".trim(strip_tags($_GET["searchq"]))."%' OR phone_number LIKE '%".trim(strip_tags($_GET["searchq"]))."%' OR username LIKE '%".trim(strip_tags($_GET["searchq"]))."%' OR firstname LIKE '%".trim(strip_tags($_GET["searchq"]))."%' OR lastname LIKE '%".trim(strip_tags($_GET["searchq"]))."%' OR othername LIKE '%".trim(strip_tags($_GET["searchq"]))."%')";
                $search_parameter = "searchq=".trim(strip_tags($_GET["searchq"]))."&&";
            }else{
                $search_statement = "";
                $search_parameter = "";
            }
            $get_active_user_details = mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && status='1' $search_statement ORDER BY reg_date DESC LIMIT 50 $offset_statement");
            $get_inactive_user_details = mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && status='2' $search_statement ORDER BY reg_date DESC LIMIT 10 $offset_statement");
            $get_deleted_user_details = mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && status='3' $search_statement ORDER BY reg_date DESC LIMIT 10 $offset_statement");
            
        ?>
        <div class="card info-card px-5 py-5">
                <form method="get" action="Users.php" class="m-margin-tp-1 s-margin-tp-1">
                    <input style="user-select: auto;" name="searchq" type="text" value="<?php echo trim(strip_tags($_GET["searchq"])); ?>" placeholder="Email, Username, Phone number, Firstname e.t.c" class="form-control mb-1" />
                    <button style="user-select: auto;" type="submit" class="btn btn-success d-inline col-12 col-lg-auto my-2" >
                        <i class="bi bi-search"></i> Search
                    </button>
                </form>
            </div>

            <div class="card info-card px-5 py-5">
                <form method="post" action="Users.php" enctype="multipart/form-data" class="m-margin-tp-1 s-margin-tp-1">
                    <input type="file" name="user-csv" class="form-control mb-1" accept=".csv" required>
                    <button name="import-users" type="submit" class="btn btn-primary d-inline col-12 col-lg-auto my-2">
                        <i class="bi bi-box-arrow-down"></i> Import Users
                    </button>
                </form>
            </div>

            <div class="card info-card px-5 py-5">
                <form method="post" action="Users.php" class="m-margin-tp-1 s-margin-tp-1">
                    <select name="status" class="form-control mb-1">
                        <option value="all">All Users</option>
                        <option value="1">Active Users</option>
                        <option value="2">Blocked Users</option>
                        <option value="3">Deleted Users</option>
                    </select>
                    <button name="export-users" type="submit" class="btn btn-primary d-inline col-12 col-lg-auto my-2">
                        <i class="bi bi-box-arrow-up"></i> Export Users
                    </button>
                </form>
            </div>

            <span style="user-select: auto;" class="fw-bold h4 mb-1">ACTIVE ACCOUNT (<?php echo mysqli_num_rows($get_active_user_details); ?>)</span><br>
      <div style="user-select: auto; cursor: grab;" class="overflow-auto">
        <table style="" class="table table-responsive table-striped table-bordered" title="Horizontal Scroll: Shift + Mouse Scroll Button">
            <thead class="thead-dark">
              <tr>
                  <th>S/N</th><th>Fullname</th><th>Username ID</th><th>Level</th><th>Balance</th><th>Phone number</th><th>Address</th><th>Referral</th><th>API Status</th><th>APIKey</th><th>Security Answer</th><th>Reg Date</th><th>Last Login</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
                    <?php
                    if(mysqli_num_rows($get_active_user_details) >= 1){
                        while($user_details = mysqli_fetch_assoc($get_active_user_details)){
                            $transaction_type = ucwords($user_details["type_alternative"]);
                            $countTransaction += 1;
                            $block_user_account = '<span onclick="updateUserAccountStatus(`2`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: red;" class=""><i title="Block Account" style="" class="bi bi-ban" ></i></span>';
                            $delete_user_account = '<span onclick="updateUserAccountStatus(`3`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: green;" class=""><i title="Delete Account" style="" class="bi bi-trash-fill" ></i></span>';
                            $login_user_account = '<span onclick="loginUserAccount(`'.$user_details["id"].'`, `'.$user_details["username"].'`);" style="text-decoration: underline; color: orange;" class=""><i title="Login Account" style="" class="bi bi-box-arrow-in-right" ></i></span>';
                            $all_user_account_action = $block_user_account." ".$delete_user_account." ".$login_user_account;
							
							//$user_bvn = '<span onclick="copyText(`BVN copied successfully`,`'.$user_details["bvn"].'`);" style="text-decoration: underline; color: red;" class="a-cursor"><img title="Copy BVN" src="'.$web_http_host.'/asset/copy-icon.png" style="width: 12px; padding: 6px 6px 6px 6px;" class="a-cursor bg-1 m-margin-lt-1 s-margin-lt-1" /></span>';
							//$user_nin = '<span onclick="copyText(`NIN copied successfully`,`'.$user_details["nin"].'`);" style="text-decoration: underline; color: red;" class="a-cursor"><img title="Copy NIN" src="'.$web_http_host.'/asset/copy-icon.png" style="width: 12px; padding: 6px 6px 6px 6px;" class="a-cursor bg-1 m-margin-lt-1 s-margin-lt-1" /></span>';
							$user_apikey = '<span onclick="copyText(`APIkey copied successfully`,`'.$user_details["api_key"].'`);" style="text-decoration: underline; color: red;" class=""><i title="Login Account" style="" class="bi bi-copy" ></i></span>';
							
                            $username_with_link = ucwords($user_details["username"]).' <span onclick="customJsRedirect(`/bc-admin/UserEdit.php?userID='.$user_details["id"].'`, `Are you sure you want to edit '.strtoupper($user_details["username"]).' account`);" style="text-decoration: underline; color: green;" class=""><i title="Edit Account" style="" class="bi bi-pencil-square" ></i></span>';
                            $user_level_with_upgrade_link = accountLevel($user_details["account_level"]).' <span onclick="customJsRedirect(`/bc-admin/UserUpgrade.php?userID='.$user_details["id"].'`, `Are you sure you want to Upgrade '.strtoupper($user_details["username"]).' account`);" style="text-decoration: underline; color: green;" class=""><i title="Upgrade Account" style="" class="bi bi-arrow-down-up" ></i></span>';
                            
                            if(!empty($user_details["referral_id"]) && is_numeric($user_details["referral_id"])){
                                $get_user_referral_details = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='".$user_details["referral_id"]."'"));
                            }else{
                                $get_user_referral_details = array("username" => "Not Referred");
                            }

                            $account_api_status = array(1 => "enabled", 2 => "disabled");
                            if(in_array($user_details["api_status"], array_keys($account_api_status))){
                                if($account_api_status[$user_details["api_status"]] == "enabled"){
                                    $api_status_details = ucwords($account_api_status[$user_details["api_status"]]).' <i onclick="updateUserAccountAPIStatus(`2`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: red;" class="bi bi-person-dash-fill"></i><span>';
                                }else{
                                    $api_status_details = ucwords($account_api_status[$user_details["api_status"]]).' <i onclick="updateUserAccountAPIStatus(`1`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: green;" class="bi bi-person-check-fill"></i><span>';
                                }
                            }else{
                                $api_status_details = '<span style="color: red;">Invalid Status Code</span>';
                            }

                            echo 
                            '<tr>
                                <td>'.$countTransaction.'</td><td>'.$user_details["firstname"]." ".$user_details["lastname"].checkIfEmpty(ucwords($user_details["othername"]),", ", "").'</td><td style="user-select: auto;">'.$username_with_link.'</td><td>'.$user_level_with_upgrade_link.'</td><td>'.toDecimal($user_details["balance"], 2).'</td><td>'.$user_details["phone_number"].'</td><td>'.$user_details["home_address"].'</td><td style="user-select: auto;">'.$get_user_referral_details["username"].'</td><td>'.$api_status_details.'</td><td style="user-select: auto;">'.$user_apikey.'</td><td style="user-select: auto;">'.$user_details["security_answer"].'</td><td>'.formDate($user_details["reg_date"]).'</td><td>'.formDate($user_details["last_login"]).'</td><td class="">'.$all_user_account_action.'</td>
                            </tr>';
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div><br/>

            <span style="user-select: auto;" class="fw-bold h4 mb-1">BLOCKED ACCOUNT (<?php echo mysqli_num_rows($get_inactive_user_details); ?>)</span><br>
  
      <div style="user-select: auto; cursor: grab;" class="overflow-auto">
        <table style="" class="table table-responsive table-striped table-bordered" title="Horizontal Scroll: Shift + Mouse Scroll Button">
            <thead class="thead-dark">
              <tr>
                  <th>S/N</th><th>Fullname</th><th>Username ID</th><th>Level</th><th>Balance</th><th>Phone number</th><th>Address</th><th>Referral</th><th>API Status</th><th>APIKey</th><th>Security Answer</th><th>Reg Date</th><th>Last Login</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
                    <?php
                    if(mysqli_num_rows($get_inactive_user_details) >= 1){
                        while($user_details = mysqli_fetch_assoc($get_inactive_user_details)){
                            $transaction_type = ucwords($user_details["type_alternative"]);
                            $countTransaction += 1;
                            $activate_user_account = '<span onclick="updateUserAccountStatus(`1`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: red;" class=""><i title="Re-activate Account" style="" class="bi bi-check-circle" ></i></span>';
                            $delete_user_account = '<span onclick="updateUserAccountStatus(`3`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: green;" class=""><i title="Delete Account" style="" class="bi bi-trash-fill" ></i></span>';
                            $login_user_account = '<span onclick="loginUserAccount(`'.$user_details["id"].'`, `'.$user_details["username"].'`);" style="text-decoration: underline; color: orange;" class=""><i title="Login Account" style="" class="bi bi-box-arrow-in-right" ></i></span>';
                            $all_user_account_action = $activate_user_account." ".$delete_user_account." ".$login_user_account;
                            $user_apikey = '<span onclick="copyText(`APIkey copied successfully`,`'.$user_details["api_key"].'`);" style="text-decoration: underline; color: red;" class=""><i title="Copy APIkey" style="" class="bi bi-copy" ></i></span>';
							
                            $username_with_link = ucwords($user_details["username"]).' <span onclick="customJsRedirect(`/bc-admin/UserEdit.php?userID='.$user_details["id"].'`, `Are you sure you want to edit '.strtoupper($user_details["username"]).' account`);" style="text-decoration: underline; color: green;" class=""><i title="Edit Account" style="" class="bi bi-pencil-square" ></i></span>';
                            $user_level_with_upgrade_link = accountLevel($user_details["account_level"]).' <span onclick="customJsRedirect(`/bc-admin/UserUpgrade.php?userID='.$user_details["id"].'`, `Are you sure you want to Upgrade '.strtoupper($user_details["username"]).' account`);" style="text-decoration: underline; color: green;" class=""><i title="Upgrade Account" style="" class="bi bi-arrow-down-up" ></i></span>';
                            
                            if(!empty($user_details["referral_id"]) && is_numeric($user_details["referral_id"])){
                                $get_user_referral_details = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='".$user_details["referral_id"]."'"));
                            }else{
                                $get_user_referral_details = array("username" => "Not Referred");
                            }

                            $account_api_status = array(1 => "enabled", 2 => "disabled");
                            if(in_array($user_details["api_status"], array_keys($account_api_status))){
                                if($account_api_status[$user_details["api_status"]] == "enabled"){
                                    $api_status_details = ucwords($account_api_status[$user_details["api_status"]]).' <i onclick="updateUserAccountAPIStatus(`2`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: red;" class="bi bi-person-dash-fill"></i><span>';
                                }else{
                                    $api_status_details = ucwords($account_api_status[$user_details["api_status"]]).' <span onclick="updateUserAccountAPIStatus(`1`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: green;"class="bi bi-person-check-fill"></i><span>';
                                }
                            }else{
                                $api_status_details = '<span style="color: red;">Invalid Status Code</span>';
                            }

                            echo 
                            '<tr>
                                <td>'.$countTransaction.'</td><td>'.$user_details["firstname"]." ".$user_details["lastname"].checkIfEmpty(ucwords($user_details["othername"]),", ", "").'</td><td style="user-select: auto;">'.$username_with_link.'</td><td>'.$user_level_with_upgrade_link.'</td><td>'.toDecimal($user_details["balance"], 2).'</td><td>'.$user_details["phone_number"].'</td><td>'.$user_details["home_address"].'</td><td style="user-select: auto;">'.$get_user_referral_details["username"].'</td><td>'.$api_status_details.'</td><td style="user-select: auto;">'.$user_apikey.'</td><td style="user-select: auto;">'.$user_details["security_answer"].'</td><td>'.formDate($user_details["reg_date"]).'</td><td>'.formDate($user_details["last_login"]).'</td><td class="">'.$all_user_account_action.'</td>
                            </tr>';
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div><br/>

            <span style="user-select: auto;" class="fw-bold h4 mb-1">DELETED ACCOUNT (<?php echo mysqli_num_rows($get_deleted_user_details); ?>)</span><br>
      <div style="user-select: auto; cursor: grab;" class="overflow-auto">
        <table style="" class="table table-responsive table-striped table-bordered" title="Horizontal Scroll: Shift + Mouse Scroll Button">
            <thead class="thead-dark">
              <tr>
                  <th>S/N</th><th>Fullname</th><th>Username ID</th><th>Level</th><th>Balance</th><th>Phone number</th><th>Address</th><th>Referral</th><th>API Status</th><th>APIKey</th><th>Security Answer</th><th>Reg Date</th><th>Last Login</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
                    <?php
                    if(mysqli_num_rows($get_deleted_user_details) >= 1){
                        while($user_details = mysqli_fetch_assoc($get_deleted_user_details)){
                            $transaction_type = ucwords($user_details["type_alternative"]);
                            $countTransaction += 1;
                            $activate_user_account = '<span onclick="updateUserAccountStatus(`1`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: red;"  class=""><i title="Re-activate Account" style="" class="bi bi-pencil-square" ></i></span>';
                            $login_user_account = '<span onclick="loginUserAccount(`'.$user_details["id"].'`, `'.$user_details["username"].'`);" style="text-decoration: underline; color: orange;"  class=""><i title="Login Account" style="" class="bi bi-box-arrow-in-right" ></i></span>';
                            $all_user_account_action = $activate_user_account." ".$login_user_account;
                            $user_apikey = '<span onclick="copyText(`APIkey copied successfully`,`'.$user_details["api_key"].'`);" style="text-decoration: underline; color: red;"  class=""><i title="Copy APIkey" style="" class="bi bi-copy" ></i></span>';
							
                            $username_with_link = ucwords($user_details["username"]).' <span onclick="customJsRedirect(`/bc-admin/UserEdit.php?userID='.$user_details["id"].'`, `Are you sure you want to edit '.strtoupper($user_details["username"]).' account`);" style="text-decoration: underline; color: green;"  class=""><i title="Edit Account" style="" class="bi bi-pencil-square" ></i></span>';
                            $user_level_with_upgrade_link = accountLevel($user_details["account_level"]).' <span onclick="customJsRedirect(`/bc-admin/UserUpgrade.php?userID='.$user_details["id"].'`, `Are you sure you want to Upgrade '.strtoupper($user_details["username"]).' account`);" style="text-decoration: underline; color: green;" class=""><i title="Upgrade Account" style="" class="bi bi-arrow-down-up" ></i></span>';
                            
                            if(!empty($user_details["referral_id"]) && is_numeric($user_details["referral_id"])){
                                $get_user_referral_details = mysqli_fetch_array(mysqli_query($connection_server, "SELECT * FROM sas_users WHERE vendor_id='".$get_logged_admin_details["id"]."' && id='".$user_details["referral_id"]."'"));
                            }else{
                                $get_user_referral_details = array("username" => "Not Referred");
                            }

                            $account_api_status = array(1 => "enabled", 2 => "disabled");
                            if(in_array($user_details["api_status"], array_keys($account_api_status))){
                                if($account_api_status[$user_details["api_status"]] == "enabled"){
                                    $api_status_details = ucwords($account_api_status[$user_details["api_status"]]).' <i onclick="updateUserAccountAPIStatus(`2`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: red;" class="bi bi-person-dash-fill"></i><span>';
                                }else{
                                    $api_status_details = ucwords($account_api_status[$user_details["api_status"]]).' <span onclick="updateUserAccountAPIStatus(`1`,`'.$user_details["username"].'`);" style="text-decoration: underline; color: green;" class="bi bi-person-check-fill"></i><span>';
                                }
                            }else{
                                $api_status_details = '<span style="color: red;">Invalid Status Code</span>';
                            }

                            echo 
                            '<tr>
                                <td>'.$countTransaction.'</td><td>'.$user_details["firstname"]." ".$user_details["lastname"].checkIfEmpty(ucwords($user_details["othername"]),", ", "").'</td><td style="user-select: auto;">'.$username_with_link.'</td><td>'.$user_level_with_upgrade_link.'</td><td>'.toDecimal($user_details["balance"], 2).'</td><td>'.$user_details["phone_number"].'</td><td>'.$user_details["home_address"].'</td><td style="user-select: auto;">'.$get_user_referral_details["username"].'</td><td>'.$api_status_details.'</td><td style="user-select: auto;">'.$user_apikey.'</td><td style="user-select: auto;">'.$user_details["security_answer"].'</td><td>'.formDate($user_details["reg_date"]).'</td><td>'.formDate($user_details["last_login"]).'</td><td class="">'.$all_user_account_action.'</td>
                            </tr>';
                        }
                    }
                    ?>
                  </tbody>
                </table>
            </div><br/>
            
            <div class="mt-2 justify-content-between justify-items-center">
                <?php if(isset($_GET["page"]) && is_numeric(trim(strip_tags($_GET["page"]))) && (trim(strip_tags($_GET["page"])) > 1)){ ?>
                <a href="Users.php?<?php echo $search_parameter; ?>page=<?php echo (trim(strip_tags($_GET["page"])) - 1); ?>">
                    <button style="user-select: auto;" class="btn btn-success col-auto">Prev</button>
                </a>
                <?php } ?>
                <?php
                	if(isset($_GET["page"]) && is_numeric(trim(strip_tags($_GET["page"]))) && (trim(strip_tags($_GET["page"])) >= 1)){
                		$trans_next = (trim(strip_tags($_GET["page"])) +1);
                	}else{
                		$trans_next = 2;
                	}
                ?>
                <a href="Users.php?<?php echo $search_parameter; ?>page=<?php echo $trans_next; ?>">
                    <button style="user-select: auto;" class="btn btn-success col-auto">Next</button>
                </a>
            </div>
            
        </div>
      </div>
    </section>

    <?php include("../func/bc-admin-footer.php"); ?>
    
</body>
</html>