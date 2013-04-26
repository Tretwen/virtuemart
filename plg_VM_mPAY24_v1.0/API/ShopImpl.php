<?php

if (!class_exists('MPay24Shop')) {
	require('MPay24Shop.php');
}

/**
 * mPAY24 Shop Implementation used by mpay24.php
 * @author Tretwen
 * @version 1.0
 * @license http://ec.europa.eu/idabc/eupl.html EUPL, Version 1.1
 */
class ShopImpl extends MPay24Shop {
	private $data;

	/**
	 * @author support@mpay24.com
	 */
	function updateTransaction($tid, $args, $shippingConfirmed){}
	
	/**
	 * @author support@mpay24.com
	 */
	function write_log($operation, $info_to_log) {
		$fh = fopen("mpay24.log", 'a');
		if($fh !== FALSE) {
			fwrite($fh, $operation . ' ' . $info_to_log);
			fclose($fh);
		}
	}
	
	/**
	 * @author support@mpay24.com
	 */
	function getTransaction($tid) {
		if(file_exists("orders.xml")){
			$doc = new DOMDocument();
			$doc->load("orders.xml");
			$xmlOrders = $doc->getElementsByTagName("Orders")->item(0);
			$ordersNodeList = $doc->getElementsByTagName("Order");
			$ordersCount = $ordersNodeList->length;
		} else{
			$ordersCount = 0;
		}
		
		for($i = 0; $i < $ordersCount; $i++){
			if($ordersNodeList->item($i)->getElementsByTagName("Tid")->item(0)->nodeValue == $tid){
				$order = $ordersNodeList->item($i);
			}
		}
		
		$transaction = new Transaction($tid);
		//$transaction->MPAYTID = $order->getElementsByTagName("MPAYTid")->item(0)->nodeValue; 
		$transaction->STATUS = $order->getElementsByTagName("TransactionStatus")->item(0)->nodeValue;
		$transaction->APPR_CODE = $order->getElementsByTagName("TransactionApprCode")->item(0)->nodeValue;
		$transaction->P_TYPE = substr($order->getElementsByTagName("PaymentMethod")->item(0)->nodeValue, 0,
				strpos($order->getElementsByTagName("PaymentMethod")->item(0)->nodeValue, " "));
		$transaction->BRAND = substr($order->getElementsByTagName("PaymentMethod")->item(0)->nodeValue,
				strpos($order->getElementsByTagName("PaymentMethod")->item(0)->nodeValue, " "));
		//$transaction->ORDERDESC = $order->getElementsByTagName("OrderDesc")->item(0)->nodeValue; 
		$transaction->PRICE = $order->getElementsByTagName("TotalPrice")->item(0)->nodeValue;
		$transaction->CURRENCY = $order->getElementsByTagName("Currency")->item(0)->nodeValue;
		//$transaction->CUSTOMER_ID = $order->getElementsByTagName("CustomerId")->item(0)->nodeValue; 
		
		return $transaction;
	}
	
	function createProfileOrder($tid) {} // unsupported
	function createExpressCheckoutOrder($tid) {} // unsupported
	function createFinishExpressCheckoutOrder($tid, $shippingCosts, $amount, $cancel) {} // unsupported

	/**
	 * @author support@mpay24.com (example shop)
	 */
	function createSecret($tid, $amount, $currency, $timeStamp){
		$ts   = microtime();
		$rand = mt_rand();
		$seed = (string) $ts * $rand * $amount . $currency . $timeStamp . $tid;
	
		$secret = hash("sha1", $seed);
	
		return $secret;
	}
	
	function getSecret($tid) {}
	
