<?php session_start([
    'cookie_lifetime' => 286400,
	'gc_maxlifetime' => 286400,
]);
    include("../func/bc-config.php");
    
    if(isset($_POST["buy-airtime"])){
        $purchase_method = "web";
		include_once("func/airtime.php");
        $json_response_decode = json_decode($json_response_encode,true);
        $_SESSION["product_purchase_response"] = $json_response_decode["desc"];
        //echo '<script>alert("'.$json_response_decode["status"].': '.$json_response_decode["desc"].'");</script>';
        header("Location: ".$_SERVER["REQUEST_URI"]);
    }

?>
<!DOCTYPE html>
<head>
<title>Airtime | <?php echo $get_all_site_details["site_title"]; ?></title>
    <meta charset="UTF-8" />
    <meta name="description" content="<?php echo substr($get_all_site_details["site_desc"], 0, 160); ?>" />
    <meta http-equiv="Content-Type" content="text/html; " />
    <meta name="theme-color" content="black" />
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="stylesheet" href="<?php echo $css_style_template_location; ?>">
    <link rel="stylesheet" href="/cssfile/bc-style.css">
    <meta name="author" content="BeeCodes Titan">
    <meta name="dc.creator" content="BeeCodes Titan">
    
      
        
    <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link
    href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
    rel="stylesheet">

    <script src="https://merchant.beewave.ng/checkout.min.js"></script> 
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
	<?php include("../func/bc-header.php"); ?>	
	
	<div class="pagetitle">
      <h1>BUY AIRTIME</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Buy Airtime</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="col-12">

    
    <div class="card info-card px-5 py-5">
        <form method="post" action="">
                <div style="text-align: center; user-select: auto;" class="container">
                    <img alt="Airtel" id="airtel-lg" product-status="enabled" src="/asset/airtel.png" onclick="tickAirtimeCarrier('airtel');" class="col-2 rounded-5 border m-1 "/>
                    <img alt="MTN" id="mtn-lg" product-status="enabled" src="/asset/mtn.png" onclick="tickAirtimeCarrier('mtn');" class="col-2 rounded-5 border m-1 "/>
                    <img alt="Glo" id="glo-lg" product-status="enabled" src="/asset/glo.png" onclick="tickAirtimeCarrier('glo');" class="col-2 rounded-5 border m-1 "/>
                    <img alt="9mobile" id="9mobile-lg" product-status="enabled" src="/asset/9mobile.png" onclick="tickAirtimeCarrier('9mobile');" class="col-2 rounded-5 border m-1 "/>
                </div><br/>
                <input id="isprovider" name="isp" type="text" placeholder="Isp" hidden readonly required/>
                <input style="text-align: center;" id="phone-number" name="phone-number" onkeyup="tickAirtimeCarrier();" type="text" value="" placeholder="Phone number e.g 08124232128" pattern="[0-9]{11}" title="Charater must be an 11 digit" class="form-control mb-1" required/><br/>
                <input style="text-align: center;" id="product-amount" name="amount" onkeyup="tickAirtimeCarrier();" type="text" value="" placeholder="Amount e.g 100" pattern="[0-9]{3,}" title="Charater must be atleast 3 digit" class="form-control mb-1" required/><br/>
                <div style="text-align: left;" class="container mb-1">
                    <input id="phone-bypass" onclick="tickAirtimeCarrier('airtel');" type="checkbox" class="form-check-input mb-1" />
                    <div class="col-12">
                        <label for="phone-bypass" class="h5" style="user-select: auto;">
                            Bypass Phone Verification
                        </label>
                    </div>
                </div><br>
                <button id="proceedBtn" name="buy-airtime" type="button" style="pointer-events: none; user-select: auto;" class="btn btn-success mb-1 col-12" >
                    BUY AIRTIME
                </button><br>
                <div style="text-align: center;" class="col-8">
                    <span id="product-status-span" class="h5" style="user-select: auto;"></span>
                </div>
            </form>
        </div>
    </div>
</section>
		<?php include("../func/short-trans.php"); ?>
	<?php include("../func/bc-footer.php"); ?>
	
</body>
</html>