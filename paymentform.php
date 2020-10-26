<?php
define("LIVESECRETKEY", "sk_live_");
define("LIVEPUBLICKEY", "pk_live_");
define("TESTSECRETKEY", "sk_test_");
define("TESTPUBLICKEY", "pk_test_");
define("CHECKSTATUSURL", "http://www.paystack.co/pay/");
define("GATEWAYURL", "http://www.paystack.co/pay/");
define("PATH", 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']));
define("CALLBACKURL", "http://www.yoursite.com/pagethatreceivespaymentstatus");
define("WEBHOOK", "http://www.yoursite.com/webhookpage");//not compulsory but may read about it on payment documentation page


//start processing payment
//This section gets values for the variables from a form or database data
	$amount = isset($_POST["payment-amount"]) ? $_POST["payment-amount"] : 0;
	$amount = $amount*100;//coverted to kobo
	if (empty($amount) || $amount == 0) {
		$error = 1;
		$errmsg = "Invalid amount!<p class='tcenter'>Please <a href='buy'>try your request again.</a></p>";
	}
	$customerFName = isset($_POST["customer-first-name"]) ? $_POST["customer-first-name"] : "";
	$customerLName = isset($_POST["customer-last-name"]) ? $_POST["customer-last-name"] : "";
	$customerFullName = $customerFName." ".$customerLName;
	$customerEmail = isset($_POST["customer-email"]) ? $_POST["customer-email"] : "";
	if (empty($customerEmail) || email_spamcheck($customerEmail)==FALSE) {
		$error = 1;
		$errmsg = "Invalid email!<p class='tcenter'>Please <a href='buy'>try your request again.</a></p>";
	}
	$customerPhone = isset($_POST["customer-phone"]) ? $_POST["customer-phone"] : "";
	$customerUsername = isset($_POST["customer-username"]) ? $_POST["customer-username"] : "";
	$currency = isset($_POST['payment-currency-options']) ? $_POST['payment-currency-options'] : "NGN";
	$quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 0;

	$transref = isset($_POST["payment-ref"]) ? $_POST["payment-ref"] : "";

	$description = isset($_POST['description']) ? $_POST['description'] : "OrgDS SMS Purchase";
?>


<?php
//This is the guy that will process payment script
if ($error == false) {
	echo "<script type='text/javascript'>";
	echo " var pkey = '".LIVEPUBLICKEY."';";
	echo " var tpkey = '".TESTPUBLICKEY."';";
	echo " var callbackurl = '".CALLBACKURL."';";
	echo "</script>";
	?>
	<script src='st_includes/js/payment.js'></script>
	<script src="https://js.paystack.co/v1/inline.js"></script>
	<script type="text/javascript">
	  function payWithPaystack(){

	  	//write an ajax code that will update database

	  	var pamount = document.getElementById('psamt').value;
	  	var pmail = document.getElementById('customerEmail').value;
	  	var pref = document.getElementById('prefid').value;
	  	var phone = document.getElementById('customerPhone').value;
	    var handler = PaystackPop.setup({
	      key: pkey,
	      email: pmail,
	      amount: pamount,
	      ref: pref,
	      subaccount: '',
	      bearer: '',
	      metadata: {
	         custom_fields: [
	            {
	                display_name: "Mobile Number",
	                variable_name: "mobile_number",
	                value: phone
	            }
	         ]
	      },
	      callback: function(response){
	      	
	      	alert('Transaction was successful! Your transaction reference is ' + response.reference + "\nClick OK to continue...");
	      	
	      	document.getElementById('busygif').innerHTML = '';
      	
	      	var rdirurl = pagethatreceivespaymentstatus+"&prefid="+response.reference+"&r=success";

	      	giveUserValue(rdirurl);

	      },
	      onClose: function(){
	      	alert('Transaction was canceled! You can try again.');

	      	document.getElementById('busygif').innerHTML = '';

	      	var rdirurl = pagethatreceivespaymentstatus+"&prefid="+pref+"&r=canceled";
			
			giveUserValue(rdirurl);
	      }
	    });
	    handler.openIframe();
	  }

	</script>

	<form onsubmit="payWithPaystack()" id="paystack_form" name="paystack_form" method="POST">
		<!-- Supply the following data -->
		<input id="psamt" name="psamt" value="<?= $amount; ?>" type="hidden"/>
		<input id="customerName" name="customerName" value="<?= $customerFullName; ?>" type="hidden"/>
		<input id="customerEmail" name="customerEmail" value="<?= $customerEmail; ?>" type="hidden"/>
		<input id="customerPhone" name="customerPhone" value="<?= $customerPhone; ?>" type="hidden"/>
		<input id="prefid" name="prefid" value="<?= $transref; ?>" type="hidden" />
			<!-- <input type="submit" class="w3-red" id="chpassbtn" value="Click here to proceed" /> -->
			<script type="text/javascript">
			var newcontent = "<button type='button' id='makingdifferenttimer' onclick='payWithPaystack()' class='w3-red w3-padding-medium'>Click here to proceed</button>"
			jQuery(document).ready(function() {
			var sec = 5
			$("#makingdifferenttimer").hide(0);
			var timer = setInterval(function() {
			$("#mdtimer span").text(sec--);
				if (sec == 0) {
					$("#makingdifferenttimer").delay(1000).fadeIn(1000);
					$("#mdtimer").hide(1000) .fadeOut(fast);}
				},1000);
			});
		</script>
		<div id="mdtimer" class="tcenter">
			<b></b>
			<div style="font-size: large;">
				<b>Please wait <span>5</span> seconds for redirection</b>
			</div>
		</div>
		<div class="tcenter" id="makingdifferenttimer" style="font-size: large;">
			<h5><span id="waiting">Please wait</span> for redirection...</h5>
			<!-- <button type='button' id='makingdifferenttimer' onclick='payWithPaystack()' class='w3-red w3-padding-medium'>Click here to proceed</button> -->
		</div>
	</form>
	<script type="text/javascript">payWithPaystack();</script>