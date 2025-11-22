<?php session_start();
if(!isset($_SESSION["user_session"])){
    header("Location: /web/Login.php");
}
    include_once("../func/bc-config.php");
    include_once("../func/payment-link-handlers.php");

    handle_create_payment_link($connection_server, $select_vendor_table, $select_user_table);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payments | <?php echo $get_all_site_details["site_title"]; ?></title>
    <meta charset="UTF-8" />
    <meta name="description" content="<?php echo substr($get_all_site_details["site_desc"], 0, 160); ?>" />
    <meta http-equiv="Content-Type" content="text/html; " />
    <meta name="theme-color" content="black" />
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="author" content="BeeCodes Titan">
    <meta name="dc.creator" content="BeeCodes Titan">

    <link rel="stylesheet" href="/cssfile/bc-style.css">
    <link href="../assets-2/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets-2/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets-2/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include("../func/bc-header.php"); ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Payments</h1>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Payment History</h5>
                            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createPaymentModal">
                                Create Payment
                            </button>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Reference</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" class="text-center">No payments found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include("../func/bc-footer.php"); ?>

    <!-- Create Payment Modal -->
    <div class="modal fade" id="createPaymentModal" tabindex="-1" aria-labelledby="createPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createPaymentModalLabel">Create Payment Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createPaymentForm" method="post" action="?action=create-payment-link">
                        <div class="form-group mb-3">
                            <label for="item_description">Item Description</label>
                            <input type="text" class="form-control" id="item_description" name="item_description" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="amount">Amount</label>
                            <input type="number" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="currency">Currency</label>
                            <select class="form-control" id="currency" name="currency" required>
                                <option value="NGN">NGN</option>
                                <option value="CAD">CAD</option>
                            </select>
                        </div>
                        <button type="submit" id="generateBtn" class="btn btn-primary">Generate</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets-2/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('createPaymentForm').addEventListener('submit', function() {
            var generateBtn = document.getElementById('generateBtn');
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        });
    </script>
</body>
</html>