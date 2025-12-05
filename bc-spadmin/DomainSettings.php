<?php session_start();
    include("../func/bc-spadmin-config.php");

    // Handle form submission
    if(isset($_POST['save_settings'])) {
        $nameservers = mysqli_real_escape_string($connection_server, $_POST['nameservers']);
        $ip_address = mysqli_real_escape_string($connection_server, $_POST['ip_address']);

        // For simplicity, we'll store these in the sas_super_admin_options table
        // This requires having a table like this:
        // CREATE TABLE sas_super_admin_options (
        //   option_name VARCHAR(255) PRIMARY KEY,
        //   option_value TEXT
        // );

        $sql_nameservers = "INSERT INTO sas_super_admin_options (option_name, option_value) VALUES ('domain_nameservers', '$nameservers') ON DUPLICATE KEY UPDATE option_value = '$nameservers'";
        $sql_ip = "INSERT INTO sas_super_admin_options (option_name, option_value) VALUES ('domain_ip_address', '$ip_address') ON DUPLICATE KEY UPDATE option_value = '$ip_address'";

        if(mysqli_query($connection_server, $sql_nameservers) && mysqli_query($connection_server, $sql_ip)) {
            $_SESSION['page_alert'] = "Settings saved successfully!";
        } else {
            $_SESSION['page_alert'] = "Error saving settings: " . mysqli_error($connection_server);
        }
        header("Location: DomainSettings.php");
        exit();
    }

    // Fetch current settings
    $nameservers = '';
    $ip_address = '';
    $sql_fetch = "SELECT * FROM sas_super_admin_options WHERE option_name IN ('domain_nameservers', 'domain_ip_address')";
    $result = mysqli_query($connection_server, $sql_fetch);
    while($row = mysqli_fetch_assoc($result)) {
        if($row['option_name'] == 'domain_nameservers') {
            $nameservers = $row['option_value'];
        }
        if($row['option_name'] == 'domain_ip_address') {
            $ip_address = $row['option_value'];
        }
    }
?>
<!DOCTYPE html>
<head>
    <title>Domain Setup Instructions</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link href="../assets-2/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets-2/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets-2/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include("../func/bc-spadmin-header.php"); ?>

    <div class="pagetitle">
        <h1>Domain Setup Instructions</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="Dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Domain Settings</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Configure Domain Instructions for New Vendors</h5>

                        <?php if(isset($_SESSION['page_alert'])): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['page_alert']; unset($_SESSION['page_alert']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nameservers" class="form-label">Nameservers</label>
                                <textarea class="form-control" id="nameservers" name="nameservers" rows="4" placeholder="e.g., ns1.yourhost.com&#x0a;ns2.yourhost.com"><?php echo htmlspecialchars($nameservers); ?></textarea>
                                <div class="form-text">Enter each nameserver on a new line.</div>
                            </div>
                            <div class="mb-3">
                                <label for="ip_address" class="form-label">A Record IP Address</label>
                                <input type="text" class="form-control" id="ip_address" name="ip_address" value="<?php echo htmlspecialchars($ip_address); ?>" placeholder="e.g., 192.168.1.1">
                                <div class="form-text">The IP address for vendors to use for A records (for subdomains).</div>
                            </div>
                            <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include("../func/bc-spadmin-footer.php"); ?>
</body>
</html>
