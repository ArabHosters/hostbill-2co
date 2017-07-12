<?php



	hbm_create('twocheckout',array(
            'description'=>'2checkout Module for HostBill by ArabHosters',
            'version'=>'1.0',
            'currencies'=>array('USD','EGP','SAR')
        ));
    



	hbm_add_config_option('Merchant ID');
	
	hbm_add_config_option('Secret Word');

	hbm_add_config_option('Return URL');
	//hbm_add_config_option('Hash Total');
	  

    hbm_on_action('payment.displayform', function($details){

        $hostbill_details = hbm_get_hostbill_details();
        $cc_url = 'https://www.2checkout.com/checkout/spurchase';
        $merchant_id=hbm_get_config_option('Merchant ID');

        //This will create url to callback route created below
        $callback_url = hbm_client_url('callback');



	$api = new ApiWrapper();
	$return = $api->getCurrencies();
	
	$price = $details['invoice']['amount'];
	
	if($details['invoice']['currency'] != 'USD'){
		foreach($return['currencies'] as $cur){
			if($cur['iso'] == $details['invoice']['currency'])
			
				$price = money_format('%.2n', $details['invoice']['amount']/$cur['rate']);
		}
	}
/*
	if($details['invoice']['currency']=='SAR'){
		$price = $details['invoice']['amount']/$return['currencies'][0]['rate'];
	}elseif($details['invoice']['currency']=='EGP'){
		$price = $details['invoice']['amount']/$return['currencies'][1]['rate'];
	}else{
		$price = $details['invoice']['amount'];
	}
*/	

        $form = '<form action="'.$cc_url.'" method="post" name="payform">
			<input type="hidden" name="skip_landing" value="1">
			<input type="hidden" name="sid" value="'.$merchant_id.'">
			<input type="hidden" name="lang" value="en">
			<input type="hidden" name="first_name" value="'.$details['client']['firstname'].'">
			<input type="hidden" name="last_name" value="'.$details['client']['lastname'].'">
			<input type="hidden" name="street_address" value="'.$details['client']['address1'].'">
			<input type="hidden" name="street_address2" value="'.$details['client']['address2'].'">
			<input type="hidden" name="city" value="'.$details['client']['city'].'">
			<input type="hidden" name="zip" value="'.$details['client']['postcode'].'">
			<input type="hidden" name="country" value="'.$details['client']['country'].'">
			<input type="hidden" name="state" value="'.$details['client']['state'].'">
			<input type="hidden" name="phone" value="'.$details['client']['phonenumber'].'">
			<input type="hidden" name="currency_code" value="USD">
			<input type="hidden" name="total" value="'.$price.'">
			<input type="hidden" name="merchant_order_id" value="'.$details['invoice']['id'].'">
			<input type="hidden" name="cart_order_id" value="'.$details['invoice']['id'].'">
			<input type="hidden" name="c_name" value="'.$details['invoice']['description'].'">
			<input type="hidden" name="c_prod" value="'.$details['invoice']['id'].'">
			<input type="hidden" name="c_price" value="'.$price.'">
			<input type="hidden" name="fixed" value="Y">
			<input type="hidden" name="return_url" value="'.$callback_url.'">
			<input type="hidden" name="email" value="'.$details['client']['email'].'">
			<input type="submit" value="ادفع الآن">
	</form>';


     
        return $form;
    });


	hbm_client_route('callback',function($request) {


		if ($_REQUEST['demo'] == 'Y') 
		{
		    $order_number = 1;
		}
		else
		{
		    $order_number = $_REQUEST['order_number'];
		}
	
		$secretWord = hbm_get_config_option('Secret Word');    //2Checkout Secret Word
		$sid = hbm_get_config_option('Merchant ID');    //2Checkout account number
		
		$string_to_hash = $secretWord.$sid.$order_number.$_POST["total"];

		$check_key = strtoupper(md5($string_to_hash));
		

		$return_url=hbm_get_config_option('Return URL');
		if ($check_key != $_REQUEST['key']) {
			$result = "Fail - Hash Mismatch";
			echo $result;
		} else {
			
			
			$api = new ApiWrapper();
		   $params = array(
			  'id'=>$_POST['merchant_order_id']
		   );
		   $return = $api->getInvoiceDetails($params);
		   $total =  $return['invoice']['total'];
		   
			hbm_log_callback($_POST,'Successfull');
			hbm_add_transaction( $_POST['merchant_order_id'],$total,array(
                                'description' => $_POST['merchant_order_id'],
                                'transaction_id' => $_POST['merchant_order_id']
                            ));
			
 			echo '<META HTTP-EQUIV="Refresh" Content="0; URL='.$return_url.$_POST['merchant_order_id'].'"/>';  
		}
		


	});


