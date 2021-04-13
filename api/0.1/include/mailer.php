<?php

// Mailer.php

$mailHeaders = 'From: skylaski@skylaski.com' . "\r\n" .
    'Reply-To: skylaski@skylaski.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion() . "\r\n" .
    'Content-Type: text/html; charset="UTF-8"';

$subjectNewPlan = "Your Skylaski.com VPN Plan is Ready!";
$messageNewPlan = <<<EOT
<div style="background-color: #ffffff;">
	<center><img src="https://www0.skylaski.com/media/pics/bannermedium.png" width="30%"><hr>
	<h1>Welcome to Skylaski VPN!</h1>
	<h3>You can now <a href="https://www0.skylaski.com/sign-in/" target="_blank">login</a> and start managing your VPN Client(s).
		
		<br><br><p>If this is your first VPN Plan, you might want to take a look at <a href="https://discourse.skylaski.com/t/getting-started-with-skylaski-vpn/" target="_blank">Getting Started</a>, or the <a href="https://discourse.skylaski.com/t/skylaski-vpn-faq-frequently-asked-questions/">FAQ</a></p>
		
<br><br><p>Next, check out our <a href="https://discourse.skylaski.com">Community</a> for even more information and support!</p><br><br>

<p>Follow us on Social Media to learn more about Privacy and Security online.</p>

<a href="https://www.facebook.com/skylaskivpn" target="_blank"><img src="https://www0.skylaski.com/media/pics/social/icon-facebook-square-png-24x24.png"></a>
<a href="https://www.instagram.com/skylaskivpn" target="_blank"><img src="https://www0.skylaski.com/media/pics/social/ig-1-24x24.jpg"></a>
<a href="https://twitter.com/SkylaskiVPN" target="_blank"><img src="https://www0.skylaski.com/media/pics/social/iconfinder_twitter_173834-24x24.png"></a>
<a href="https://www.minds.com/skylaskivpn/" target="_blank"><img src="https://www0.skylaski.com/media/pics/social/bulb-15x24.png"></a>
	
<br><br><h1>Enjoy!</h1>
<p><a href="https://www0.skylaski.com" target="_blank">Skylaski.com</a></p>
<img src="https://www0.skylaski.com/media/pics/wingstiny.png" width="5%">
	
	
	</center>
	
</div>
EOT;

$subjectReferralConfirmed = "You're Skylaski.com Referral Reward is Here!";
$messageReferralConfirmed = <<<EOT
<div style="background-color: #ffffff;">
	<center><img src="https://www0.skylaski.com/media/pics/bannermedium.png" width="30%"><hr>
	<h1>You've referred a friend to Skylaski.com!</h1>
	<h3>We really appreciate your efforts! Have a free month of VPN services on us!
		
	
<br><br><h1>Thank You!</h1>
<p><a href="https://www0.skylaski.com" target="_blank">Skylaski.com</a></p>
<img src="https://www0.skylaski.com/media/pics/wingstiny.png" width="5%">
	
	
	</center>
	
</div>
EOT;

$subjectRenewal = "You're Skylaski VPN Plan Has Been Renewed!";
$messageRenewal = <<<EOT
<div style="background-color: #ffffff;">
	<center><img src="https://www0.skylaski.com/media/pics/bannermedium.png" width="30%"><hr>
	<h1>You've Renewed Your VPN Plan!</h1>
	<h3>We appreciate your business!</h3>
	
<br><br><h1>Enjoy!</h1>
<p><a href="https://www0.skylaski.com" target="_blank">Skylaski.com</a></p>
<img src="https://www0.skylaski.com/media/pics/wingstiny.png" width="5%">
	
	
	</center>
	
</div>
EOT;

$subjectUpgrade = "You're Skylaski VPN Plan Has Been Upgraded!";
$messageUpgrade = <<<EOT
<div style="background-color: #ffffff;">
	<center><img src="https://www0.skylaski.com/media/pics/bannermedium.png" width="30%"><hr>
	<h1>You've Upgraded Your VPN Plan!</h1>
	<h3>You now have access to additional VPN clients. Congratulations!
	
<br><br><h1>Enjoy!</h1>
<p><a href="https://www0.skylaski.com" target="_blank">Skylaski.com</a></p>
<img src="https://www0.skylaski.com/media/pics/wingstiny.png" width="5%">
	
	
	</center>
	
</div>
EOT;

$subjectDelete = "You're Account at Skylaski VPN has been Deleted";
$messageDelete = <<<EOT
<div style="background-color: #ffffff;">
	<center><img src="https://www0.skylaski.com/media/pics/bannermedium.png" width="30%"><hr>
	<h1>We've Deleted Your Account.</h1>
	<h3>Sorry to see you go.</h3>
	<p>If you'd like to provide us with some <a href="mailto:support@skylaski.com">feedback</a>, we'd greatly appreciate it!</p>	
<br><br><h1>Thanks for Giving Us a Shot!</h1>
<p><a href="https://www0.skylaski.com" target="_blank">Skylaski.com</a></p>
<img src="https://www0.skylaski.com/media/pics/wingstiny.png" width="5%">
	
	
	</center>
	
</div>
EOT;

