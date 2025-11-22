<?php session_start();
if(!isset($_SESSION["user_session"])){
    header("Location: /web/Login.php");
}
    include_once("../func/bc-config.php");
    include_once("../func/balance-actions.php");
    include_once("../func/transfer-handlers.php");
    include_once("../func/crypto-handlers.php");
    include_once("../func/receive-money-handlers.php");
    include_once("../func/convert-handlers.php");

    // Fetch virtual bank accounts for CAD and NGN
    $cad_account = get_virtual_account('CAD', $connection_server, $select_vendor_table, $select_user_table);
    $ngn_account = get_virtual_account('NGN', $connection_server, $select_vendor_table, $select_user_table);

    // Determine the selected currency, defaulting to NGN
    $selected_currency = isset($_GET['currency']) ? $_GET['currency'] : 'NGN';

    // Fetch balance and transactions for the selected currency
    $balance = get_juicyway_balance($selected_currency, $connection_server, $select_vendor_table, $select_user_table);
    $transactions = get_juicyway_transactions($selected_currency, $connection_server, $select_vendor_table);

    $juicyway_keys = mysqli_fetch_assoc(mysqli_query($connection_server,"SELECT * FROM sas_payment_gateways WHERE vendor_id='".$select_vendor_table["id"]."' && gateway_name='juicyway'"));
    $banks_response = json_decode(executeApiRequest("GET", "https://api.spendjuice.com/payment-methods/banks", ["Authorization: ".$juicyway_keys["secret_key"]], ""), true);
    $banks = $banks_response["data"];

    handle_initiate_transfer($connection_server, $select_vendor_table, $select_user_table, $banks);
    handle_initiate_crypto_payment($connection_server, $select_vendor_table, $select_user_table);
    handle_convert_currency($connection_server, $select_vendor_table);
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
    <link href="../assets-2/css/style.css" rel="stylesheet">
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
                        <a href="javascript:void(0);" id="copy-referral-link">Copy referral link</a>
                    </div>
                </div>

                <!-- Currency Selector -->
                <div class="currency-selector">
                    <div class="currency-tab <?php echo ($selected_currency == 'NGN') ? 'active' : ''; ?>" onclick="window.location.href='?currency=NGN'"><img src="/img/ng.png" alt="NGN" class="currency-flag"> NGN</div>
                    <div class="currency-tab <?php echo ($selected_currency == 'GBP') ? 'active' : ''; ?>" onclick="window.location.href='?currency=GBP'"><img src="/img/gb.png" alt="GBP" class="currency-flag"> GBP</div>
                    <div class="currency-tab <?php echo ($selected_currency == 'USD') ? 'active' : ''; ?>" onclick="window.location.href='?currency=USD'"><img src="/img/us.png" alt="USD" class="currency-flag"> USD</div>
                    <div class="currency-tab <?php echo ($selected_currency == 'CAD') ? 'active' : ''; ?>" onclick="window.location.href='?currency=CAD'"><img src="/img/ca.png" alt="CAD" class="currency-flag"> CAD</div>
                    <div class="currency-tab <?php echo ($selected_currency == 'EUR') ? 'active' : ''; ?>" onclick="window.location.href='?currency=EUR'"><img src="/img/eu.png" alt="EUR" class="currency-flag"> EUR</div>
                </div>

                <!-- Balance Card -->
                <div class="balance-card">
                    <h2>Available balance</h2>
                    <div class="amount"><?php echo htmlspecialchars($balance['currency']); ?> <?php echo number_format($balance['available_balance'] / 100, 2); ?></div>
                    <div class="ledger-balance">Ledger balance: <?php echo htmlspecialchars($balance['currency']); ?> <?php echo number_format($balance['ledger_balance'] / 100, 2); ?></div>
                    <a href="#" class="account-details-btn">Account Details</a>
                </div>

                <!-- Action Cards -->
                <div class="action-cards">
                    <div class="action-card" data-bs-toggle="modal" data-bs-target="#depositModal">
                        <div class="icon">↓</div>
                        <h3>Deposit</h3>
                        <p>Top up your account</p>
                    </div>
                    <div class="action-card" onclick="scrollToTransferForm()">
                        <div class="icon">↗</div>
                        <h3>Transfer</h3>
                        <p>Send money to others</p>
                    </div>
                    <div class="action-card" data-bs-toggle="modal" data-bs-target="#convertModal">
                        <div class="icon">⇆</div>
                        <h3>Convert</h3>
                        <p>Swap currencies</p>
                    </div>
                    <div class="action-card" onclick="window.location.href='Payments.php'">
                        <div class="icon">💳</div>
                        <h3>Payments</h3>
                        <p>Create and manage payment links</p>
                    </div>
                </div>

                <!-- Transactions Section -->
                <div class="transactions-section">
                    <div class="tabs">
                        <div class="tab active">Transactions</div>
                    </div>

                    <div id="transactions" class="transaction-tab-content" style="display: block;">
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
                                    <?php if(!empty($transactions)): ?>
                                        <?php foreach($transactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaction['reference']); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['currency']); ?> <?php echo number_format($transaction['amount'] / 100, 2); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                <td><span class="status-badge status-<?php echo strtolower(htmlspecialchars($transaction['status'])); ?>"><?php echo htmlspecialchars($transaction['status']); ?></span></td>
                                                <td><?php echo date("Y-m-d H:i", strtotime($transaction['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="text-align: center;">No transactions found for <?php echo htmlspecialchars($selected_currency); ?>.</td>
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
                        <button class="tab-link" onclick="openForm(event, 'receive-money')">Receive Money</button>
                    </div>

                    <div id="bank-transfer" class="tab-content" style="display: block;">
                        <h3>Send via Bank Transfer</h3>
                        <form method="post" action="?action=initiate-transfer">
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
                        <form method="post" action="?action=initiate-crypto-payment">
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

                    <div id="receive-money" class="tab-content">
                        <h3>Receive Money</h3>
                        <p>Receive payments directly into your wallet using the virtual bank account details below.</p>

                        <?php if($ngn_account): ?>
                            <div class="account-details">
                                <h4>NGN Account</h4>
                                <p><strong>Account Name:</strong> <?php echo htmlspecialchars($ngn_account['account_name']); ?></p>
                                <p><strong>Account Number:</strong> <?php echo htmlspecialchars($ngn_account['account_number']); ?></p>
                                <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($ngn_account['bank_name']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if($cad_account): ?>
                            <div class="account-details">
                                <h4>CAD Account</h4>
                                <p><strong>Account Name:</strong> <?php echo htmlspecialchars($cad_account['account_name']); ?></p>
                                <p><strong>Account Number:</strong> <?php echo htmlspecialchars($cad_account['account_number']); ?></p>
                                <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($cad_account['bank_name']); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if(!$ngn_account && !$cad_account): ?>
                            <p>No virtual bank accounts are available at this time.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <script>
        document.getElementById('copy-referral-link').addEventListener('click', function() {
            var referralLink = "<?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/web/Register.php?ref=' . $select_user_table['username']; ?>";
            navigator.clipboard.writeText(referralLink).then(function() {
                alert('Referral link copied to clipboard!');
            }, function(err) {
                alert('Could not copy text: ', err);
            });
        });

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

    function scrollToTransferForm() {
        document.querySelector('.send-form-card').scrollIntoView({ behavior: 'smooth' });
    }
    </script>
    <?php include("../func/bc-footer.php"); ?>

    <!-- Deposit Modal -->
    <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="depositModalLabel">Deposit Funds</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>To deposit funds, please use the following virtual bank account details:</p>
                    <?php if($ngn_account): ?>
                        <div class="account-details">
                            <h4>NGN Account</h4>
                            <p><strong>Account Name:</strong> <?php echo htmlspecialchars($ngn_account['account_name']); ?></p>
                            <p><strong>Account Number:</strong> <?php echo htmlspecialchars($ngn_account['account_number']); ?></p>
                            <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($ngn_account['bank_name']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if($cad_account): ?>
                        <div class="account-details">
                            <h4>CAD Account</h4>
                            <p><strong>Account Name:</strong> <?php echo htmlspecialchars($cad_account['account_name']); ?></p>
                            <p><strong>Account Number:</strong> <?php echo htmlspecialchars($cad_account['account_number']); ?></p>
                            <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($cad_account['bank_name']); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if(!$ngn_account && !$cad_account): ?>
                        <p>No virtual bank accounts are available at this time.</p>
                    <?php endif; ?>
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
                    <form method="post" action="?action=convert-currency">
                        <div class="form-group">
                            <label for="from_currency">From</label>
                            <select class="form-control" id="from_currency" name="from_currency" required>
                                <option value="NGN">NGN</option>
                                <option value="GBP">GBP</option>
                                <option value="USD">USD</option>
                                <option value="CAD">CAD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="to_currency">To</label>
                            <select class="form-control" id="to_currency" name="to_currency" required>
                                <option value="NGN">NGN</option>
                                <option value="GBP">GBP</option>
                                <option value="USD">USD</option>
                                <option value="CAD">CAD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" class="form-control" id="amount" name="amount" required>
                        </div>
                        <button type="submit" name="convert" class="btn btn-primary">Convert</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets-2/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
