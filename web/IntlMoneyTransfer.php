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
        $source_currency_post = mysqli_real_escape_string($connection_server, trim(strip_tags($_POST["source_currency"])));

        // Check if the user has enough balance
        $wallet_query = mysqli_query($connection_server, "SELECT balance FROM sas_user_wallets WHERE user_id = '$user_id' AND currency = '$source_currency_post'");
        if (mysqli_num_rows($wallet_query) > 0) {
            $wallet = mysqli_fetch_assoc($wallet_query);
            if ($wallet['balance'] < $amount) {
                $_SESSION["product_purchase_response"] = "Insufficient funds.";
                header("Location: ".$_SERVER["REQUEST_URI"]);
                exit();
            }
        } else {
            $_SESSION["product_purchase_response"] = "You do not have a wallet for the selected currency.";
            header("Location: ".$_SERVER["REQUEST_URI"]);
            exit();
        }

        $beneficiary_data = array(
            "type" => "bank_account",
            "currency" => $source_currency_post,
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
                "destination_currency" => $source_currency_post,
                "pin" => $pin,
                "reference" => "pmt_user_".substr(str_shuffle("1234567890abcdefghijklmnopqrstuvwxyz"), 0, 10),
                "source_currency" => $source_currency_post,
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

                $stmt = $connection_server->prepare("INSERT INTO sas_juicyway_transfers (user_id, vendor_id, reference, amount, description, destination_currency, source_currency, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdssss", $user_id, $select_vendor_table["id"], $reference, $amount, $description, $destination_currency, $source_currency, $status);
                $stmt->execute();

                // Deduct the amount from the user's wallet
                $new_balance = $wallet['balance'] - $amount;
                mysqli_query($connection_server, "UPDATE sas_user_wallets SET balance = '$new_balance' WHERE user_id = '$user_id' AND currency = '$source_currency_post'");

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

            $stmt = $connection_server->prepare("INSERT INTO sas_juicyway_transfers (user_id, vendor_id, reference, amount, description, wallet_address, chain, crypto_currency, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisdsssss", $user_id, $select_vendor_table["id"], $reference, $amount, $description, $address, $chain, $crypto_currency, $status);
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

    <div class="main-content-container">
        <div class="main-content">
            <div class="header">
                    <div class="greeting">
                        <h1>Hello, <?php echo $select_user_table['fullname']; ?></h1>
                    </div>
                    <div class="referral-link">
                        <a href="#" id="copy-referral-link" data-link="<?php echo $web_http_host; ?>/register.php?ref=<?php echo $select_user_table['username']; ?>">Copy referral link</a>
                    </div>
                </div>

                <!-- Currency Selector -->
                <div class="currency-selector">
                    <div class="currency-tab active" data-currency="NGN" onclick="selectCurrency('NGN')">🇳🇬 NGN</div>
                    <div class="currency-tab" data-currency="GBP" onclick="selectCurrency('GBP')">🇬🇧 GBP</div>
                    <div class="currency-tab" data-currency="USD" onclick="selectCurrency('USD')">🇺🇸 USD</div>
                    <div class="currency-tab" data-currency="CAD" onclick="selectCurrency('CAD')">🇨🇦 CAD</div>
                    <div class="currency-tab" data-currency="EUR" onclick="selectCurrency('EUR')">🇪🇺 EUR</div>
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
                    <div class="action-card" data-bs-toggle="modal" data-bs-target="#depositModal">
                        <div class="icon">↓</div>
                        <h3>Deposit</h3>
                        <p>Top up your account</p>
                    </div>
                    <div class="action-card" data-bs-toggle="modal" data-bs-target="#transferModal">
                        <div class="icon">↗</div>
                        <h3>Transfer</h3>
                        <p>Send money to others</p>
                    </div>
                    <div class="action-card" data-bs-toggle="modal" data-bs-target="#convertModal">
                        <div class="icon">⇆</div>
                        <h3>Convert</h3>
                        <p>Swap currencies</p>
                    </div>
                    <div class="action-card" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <div class="icon">💸</div>
                        <h3>Payments</h3>
                        <p>Create payment links</p>
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
                                    <?php
                                    $user_id = $select_user_table['id'];
                                    // In a real application, you would also join with the users table to get the username
                                    $transactions_query = mysqli_query($connection_server, "SELECT * FROM sas_juicyway_transfers WHERE vendor_id='".$select_vendor_table["id"]."' ORDER BY id DESC");
                                    if(mysqli_num_rows($transactions_query) > 0):
                                        while($transaction = mysqli_fetch_assoc($transactions_query)):
                                    ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaction['reference']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['amount']); ?> <?php echo htmlspecialchars($transaction['destination_currency'] ?? $transaction['crypto_currency']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                <td><span class="status-badge status-<?php echo strtolower(htmlspecialchars($transaction['status'])); ?>"><?php echo htmlspecialchars($transaction['status']); ?></span></td>
                                                <td><?php echo date("Y-m-d H:i", strtotime($transaction['date'])); ?></td>
                                            </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
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

            <aside class="sidebar-right">
                <div class="send-form-card">
                    <div class="form-tabs">
                        <button class="tab-link active" onclick="openForm(event, 'bank-transfer')">Bank Transfer</button>
                        <button class="tab-link" onclick="openForm(event, 'crypto')">Crypto</button>
                    </div>

                    <div id="bank-transfer" class="tab-content" style="display: block;">
                        <h3>Send via Bank Transfer</h3>
                        <form method="post" action="">
                            <div class="form-group">
                                <label for="source_currency">Source Currency</label>
                                <select class="form-control" id="source_currency" name="source_currency" required>
                                    <option value="NGN">NGN</option>
                                    <option value="USD">USD</option>
                                    <option value="GBP">GBP</option>
                                    <option value="EUR">EUR</option>
                                    <option value="CAD">CAD</option>
                                </select>
                            </div>
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

    <script src="/js/intl-money-transfer.js"></script>

    <!-- Deposit Modal -->
    <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="depositModalLabel">Deposit Funds</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-tabs">
                        <button class="tab-link active" onclick="openDepositForm(event, 'bank-deposit')">Bank Transfer</button>
                        <button class="tab-link" onclick="openDepositForm(event, 'crypto-deposit')">Crypto Deposit</button>
                    </div>

                    <div id="bank-deposit" class="tab-content" style="display: block;">
                        <h3>Deposit via Bank Transfer</h3>
                        <div id="virtual-account-details">
                            <p>Click the button below to generate a virtual account for your deposit.</p>
                            <button id="generate-vacct-btn" class="btn btn-primary">Generate Virtual Account</button>
                        </div>
                    </div>

                    <div id="crypto-deposit" class="tab-content">
                        <h3>Deposit via Crypto</h3>
                        <p>Please send USDC or USDT to the following address:</p>
                        <p><strong>Address:</strong> 0x1234567890abcdef1234567890abcdef12345678</p>
                        <p><strong>Network:</strong> Ethereum (ERC20)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Modal -->
    <div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferModalLabel">Transfer Funds</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please use the form on the right to transfer funds.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Convert Modal -->
    <div class="modal fade" id="convertModal" tabindex="-1" aria-labelledby="convertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="convertModalLabel">Convert Currency</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="convert-currency-form">
                        <div class="form-group">
                            <label for="from-currency">From</label>
                            <select class="form-control" id="from-currency" name="from_currency" required>
                                <option value="NGN">NGN</option>
                                <option value="USD">USD</option>
                                <option value="GBP">GBP</option>
                                <option value="EUR">EUR</option>
                                <option value="CAD">CAD</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="to-currency">To</label>
                            <select class="form-control" id="to-currency" name="to_currency" required>
                                <option value="NGN">NGN</option>
                                <option value="USD">USD</option>
                                <option value="GBP">GBP</option>
                                <option value="EUR">EUR</option>
                                <option value="CAD">CAD</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="convert-amount">Amount</label>
                            <input type="number" class="form-control" id="convert-amount" name="amount" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Convert</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Create Payment Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="create-payment-link-form">
                        <div class="form-group">
                            <label for="payment-amount">Amount</label>
                            <input type="number" class="form-control" id="payment-amount" name="amount" required>
                        </div>
                        <div class="form-group">
                            <label for="payment-currency">Currency</label>
                            <select class="form-control" id="payment-currency" name="currency" required>
                                <option value="NGN">NGN</option>
                                <option value="USD">USD</option>
                                <option value="GBP">GBP</option>
                                <option value="EUR">EUR</option>
                                <option value="CAD">CAD</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="payment-description">Description</label>
                            <input type="text" class="form-control" id="payment-description" name="description" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Link</button>
                    </form>
                    <div id="payment-link-result" style="display:none; margin-top: 1rem;">
                        <p>Here is your payment link:</p>
                        <input type="text" class="form-control" id="payment-link-url" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../func/bc-footer.php"); ?>
</body>
</html>
