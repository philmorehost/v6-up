<?php session_start();
    include("../func/bc-admin-config.php");

    $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server,"SELECT * FROM sas_payment_gateways WHERE vendor_id='".$get_logged_admin_details["id"]."' && gateway_name='juicyway'"));
    $banks_response = json_decode(executeApiRequest("GET", "https://api.spendjuice.com/payment-methods/banks", ["Authorization: ".$juicyway_keys["secret_key"]], ""), true);
    $banks = $banks_response["data"];

    if(isset($_POST["initiate-transfer"])){
        $account_name = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["account_name"])));
        $account_number = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["account_number"])));
        $bank_name = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["bank_name"])));
        $bank_code = "";
        foreach($banks as $bank){
            if($bank["name"] == $bank_name){
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

        $create_beneficiary_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/beneficiaries", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($beneficiary_data)), true);

        if(isset($create_beneficiary_response["data"]["id"])){
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
                "reference" => "pmt_vendor_".substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyz"), 0, 10),
                "source_currency" => "NGN",
                "fee_charged_to" => "sender"
            );

            $initiate_transfer_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/payouts", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($transfer_data)), true);

            if(isset($initiate_transfer_response["data"]["id"])){
                $reference = $initiate_transfer_response["data"]["reference"];
                $amount = $initiate_transfer_response["data"]["amount"] / 100;
                $description = $initiate_transfer_response["data"]["description"];
                $destination_currency = $initiate_transfer_response["data"]["destination_currency"];
                $source_currency = $initiate_transfer_response["data"]["source_currency"];
                $status = $initiate_transfer_response["data"]["status"];

                $stmt = $connection_server->prepare("INSERT INTO sas_juicyway_transfers (vendor_id, reference, amount, description, destination_currency, source_currency, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isdssss", $get_logged_admin_details["id"], $reference, $amount, $description, $destination_currency, $source_currency, $status);
                $stmt->execute();

                $_SESSION["product_purchase_response"] = "Transfer initiated successfully.";
            } else {
                $_SESSION["product_purchase_response"] = "Failed to initiate transfer: " . $initiate_transfer_response["error"]["message"];
            }
        } else {
            $_SESSION["product_purchase_response"] = "Failed to create beneficiary: " . $create_beneficiary_response["error"]["message"];
        }
        header("Location: ".$_SERVER["REQUEST_URI"]);
    }

    if(isset($_POST["initiate-crypto-payment"])){
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
                    "line1" => $get_logged_admin_details["home_address"],
                    "city" => $get_logged_admin_details["home_address"],
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

        $initiate_crypto_payment_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/payment-sessions", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($crypto_data)), true);

        if(isset($initiate_crypto_payment_response["data"]["payment"]["payment_method"]["address"])){
            $address = $initiate_crypto_payment_response["data"]["payment"]["payment_method"]["address"];
            $chain = $initiate_crypto_payment_response["data"]["payment"]["payment_method"]["chain"];
            $crypto_currency = $initiate_crypto_payment_response["data"]["payment"]["payment_method"]["currency"];
            $reference = $initiate_crypto_payment_response["data"]["payment"]["reference"];
            $status = $initiate_crypto_payment_response["data"]["payment"]["status"];

            $stmt = $connection_server->prepare("INSERT INTO sas_juicyway_transfers (vendor_id, reference, amount, description, wallet_address, chain, crypto_currency, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsssss", $get_logged_admin_details["id"], $reference, $amount, $description, $address, $chain, $crypto_currency, $status);
            $stmt->execute();

            $_SESSION["product_purchase_response"] = "Crypto payment initiated. Please send ".$crypto_currency." to the following address on the ".$chain." network: ".$address;
        } else {
            $_SESSION["product_purchase_response"] = "Failed to initiate crypto payment: " . $initiate_crypto_payment_response["error"]["message"];
        }
        header("Location: ".$_SERVER["REQUEST_URI"]);
    }
?>
<!DOCTYPE html>
<head>
    <title>Juicyway | <?php echo $get_all_super_admin_site_details["site_title"]; ?></title>
    <meta charset="UTF-8" />
    <meta name="description" content="<?php echo substr($get_all_super_admin_site_details["site_desc"], 0, 160); ?>" />
    <meta http-equiv="Content-Type" content="text/html; " />
    <meta name="theme-color" content="black" />
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
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
  <link href="css/juicyway.css" rel="stylesheet">