	function createTransaction() {
		$transaction = new Transaction($this->data['Tid']);
		$transaction->PRICE = $this->data['Amount'];
		$transaction->CURRENCY = $this->data['Currency'];
		$transaction->LANGUAGE = $this->data['TemplateSet_Language'];
		
		if (!file_exists("orders.xml")){
			$doc = new DOMDocument("1.0", "UTF-8");
			$doc->formatOutput = true;
			$xmlOrders = $doc->createElement('Orders');
			$xmlOrders = $doc->appendChild($xmlOrders);
		} else{
			$doc = new DOMDocument();
			$doc->load("orders.xml");
			$xmlOrders = $doc->getElementsByTagName("Orders")->item(0);
		}
		
		$ordersNodeList = $doc->getElementsByTagName("Order");
		if($ordersNodeList->length == 24){
			$order = $xmlOrders->getElementsByTagName('Order')->item(0);
			$oldorder = $xmlOrders->removeChild($order);
		}
		$xmlOrder = $doc->createElement('Order');
		$xmlOrder = $xmlOrders->appendChild($xmlOrder);
		
		$xmlSecret = $doc->createElement('Secret', $this->createSecret($this->data['Tid'], number_format($this->data['Amount'],2,'.',''),
				$this->data['Currency'], $this->data['Tid']));
		$xmlSecret = $xmlOrder->appendChild($xmlSecret);
		
		$xmlTimeStamp = $doc->createElement('TimeStamp', $this->data['Tid']);
		$xmlTimeStamp = $xmlOrder->appendChild($xmlTimeStamp);
		
		$xmlTid = $doc->createElement('Tid', $this->data['Tid']);
		$xmlTid = $xmlOrder->appendChild($xmlTid);
		$xmlMpayTid = $doc->createElement('mPAYTid', 'N/A');
		$xmlMpayTid = $xmlOrder->appendChild($xmlMpayTid);
		$xmlTransactionStatus = $doc->createElement('TransactionStatus', 'N/A');
		$xmlTransactionStatus = $xmlOrder->appendChild($xmlTransactionStatus);
		$xmlTransactionApprCode = $doc->createElement('TransactionApprCode', 'N/A');
		$xmlTransactionApprCode = $xmlOrder->appendChild($xmlTransactionApprCode);
		$xmlPaymentMethod = $doc->createElement('PaymentMethod', 'N/A');
		$xmlPaymentMethod = $xmlOrder->appendChild($xmlPaymentMethod);
		
		$xmlBillingAddress = $doc->createElement('BillingAddress');
		$xmlBillingAddress = $xmlOrder->appendChild($xmlBillingAddress);

		$xmlBillingAddressName = $doc->createElement('Name', $this->data['BillingAddr']['Name']);
		$xmlBillingAddressName = $xmlBillingAddress->appendChild($xmlBillingAddressName);
		$xmlBillingAddressEmail = $doc->createElement('Email', $this->data['BillingAddr']['Email']);
		$xmlBillingAddressEmail = $xmlBillingAddress->appendChild($xmlBillingAddressEmail);
		$xmlBillingAddressStreet = $doc->createElement('Street', $this->data['BillingAddr']['Street']
				. " " . $this->data['BillingAddr']['Street2']);
		$xmlBillingAddressStreet = $xmlBillingAddress->appendChild($xmlBillingAddressStreet);
		$xmlBillingAddressCity = $doc->createElement('City', $this->data['BillingAddr']['Zip']
				. " " . $this->data['BillingAddr']['City']);
		$xmlBillingAddressCity = $xmlBillingAddress->appendChild($xmlBillingAddressCity);
		$xmlBillingAddressCountry = $doc->createElement('Country', $this->data['BillingAddr']['Country']);
		$xmlBillingAddressCountry = $xmlBillingAddress->appendChild($xmlBillingAddressCountry);
		
		$xmlShippingAddress = $doc->createElement('ShippingAddress');
		$xmlShippingAddress = $xmlOrder->appendChild($xmlShippingAddress);
		
		if(isset($this->data['ShippingAddr'])) {
			$xmlShippingAddressName = $doc->createElement('Name', $this->data['ShippingAddr']['Name']);
			$xmlShippingAddressName = $xmlShippingAddress->appendChild($xmlShippingAddressName);
			$xmlShippingAddressEmail = $doc->createElement('Email', $this->data['ShippingAddr']['Email']);
			$xmlShippingAddressEmail = $xmlShippingAddress->appendChild($xmlShippingAddressEmail);
			$xmlShippingAddressStreet = $doc->createElement('Street', $this->data['ShippingAddr']['Street']
				. " " . $this->data['ShippingAddr']['Street2']);
			$xmlShippingAddressStreet = $xmlShippingAddress->appendChild($xmlShippingAddressStreet);
			$xmlShippingAddressCity = $doc->createElement('City', $this->data['ShippingAddr']['Zip']
				. " " . $this->data['ShippingAddr']['City']);
			$xmlShippingAddressCity = $xmlShippingAddress->appendChild($xmlShippingAddressCity);
			$xmlShippingAddressCountry = $doc->createElement('Country', $this->data['ShippingAddr']['Country']);
			$xmlShippingAddressCountry = $xmlShippingAddress->appendChild($xmlShippingAddressCountry);
		} else{
			$xmlShippingAddressName = $doc->createElement('Name', $this->data['BillingAddr']['Name']);
			$xmlShippingAddressName = $xmlShippingAddress->appendChild($xmlShippingAddressName);
			$xmlShippingAddressEmail = $doc->createElement('Email', $this->data['BillingAddr']['Email']);
			$xmlShippingAddressEmail = $xmlShippingAddress->appendChild($xmlShippingAddressEmail);
			$xmlShippingAddressStreet = $doc->createElement('Street', $this->data['BillingAddr']['Street']
					. " " . $this->data['BillingAddr']['Street2']);
			$xmlShippingAddressStreet = $xmlShippingAddress->appendChild($xmlShippingAddressStreet);
			$xmlShippingAddressCity = $doc->createElement('City', $this->data['BillingAddr']['Zip']
				. " " . $this->data['BillingAddr']['City']);
			$xmlShippingAddressCity = $xmlShippingAddress->appendChild($xmlShippingAddressCity);
			$xmlShippingAddressCountry = $doc->createElement('Country', $this->data['BillingAddr']['Country']);
			$xmlShippingAddressCountry = $xmlShippingAddress->appendChild($xmlShippingAddressCountry);
		}
		
		if(isset($this->data['ShoppingCart']['Description'])) {
			$xmlOrderDesc = $doc->createElement('OrderDesc', $this->data['ShoppingCart']['Description']);
			$xmlOrderDesc = $xmlOrder->appendChild($xmlOrderDesc);
		}
		
		$xmlItems = $doc->createElement('Items');
		$xmlItems = $xmlOrder->appendChild($xmlItems);
		
		for($i = 0; $i < count($this->data['ShoppingCart']['Item']); $i++) {
			$xmlItem = $doc->createElement('Item');
			$xmlItem = $xmlItems->appendChild($xmlItem);
			
			if(isset($this->data['ShoppingCart']['Item'][$i]['Number'])) {
				$xmlNumber  = $doc->createElement('Number', $this->data['ShoppingCart']['Item'][$i]['Number']);
				$xmlNumber  = $xmlItem->appendChild($xmlNumber);
			}
			if(isset($this->data['ShoppingCart']['Item'][$i]['ProductNr'])){
				$xmlProductNumber = $doc->createElement('ProductNr', $this->data['ShoppingCart']['Item'][$i]['ProductNr']);
				$xmlProductNumber = $xmlItem->appendChild($xmlProductNumber);
			}
			if(isset($this->data['ShoppingCart']['Item'][$i]['Description'])){
				$xmlDescription= $doc->createElement('Description', $this->data['ShoppingCart']['Item'][$i]['Description']);
				$xmlDescription= $xmlItem->appendChild($xmlDescription);
			}
			if(isset($this->data['ShoppingCart']['Item'][$i]['Package'])){
				$xmlPackage = $doc->createElement('Package', $this->data['ShoppingCart']['Item'][$i]['Package']);
				$xmlPackage = $xmlItem->appendChild($xmlPackage);
			}
			if(isset($this->data['ShoppingCart']['Item'][$i]['Quantity'])){
				$xmlQuantity= $doc->createElement('Quantity', $this->data['ShoppingCart']['Item'][$i]['Quantity']);
				$xmlQuantity= $xmlItem->appendChild($xmlQuantity);
			}
			if(isset($this->data['ShoppingCart']['Item'][$i]['Price'])){
				$xmlItemPrice  = $doc->createElement('ItemPrice', $this->data['ShoppingCart']['Item'][$i]['Price']);
				$xmlItemPrice  = $xmlItem->appendChild($xmlItemPrice);
				$xmlItemPrice->setAttribute('Tax', $this->data['ShoppingCart']['Item'][$i]['ItemTax']);
			}
			if(isset($this->data['ShoppingCart']['Item'][$i]['Price'])){
				$xmlPrice= $doc->createElement('Price', $this->data['ShoppingCart']['Item'][$i]['Price']);
				$xmlPrice= $xmlItem->appendChild($xmlPrice);
			}
		}
		
		if(isset($this->data['ShoppingCart']['SubTotal']) || isset($this->data['ShoppingCart']['Discount']) ||
		   isset($this->data['ShoppingCart']['DiscountC']) || isset($this->data['ShoppingCart']['ShippingCosts']) ||
		   isset($this->data['ShoppingCart']['Tax'])) {
		
			$xmlOtherPaymentDetails = $doc->createElement('OtherPaymentDetails');
			$xmlOtherPaymentDetails = $xmlOrder->appendChild($xmlOtherPaymentDetails);
			
			if(isset($this->data['ShoppingCart']['SubTotal'])) {
				$xmlRow = $doc->createElement('SubTotal', number_format($this->data['ShoppingCart']['SubTotal'], 2, '.', ''));
				$xmlRow = $xmlOtherPaymentDetails->appendChild($xmlRow);
			}
			if(isset($this->data['ShoppingCart']['Discount'])) {
				$xmlRow = $doc->createElement('SubTotal', number_format($this->data['ShoppingCart']['Discount'], 2, '.', ''));
				$xmlRow = $xmlOtherPaymentDetails->appendChild($xmlRow);
			}
			if(isset($this->data['ShoppingCart']['DiscountC'])) {
				$xmlRow = $doc->createElement('SubTotal', number_format($this->data['ShoppingCart']['DiscountC'], 2, '.', ''));
				$xmlRow = $xmlOtherPaymentDetails->appendChild($xmlRow);
			}
			if(isset($this->data['ShoppingCart']['ShippingCosts'])) {
				$xmlRow = $doc->createElement('SubTotal', number_format($this->data['ShoppingCart']['ShippingCosts'], 2, '.', ''));
				$xmlRow = $xmlOtherPaymentDetails->appendChild($xmlRow);
			}
			if(isset($this->data['ShoppingCart']['Tax'])) {
				$xmlRow = $doc->createElement('SubTotal', number_format($this->data['ShoppingCart']['Tax'], 2, '.', ''));
				$xmlRow = $xmlOtherPaymentDetails->appendChild($xmlRow);
			}
		}
		
		$xmlTotalPrice = $doc->createElement('TotalPrice', number_format($this->data['Amount'], 2, '.', ''));
		$xmlTotalPrice = $xmlOrder->appendChild($xmlTotalPrice);
		
		$xmlCurrency = $doc->createElement('Currency', $this->data['Currency']);
		$xmlCurrency = $xmlOrder->appendChild($xmlCurrency);
		
		$ordersFile = $doc->saveXML();
            
        $myFile = "orders.xml";
        $fh = fopen($myFile, 'w') or die("can't open file");
        fwrite($fh, $ordersFile);
        fclose($fh);
        
		return $transaction;
	}

