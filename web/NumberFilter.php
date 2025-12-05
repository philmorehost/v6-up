<?php session_start();
    include("../func/bc-config.php");
?>
<!DOCTYPE html>
<head>
    <title>Number Filter | <?php echo $get_all_super_admin_site_details["site_title"]; ?></title>
    <meta charset="UTF-8" />
    <meta name="description" content="<?php echo substr($get_all_super_admin_site_details["site_desc"], 0, 160); ?>" />
    <meta http-equiv="Content-Type" content="text/html; " />
    <meta name="theme-color" content="black" />
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="stylesheet" href="<?php echo $css_style_template_location; ?>">
    <link rel="stylesheet" href="cssfile/bc-style.css">
    <meta name="author" content="BeeCodes Titan">
    <meta name="dc.creator" content="BeeCodes Titan">

            <!-- Vendor CSS Files -->
  <link href="assets-2/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets-2/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets-2/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets-2/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets-2/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets-2/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets-2/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets-2/css/style.css" rel="stylesheet">
  <style>
    .form-group {
      margin-bottom: 1rem;
    }

    .btn-secondary {
      width: 100%;
    }
  </style>

</head>
<body>
    <?php include("../func/bc-header.php"); ?>


	<main id="main" class="main">
	<div class="pagetitle">
      <h1>Number Filter</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="#">Home</a></li>
          <li class="breadcrumb-item active">Number Filter</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Phone Number Filter</h5>
              <p>Paste phone numbers in the box below and click the filter button to sort them by network.</p>

              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="phone_numbers">Paste Phone Numbers Here</label>
                    <textarea class="form-control" id="phone_numbers" rows="10"></textarea>
                  </div>
                  <button type="button" class="btn btn-primary mt-3" id="filter_button">Filter Numbers</button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="col-lg-12">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Filtered Numbers</h5>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="mtn_numbers">MTN</label>
                    <textarea class="form-control" id="mtn_numbers" rows="10" readonly></textarea>
                    <button type="button" class="btn btn-secondary mt-2" onclick="copyToClipboard('mtn_numbers')">Copy</button>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="glo_numbers">GLO</label>
                    <textarea class="form-control" id="glo_numbers" rows="10" readonly></textarea>
                    <button type="button" class="btn btn-secondary mt-2" onclick="copyToClipboard('glo_numbers')">Copy</button>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="airtel_numbers">Airtel</label>
                    <textarea class="form-control" id="airtel_numbers" rows="10" readonly></textarea>
                    <button type="button" class="btn btn-secondary mt-2" onclick="copyToClipboard('airtel_numbers')">Copy</button>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="ninemobile_numbers">9mobile</label>
                    <textarea class="form-control" id="ninemobile_numbers" rows="10" readonly></textarea>
                    <button type="button" class="btn btn-secondary mt-2" onclick="copyToClipboard('ninemobile_numbers')">Copy</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    </main>
    <?php include("../func/bc-footer.php"); ?>

    <script>
        document.getElementById('filter_button').addEventListener('click', function() {
            var phone_numbers = document.getElementById('phone_numbers').value;
            var numbers = phone_numbers.split(/[\s,;\n]+/);

            var mtn_numbers = [];
            var glo_numbers = [];
            var airtel_numbers = [];
            var ninemobile_numbers = [];

            var mtn_prefixes = ["0803", "0806", "0703", "0706", "0813", "0816", "0810", "0814", "0903", "0906"];
            var glo_prefixes = ["0805", "0807", "0705", "0815", "0811", "0905"];
            var airtel_prefixes = ["0802", "0808", "0708", "0812", "0701", "0902", "0907"];
            var ninemobile_prefixes = ["0809", "0818", "0817", "0909", "0908"];

            for (var i = 0; i < numbers.length; i++) {
                var number = numbers[i].trim();
                if (number.length >= 4) {
                    var prefix = number.substring(0, 4);
                    if (mtn_prefixes.includes(prefix)) {
                        mtn_numbers.push(number);
                    } else if (glo_prefixes.includes(prefix)) {
                        glo_numbers.push(number);
                    } else if (airtel_prefixes.includes(prefix)) {
                        airtel_numbers.push(number);
                    } else if (ninemobile_prefixes.includes(prefix)) {
                        ninemobile_numbers.push(number);
                    }
                }
            }

            document.getElementById('mtn_numbers').value = mtn_numbers.join('\n');
            document.getElementById('glo_numbers').value = glo_numbers.join('\n');
            document.getElementById('airtel_numbers').value = airtel_numbers.join('\n');
            document.getElementById('ninemobile_numbers').value = ninemobile_numbers.join('\n');
        });

        function copyToClipboard(elementId) {
            var copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            document.execCommand("copy");
            alert("Copied the text: " + copyText.value);
        }
    </script>
</body>
</html>