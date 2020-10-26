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



//This is payment verification page
if ( isset($_REQUEST['prefid']) && !empty($_REQUEST['prefid'])) {

//post or get variable prefid is the payment ref id you sent with the payment. It should bear the name you gave to the ref
	
	$error = 0;
	
	$prefid = $_REQUEST["prefid"];

	$success_ref = $prefid;

	if($_REQUEST['r']=="success"){//I defined r as part of success URL on Paystack 
		$result = array();
		//The parameter after verify/ is the transaction reference to be verified
		$url = 'https://api.paystack.co/transaction/verify/'.$prefid;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt(
		  $ch, CURLOPT_HTTPHEADER, [
		    'Authorization: Bearer '.LIVESECRETKEY]
		);// I define LIVESECRETKEY in payStack_constants.php
		$request = curl_exec($ch);
		curl_close($ch);

		if ($request) {
		  $result = json_decode($request, true);
		}

		if (array_key_exists('data', $result) && array_key_exists('status', $result['data']) && ($result['data']['status'] === 'success')) {
			//"Transaction was successful"
			//Perform necessary action
			$success = true;
			//update database and credit the user
			$callUserBack = $con->query("SELECT * FROM transactionsTable INNER JOIN usersTable ON transactionsTable.userid=usersTable.userid WHERE transaction_ref_number='$prefid'") or die('Something went wrong');//I already stored ref_number when user initiated payment order, which was sent as prefid to the server. Now I want to know if it exists in db 

			$countCalled = $callUserBack->num_rows;

			if ($countCalled) {
				$fetchCalled = $callUserBack->fetch_assoc();
				$theuserid = $fetchCalled['userid'];
				$email = $fetchCalled['email'];
				$fulltoname = $fetchCalled['fname']." ".$fetchCalled['lname'];
				$current_status = $fetchCalled['trans_status'];
					
				if ($current_status == 0) {//Checking if transaction has never been completed 
					
					$transupdatestmt = $osmscon->prepare("UPDATE transactions SET trans_status = ?, reference_number = ? WHERE truserid = ? AND ods_trans_ref_number = ?") or die('Something went wrong');
					
					$transtatus = 1;
			        
			        $transbind = $transupdatestmt->bind_param("isis",$transtatus,$success_ref,$theuserid,$prefid) or die('Something went wrong');

			        $transexec = $transupdatestmt->execute() or die('Something went wrong');


					
			        	$msg = "Transaction was successful. Thank you! ";
			        	
			        	//process email or SMS notification if needed
			        	
			        }
			        else{
			        	$success = false;
			        	$errmsg = "Something went wrong.<p class='tcenter'>Please feel free to contact us.</p>";
			        }
			    }
			    elseif($current_status == 1){
			    	$success = false;
					$errmsg = "Transaction already processed and completed.";
			    }
			    else{
			    	$success = false;
					$errmsg = "An unexpected error occured!";
			    }
			}
			else{
				//$success = false;
				$error = 1;
				$errmsg = "Unexpected error encountered: Unlinked transaction.";
			}
		}else{
		  	//$success = false;
			$error = 1;
			$errmsg = "<p class='tcenter'>Transaction not successful. Please try again.</p>";
		}
	}//end success
	elseif ($_REQUEST['r']=="failed") {//set as part of my failed URL on Paystack server or inline redirection
		//$success = false;
		$error = 1;
		$errmsg = "<p class='tcenter'>Transaction was not successful. Please try again.</p>";
	}
	elseif ($_REQUEST['r']=="canceled") {
		//$success = false;
		$error = 1;
		$errmsg = "<p class='tcenter'>Transaction was canceled. You can try again.</p>";
	}
}
else{
	$error = 1;
	$errmsg = "<p class='tcenter'>Transaction was not successfully completed.<br>Required values are missing!</p>";
}

if (isset($msg)) {
	echo $msg;
}
elseif (isset($errmsg)) {
	echo $errmsg;
}
?>