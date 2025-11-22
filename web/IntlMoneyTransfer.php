<?php session_start();
if(!isset($_SESSION["user_session"])){
    header("Location: /web/Login.php");
}
    include_once("../func/bc-config.php");

    $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server,"SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));
    $banks_response = json_decode(executeApiRequest("GET", "https://api.spendjuice.com/payment-methods/banks", ["Authorization: ".$juicyway_keys["secret_key"]], ""), true);
    $banks = $banks_response["data"];

    // Fetch user's Juicyway transfers
    $user_id = $select_user_table['id'];
    $transfers_query = mysqli_query($connection_server, "SELECT * FROM sas_juicyway_transfers WHERE vendor_id='".$select_vendor_table["id"]."' ORDER BY id DESC");

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
                "reference" => "pmt_user_".substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyz"), 0, 10),
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
                $stmt->bind_param("isdssss", $select_vendor_table["id"], $reference, $amount, $description, $destination_currency, $source_currency, $status);
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

        $initiate_crypto_payment_response = json_decode(executeApiRequest("POST", "https://api.spendjuice.com/payment-sessions", ["Authorization: ".$juicyway_keys["secret_key"], "Content-Type: application/json"], json_encode($crypto_data)), true);

        if(isset($initiate_crypto_payment_response["data"]["payment"]["payment_method"]["address"])){
            $address = $initiate_crypto_payment_response["data"]["payment"]["payment_method"]["address"];
            $chain = $initiate_crypto_payment_response["data"]["payment"]["payment_method"]["chain"];
            $crypto_currency = $initiate_crypto_payment_response["data"]["payment"]["payment_method"]["currency"];
            $reference = $initiate_crypto_payment_response["data"]["payment"]["reference"];
            $status = $initiate_crypto_payment_response["data"]["payment"]["status"];

            $stmt = $connection_server->prepare("INSERT INTO sas_juicyway_transfers (vendor_id, reference, amount, description, wallet_address, chain, crypto_currency, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdsssss", $select_vendor_table["id"], $reference, $amount, $description, $address, $chain, $crypto_currency, $status);
            $stmt->execute();

            $_SESSION["product_purchase_response"] = "Crypto payment initiated. Please send ".$crypto_currency." to the following address on the ".$chain." network: ".$address;
        } else {
            $_SESSION["product_purchase_response"] = "Failed to initiate crypto payment: " . $initiate_crypto_payment_response["error"]["message"];
        }
        header("Location: ".$_SERVER["REQUEST_URI"]);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>International Money Transfer | <?php echo $get_all_site_details["site_title"]; ?></title>
    <meta charset="UTF-8" />
    <meta name="description" content="<?php echo substr($get_all_site_details["site_desc"], 0, 160); ?>" />
    <meta http-equiv="Content-Type" content="text/html; " />
    <meta name="theme-color" content="black" />
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="author" content="BeeCodes Titan">
    <meta name="dc.creator" content="BeeCodes Titan">

    <link rel="stylesheet" href="/cssfile/bc-style.css">

    <!-- Vendor CSS Files -->
    <link href="../assets-2/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets-2/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="../cssfile/intl-money-transfer.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include("../func/bc-header.php"); ?>

    <main id="main" class="main">
        <div class="dashboard-container">
            <div class="main-content">
                <div class="header">
                    <div class="greeting">
                        <h1>Hello, <?php echo $select_user_table['fullname']; ?></h1>
                    </div>
                    <div class="referral-link">
                        <a href="#">Copy referral link</a>
                    </div>
                </div>

                <!-- Currency Selector -->
                <div class="currency-selector">
                    <div class="currency-tab active">🇳🇬 NGN</div>
                    <div class="currency-tab">🇬🇧 GBP</div>
                    <div class="currency-tab">🇺🇸 USD</div>
                    <div class="currency-tab">🇨🇦 CAD</div>
                    <div class="currency-tab">🇪🇺 EUR</div>
                </div>

                <!-- Balance Card -->
                <div class="balance-card">
                    <h2>Available balance</h2>
                    <div class="amount">₦ ******</div>
                    <div class="ledger-balance">Ledger balance: ₦ ******</div>
                    <a href="#" class="account-details-btn">Account Details</a>
                </div>

                <!-- Action Cards -->
                <div class="action-cards">
                    <div class="action-card">
                        <div class="icon">↓</div>
                        <h3>Deposit</h3>
                        <p>Top up your account</p>
                    </div>
                    <div class="action-card">
                        <div class="icon">↗</div>
                        <h3>Transfer</h3>
                        <p>Send money to others</p>
                    </div>
                    <div class="action-card">
                        <div class="icon">⇆</div>
                        <h3>Convert</h3>
                        <p>Swap currencies</p>
                    </div>
                    <div class="action-card">
                        <div class="icon">↺</div>
                        <h3>Request</h3>
                        <p>Ask others for money</p>
                    </div>
                </div>

                <!-- Transactions Section -->
                <div class="transactions-section">
                    <div class="tabs">
                        <div class="tab active" onclick="openTransactionTab(event, 'balances')">Balances</div>
                        <div class="tab" onclick="openTransactionTab(event, 'transactions')">Transactions</div>
                    </div>

                    <div id="balances" class="transaction-tab-content" style="display: block;">
                        <div class="transaction-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>SYMBOL</th>
                                        <th>CURRENCY</th>
                                        <th>AVL. BAL</th>
                                        <th>LDG. BAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Balance rows can be added here -->
                                    <tr>
                                        <td>NGN</td>
                                        <td>Nigerian Naira</td>
                                        <td>₦ 0.00</td>
                                        <td>₦ 0.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="transactions" class="transaction-tab-content">
                        <div class="transaction-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>REFERENCE</th>
                                        <th>AMOUNT</th>
                                        <th>DESCRIPTION</th>
                                        <th>STATUS</th>
                                        <th>DATE</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($transfers_query) > 0): ?>
                                        <?php while($transfer = mysqli_fetch_assoc($transfers_query)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transfer['reference']); ?></td>
                                                <td><?php echo htmlspecialchars($transfer['amount']); ?> <?php echo htmlspecialchars($transfer['destination_currency'] ?? $transfer['crypto_currency']); ?></td>
                                                <td><?php echo htmlspecialchars($transfer['description']); ?></td>
                                                <td><span class="status-badge status-<?php echo strtolower(htmlspecialchars($transfer['status'])); ?>"><?php echo htmlspecialchars($transfer['status']); ?></span></td>
                                                <td><?php echo date("Y-m-d H:i", strtotime($transfer['created_at'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center;">No transactions found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="sidebar">
                <div class="send-form-card">
                    <div class="form-tabs">
                        <button class="tab-link active" onclick="openForm(event, 'bank-transfer')">Bank Transfer</button>
                        <button class="tab-link" onclick="openForm(event, 'crypto')">Crypto</button>
                    </div>

                    <div id="bank-transfer" class="tab-content" style="display: block;">
                        <h3>Send via Bank Transfer</h3>
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="bank_name">Bank Name</label>
                                <select class="form-control" id="bank_name" name="bank_name" required>
                                    <option value="">Select Bank</option>
                                    <?php foreach($banks as $bank): ?>
                                        <option value="<?php echo htmlspecialchars($bank['name']); ?>"><?php echo htmlspecialchars($bank['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="account_number">Account Number</label>
                                <input type="text" class="form-control" id="account_number" name="account_number" required>
                            </div>
                            <div class="form-group">
                                <label for="account_name">Account Name</label>
                                <input type="text" class="form-control" id="account_name" name="account_name" required>
                            </div>
                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="number" class="form-control" id="amount" name="amount" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <input type="text" class="form-control" id="description" name="description" required>
                            </div>
                            <div class="form-group">
                                <label for="pin">PIN</label>
                                <input type="password" class="form-control" id="pin" name="pin" required>
                            </div>
                            <button type="submit" name="initiate-transfer" class="btn btn-primary">Send money</button>
                        </form>
                    </div>

                    <div id="crypto" class="tab-content">
                        <h3>Send via Crypto</h3>
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="crypto_first_name">First Name</label>
                                <input type="text" class="form-control" id="crypto_first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="crypto_last_name">Last Name</label>
                                <input type="text" class="form-control" id="crypto_last_name" name="last_name" required>
                            </div>
                            <div class="form-group">
                                <label for="crypto_email">Email</label>
                                <input type="email" class="form-control" id="crypto_email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="crypto_phone_number">Phone Number</label>
                                <input type="text" class="form-control" id="crypto_phone_number" name="phone_number" required>
                            </div>
                            <div class="form-group">
                                <label for="crypto_amount">Amount (USD)</label>
                                <input type="number" class="form-control" id="crypto_amount" name="amount" required>
                            </div>
                            <div class="form-group">
                                <label for="crypto_currency">Currency</label>
                                <select class="form-control" id="crypto_currency" name="currency" required>
                                    <option value="USDT">USDT</option>
                                    <option value="USDC">USDC</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="crypto_description">Description</label>
                                <input type="text" class="form-control" id="crypto_description" name="description" required>
                            </div>
                            <button type="submit" name="initiate-crypto-payment" class="btn btn-primary">Initiate Crypto Payment</button>
                        </form>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <script>
        function openForm(evt, formName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab-link");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(formName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        function openTransactionTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("transaction-tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.querySelectorAll(".transactions-section .tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
    </script>
    <?php include("../func/bc-footer.php"); ?>
</body>
</html>