$subjectExpired = "You're Account at Skylaski VPN has Expired";
$messageExpired = <<<EOT
<div style="background-color: #ffffff;">
	<center><img src="https://www0.skylaski.com/media/pics/bannermedium.png" width="30%"><hr>
	<h1>Your VPN Plan has Expired.</h1>
	<h3>Sorry to see you go.</h3>
	<p>If you'd like to provide us with some <a href="mailto:support@skylaski.com">feedback</a>, we'd greatly appreciate it!</p>	
<br><br><h1>Thanks for Giving Us a Shot!</h1>
<p><a href="https://www0.skylaski.com" target="_blank">Skylaski.com</a></p>
<img src="https://www0.skylaski.com/media/pics/wingstiny.png" width="5%">
	
	
	</center>
	
</div>
EOT;


function sendPlanEmail($to,$type) {
	
	switch($type){
	
		case "NewPlan":
			mail($to,$GLOBALS['subjectNewPlan'],$GLOBALS['messageNewPlan'],$GLOBALS['mailHeaders']);
		break;
	
		case "ReferralConfirmed":
			mail($to,$GLOBALS['subjectReferralConfirmed'],$GLOBALS['messageReferralConfirmed'],$GLOBALS['mailHeaders']);
		break;
		
		case "Renew":
			mail($to,$GLOBALS['subjectRenewal'],$GLOBALS['messageRenewal'],$GLOBALS['mailHeaders']);
		break;
		
		case "Upgrade":
			mail($to,$GLOBALS['subjectUpgrade'],$GLOBALS['messageUpgrade'],$GLOBALS['mailHeaders']);
		break;
		
		case "Expired":
			mail($to,$GLOBALS['subjectExpired'],$GLOBALS['messageExpired'],$GLOBALS['mailHeaders']);
		break;
	}
	
}

function sendRenewalReminder($to,$days){
	
	$message = <<<EOT
<div style="background-color: #ffffff;">
	<center><img src="https://www0.skylaski.com/media/pics/bannermedium.png" width="30%"><hr>
<h3>Your VPN Plan is About to Expire!</h3>
<p>You have $days days remaining on your current plan.</p>
<p>Visit your <a href="https://www0.skylaski.com/sign-in/">Profile</a> to upgrade or renew your plan!</p>
<p><a href="https://www.skylaski.com" target="_blank">Skylaski.com</a></p>
<img src="https://www0.skylaski.com/media/pics/wingstiny.png" width="5%">
</center>
</div>
	
EOT;

mail($to,"Your VPN Plan Will Expire in $days Days!",$message,$GLOBALS['mailHeaders']);

}

function sendDeleteEmail($to){
	mail($to,$GLOBALS['subjectDelete'],$GLOBALS['messageDelete'],$GLOBALS['mailHeaders']);
	
}

function sendReceiptEmail($to,$type,$checkoutid,$db_conn){
	
	//error_log("mail.php: Sending Receipt Email");
	//get checkout info
	$sql = "SELECT * FROM checkouts WHERE id=".$checkoutid."";
	$results = $db_conn->query($sql);
	$checkoutArr = $results->fetch_assoc();
	
	$date = $checkoutArr['create_date'];
	$invoice = $checkoutArr['code'];
	$payment = $checkoutArr['payment'];
	$details = $checkoutArr['details'];
	$price = $checkoutArr['price'];
	$coupon = $checkoutArr['coupon'];
	$totalprice = $checkoutArr['totalprice'];
	$referral = $checkoutArr['referral_code'];
	
	$message = <<<EOT
<style>
#invoice {
  font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
}
#invoice td, #invoice th {
  border: 1px solid #ddd;
  padding: 8px;
}
#invoice tr:nth-child(even){background-color: #f2f2f2;}
#invoice tr:hover {background-color: #ddd;}
#invoice th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #aa0000;
  color: white;
}
</style>
<div style="background-color: #ffffff;">
	<center><img src="https://www.skylaski.com/forum/styles/basic_red/theme/images/logo.png" width="30%"><hr>
<p>Here is a receipt for your purchase.</p>
<table id="invoice">
  <tr>
    <th>Date (UTC)</th>
    <th>Invoice</th>
    <th>Payment Method</th>
    <th>Payment Details/Code</th>
    <th>Price</th>
    <th>Coupon</th>
    <th>Total Price</th>
    <th>Referral</th>
  </tr>
  <tr>
    <td>$date</td>
    <td>$invoice</td>
    <td>$payment</td>
    <td>$details</td>
    <td>\$$price</td>
    <td>$coupon</td>
    <td>\$$totalprice</td>
    <td>$referral</td>
  </tr>
</table>
<br><br><h1>Enjoy!</h1>
<p><a href="https://www.skylaski.com" target="_blank">Skylaski.com</a></p>
<img src="https://www.skylaski.com/wp-content/uploads/2020/05/cropped-Matt-Wings-04.png" width="5%">
</center>
</div>
	
EOT;

	mail($to,"Receipt for Payment to Skylaski.com",$message,$GLOBALS['mailHeaders']);
	

}
		
if(isset($_GET['test'])){
	
	//mail("mpoletiek@skylaski.com",$GLOBALS['subjectRenewal'],$GLOBALS['messageRenewal'],$GLOBALS['mailHeaders']);
	
	sendDeleteEmail("mpoletiek@skylaski.com");
	
}	
		
		
?>