</head>
<body>
    <?php include("../func/bc-admin-header.php"); ?>
	<div class="pagetitle">
      <h1>Juicyway Features</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Juicyway</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">

        <!-- Left side columns -->
        <div class="col-lg-12">
          <div class="row">

            <!-- Bank Transfer Card -->
            <div class="col-xxl-12 col-xl-12">

              <div class="card info-card bank-transfer-card">

                <div class="card-body">
                  <h5 class="card-title">Bank Transfer</h5>

                  <form method="post" action="">
                      <div class="row">
                          <div class="col-md-6">
                              <div class="mb-3">
                                  <label for="bank_name" class="form-label">Bank Name</label>
                                  <select class="form-select" id="bank_name" name="bank_name" required>
                                      <option value="">Select Bank</option>
                                      <?php foreach($banks as $bank): ?>
                                          <option value="<?php echo htmlspecialchars($bank['name']); ?>"><?php echo htmlspecialchars($bank['name']); ?></option>
                                      <?php endforeach; ?>
                                  </select>
                              </div>
                              <div class="mb-3">
                                  <label for="account_number" class="form-label">Account Number</label>
                                  <input type="text" class="form-control" id="account_number" name="account_number" required>
                              </div>
                              <div class="mb-3">
                                  <label for="account_name" class="form-label">Account Name</label>
                                  <input type="text" class="form-control" id="account_name" name="account_name" required>
                              </div>
                          </div>
                          <div class="col-md-6">
                              <div class="mb-3">
                                  <label for="amount" class="form-label">Amount</label>
                                  <input type="number" class="form-control" id="amount" name="amount" required>
                              </div>
                              <div class="mb-3">
                                  <label for="description" class="form-label">Description</label>
                                  <input type="text" class="form-control" id="description" name="description" required>
                              </div>
                              <div class="mb-3">
                                  <label for="pin" class="form-label">PIN</label>
                                  <input type="password" class="form-control" id="pin" name="pin" required>
                              </div>
                          </div>
                      </div>
                      <button type="submit" name="initiate-transfer" class="btn btn-primary">Initiate Transfer</button>
                  </form>

                </div>
              </div>

            </div><!-- End Bank Transfer Card -->

            <!-- Other cards with "Coming Soon" -->
            <!-- Sales Card -->
            <div class="col-xxl-4 col-md-6">
              <div class="card info-card sales-card">

                <div class="card-body">
                  <h5 class="card-title">Virtual Credit/Debit Card</h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-credit-card"></i>
                    </div>
                    <div class="ps-3">
                      <h6>Coming Soon</h6>
                    </div>
                  </div>
                </div>

              </div>
            </div><!-- End Sales Card -->

            <!-- Crypto Currency Card -->
            <div class="col-xxl-12 col-xl-12">
                <div class="card info-card revenue-card">
                    <div class="card-body">
                        <h5 class="card-title">Crypto Currency</h5>
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="crypto_first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="crypto_first_name" name="first_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="crypto_last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="crypto_last_name" name="last_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="crypto_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="crypto_email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="crypto_phone_number" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="crypto_phone_number" name="phone_number" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="crypto_amount" class="form-label">Amount (USD)</label>
                                        <input type="number" class="form-control" id="crypto_amount" name="amount" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="crypto_currency" class="form-label">Currency</label>
                                        <select class="form-select" id="crypto_currency" name="currency" required>
                                            <option value="USDT">USDT</option>
                                            <option value="USDC">USDC</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="crypto_description" class="form-label">Description</label>
                                        <input type="text" class="form-control" id="crypto_description" name="description" required>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="initiate-crypto-payment" class="btn btn-primary">Initiate Crypto Payment</button>
                        </form>
                    </div>
                </div>
            </div><!-- End Crypto Currency Card -->

            <!-- Customers Card -->
            <div class="col-xxl-4 col-xl-12">

              <div class="card info-card customers-card">

                <div class="card-body">
                  <h5 class="card-title">Exchange</h5>

                  <div class="d-flex align-items-center">
                    <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                      <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div class="ps-3">
                      <h6>Coming Soon</h6>
                    </div>
                  </div>

                </div>
              </div>

            </div><!-- End Customers Card -->

          </div>
        </div><!-- End Left side columns -->

      </div>
    </section>

    <?php include("../func/bc-admin-footer.php"); ?>

</body>
</html>