	function createMDXI($transaction) {
		$mdxi = new ORDER();
		$mdxi->Order->setStyle("margin-left: auto; margin-right: auto;");
		
		$mdxi->Order->UserField = "User Field ".$transaction->TID;
		$mdxi->Order->Tid = $transaction->TID;
		
		if(isset($this->data['TemplateSet'])) {
			$mdxi->Order->TemplateSet = $this->data['TemplateSet'];
		} else {
			$mdxi->Order->TemplateSet = "WEB";
		}
		$mdxi->Order->TemplateSet->setLanguage($transaction->LANGUAGE);
		
		if($this->data['PaymentTypes'] != NULL && count($this->data['PaymentTypes'][0]) > 0 && count($this->data['PaymentTypes'][1]) > 0) {
			if(count($this->data['PaymentTypes'][0]) < count($this->data['PaymentTypes'][1])) {
				// disable payment types
				$mdxi->Order->PaymentTypes->setEnable("false");
				$paymentTypes = $this->data['PaymentTypes'][0];
			} else {
				// enable payment types
				$mdxi->Order->PaymentTypes->setEnable("true");
				$paymentTypes = $this->data['PaymentTypes'][1];
			}
			$i = 1;
			foreach($paymentTypes as $singlePayment) {
				$mdxi->Order->PaymentTypes->Payment($i)->setType($singlePayment[0]);
				if($singlePayment[0] != $singlePayment[1]) {
					$mdxi->Order->PaymentTypes->Payment($i)->setBrand($singlePayment[1]);
				}
				$i++;
			}
		}
		
		if(isset($this->data['ShoppingCart']['Description'])) {
			$mdxi->Order->ShoppingCart->Description = $this->data['ShoppingCart']['Description'];
		}
		
		for($i = 0; $i < count($this->data['ShoppingCart']['Item']); $i++) {
			if(isset($this->data['ShoppingCart']['Item'][$i]['Number']))
				$mdxi->Order->ShoppingCart->Item(($i+1))->Number = $this->data['ShoppingCart']['Item'][$i]['Number'];
			$mdxi->Order->ShoppingCart->Item(($i+1))->ProductNr = $this->data['ShoppingCart']['Item'][$i]['ProductNr'];
			$mdxi->Order->ShoppingCart->Item(($i+1))->Description = $this->data['ShoppingCart']['Item'][$i]['Description'];
			if(isset($this->data['ShoppingCart']['Item'][$i]['Package']))
				$mdxi->Order->ShoppingCart->Item(($i+1))->Package = $this->data['ShoppingCart']['Item'][$i]['Package'];
			$mdxi->Order->ShoppingCart->Item(($i+1))->Quantity = $this->data['ShoppingCart']['Item'][$i]['Quantity'];
			$mdxi->Order->ShoppingCart->Item(($i+1))->ItemPrice = $this->data['ShoppingCart']['Item'][$i]['Price'];
			$mdxi->Order->ShoppingCart->Item(($i+1))->ItemPrice->setTax($this->data['ShoppingCart']['Item'][$i]['ItemTax']);
			$mdxi->Order->ShoppingCart->Item(($i+1))->Price = $this->data['ShoppingCart']['Item'][$i]['Price'];
		}
		
		if(isset($this->data['ShoppingCart']['SubTotal'])) 
			$mdxi->Order->ShoppingCart->SubTotal(1, number_format($this->data['ShoppingCart']['SubTotal'], 2, '.', ''));
		if(isset($this->data['ShoppingCart']['Discount']))
			$mdxi->Order->ShoppingCart->Discount(1, $this->data['ShoppingCart']['Discount']);
		if(isset($this->data['ShoppingCart']['DiscountC'])) 
			$mdxi->Order->ShoppingCart->Discount(1, $this->data['ShoppingCart']['DiscountC']);
		if(isset($this->data['ShoppingCart']['ShippingCosts'])) 
			$mdxi->Order->ShoppingCart->ShippingCosts(1, $this->data['ShoppingCart']['ShippingCosts']);
		if(isset($this->data['ShoppingCart']['Tax'])) 
			$mdxi->Order->ShoppingCart->Tax(1, $this->data['ShoppingCart']['Tax']);
		
		
		$mdxi->Order->Price = $transaction->PRICE;
		$mdxi->Order->Currency = $transaction->CURRENCY;
		
		$mdxi->Order->BillingAddr->setMode($this->data['BillingAddr']['Mode']);
		$mdxi->Order->BillingAddr->Name = $this->data['BillingAddr']['Name'];
		$mdxi->Order->BillingAddr->Street = $this->data['BillingAddr']['Street'];
		if(isset($this->data['BillingAddr']['Street2']))
			$mdxi->Order->BillingAddr->Street2 = $this->data['BillingAddr']['Street2'];
		$mdxi->Order->BillingAddr->Zip = $this->data['BillingAddr']['Zip'];
		$mdxi->Order->BillingAddr->City = $this->data['BillingAddr']['City'];
		if(isset($this->data['BillingAddr']['State']))
			$mdxi->Order->BillingAddr->State = $this->data['BillingAddr']['State'];
		$mdxi->Order->BillingAddr->Country->setCode($this->data['BillingAddr']['Country']);
		$mdxi->Order->BillingAddr->Email = $this->data['BillingAddr']['Email'];
				
		$mdxi->Order->ShippingAddr->setMode('ReadOnly');
		if(isset($this->data['ShippingAddr'])) { 
			$mdxi->Order->ShippingAddr->Name = $this->data['ShippingAddr']['Name'];
			$mdxi->Order->ShippingAddr->Street = $this->data['ShippingAddr']['Street'];
			if(isset($this->data['ShippingAddr']['Street2']))
				$mdxi->Order->ShippingAddr->Street2 = $this->data['ShippingAddr']['Street2'];
			$mdxi->Order->ShippingAddr->Zip = $this->data['ShippingAddr']['Zip'];
			$mdxi->Order->ShippingAddr->City = $this->data['ShippingAddr']['City'];
			if(isset($this->data['ShippingAddr']['State']))
					$mdxi->Order->ShippingAddr->State = $this->data['ShippingAddr']['State'];
			$mdxi->Order->ShippingAddr->Country->setCode($this->data['ShippingAddr']['Country']);
			$mdxi->Order->ShippingAddr->Email = $this->data['ShippingAddr']['Email'];
		} else {
			$mdxi->Order->ShippingAddr->Name = $this->data['BillingAddr']['Name'];
			$mdxi->Order->ShippingAddr->Street = $this->data['BillingAddr']['Street'];
			if(isset($this->data['BillingAddr']['Street2']))
				$mdxi->Order->ShippingAddr->Street2 = $this->data['BillingAddr']['Street2'];
			$mdxi->Order->ShippingAddr->Zip = $this->data['BillingAddr']['Zip'];
			$mdxi->Order->ShippingAddr->City = $this->data['BillingAddr']['City'];
			if(isset($this->data['BillingAddr']['State']))
				$mdxi->Order->ShippingAddr->State = $this->data['BillingAddr']['State'];
			$mdxi->Order->ShippingAddr->Country->setCode($this->data['BillingAddr']['Country']);
			$mdxi->Order->ShippingAddr->Email = $this->data['BillingAddr']['Email'];
		}
		
		$mdxi->Order->URL->Success = $this->data['URL']['Success'];
		$mdxi->Order->URL->Error = $this->data['URL']['Error'];
		$mdxi->Order->URL->Confirmation = $this->data['URL']['Confirmation'];
		$mdxi->Order->URL->Cancel = $this->data['URL']['Cancel'];
				
		return $mdxi;
	}

	function provideData($data) { // as array
		$this->data = $data;
	}
}


