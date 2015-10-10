<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) 
  die();

	include(GetLangFileName(dirname(__FILE__)."/", "/payment.php"));

	$dateInsert = (strlen(CSalePaySystemAction::GetParamValue("DATE_INSERT")) > 0) ? CSalePaySystemAction::GetParamValue("DATE_INSERT") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"];
	$currency = (strlen(CSalePaySystemAction::GetParamValue("CURRENCY")) > 0) ? CSalePaySystemAction::GetParamValue("CURRENCY") : $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["CURRENCY"];

	$urlGetMerchantInfo = 'http://payment.kaznachey.net/api/PaymentInterface/CreatePayment';
	$urlGetClientMerchantInfo = 'http://payment.kaznachey.net/api/PaymentInterface/GetMerchatInformation';
	$merchantGuid = CSalePaySystemAction::GetParamValue("MerchantId");
	$merchnatSecretKey =  CSalePaySystemAction::GetParamValue("SecretKey");
	$ResultURL =  CSalePaySystemAction::GetParamValue("ResultURL");
	$order_id = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]); 
	$selectedPaySystemId = GetMerchnatInfo(false, 1);
	$quantitys = 0;

	$order_info = CSaleOrder::GetByID($order_id);
	$user_info = CSaleOrderUserProps::GetByID($order_info['USER_ID']);
	$user_fullinfo = CSaleOrderUserPropsValue::GetByID($order_info['USER_ID']);
	$user_email = $USER->GetParam("EMAIL");
	$user_id	= $order_info['USER_ID'];
	$amount = number_format($order_info['PRICE'], 2, '.', '');

    $path = 'http://' . $_SERVER['HTTP_HOST'];
	$ResultURL = CSalePaySystemAction::GetParamValue("ResultURL");
    $path_success = $ResultURL . '?status=success';
    $path_fail = $ResultURL . '?status=fail';
    $path_done = $ResultURL . '?status=done';

	$ii = 0;

	if(CModule::IncludeModule('iblock'))
	{ 
		$dbBasketItems = CSaleBasket::GetList(Array(),Array("ORDER_ID"=>$order_id));
		while ($arItems = $dbBasketItems->Fetch())
		{
			$res = CIBlockElement::GetByID($arItems['PRODUCT_ID']);
			if($element = $res->GetNext()){
				$products[$ii]['ImageUrl'] 	 =   $path . CFile::GetPath($element["PREVIEW_PICTURE"]);
			}
			
			$products[$ii]['ProductId'] 	 = $arItems['PRODUCT_ID'];
			$products[$ii]['ProductName'] 	 = $arItems['NAME'];
			$products[$ii]['ProductPrice'] 	 = number_format($arItems['PRICE'], 2, '.', '');
			$products[$ii]['ProductItemsNum'] = number_format($arItems['QUANTITY'], 2, '.', '');
			$quantitys += $arItems['QUANTITY'];
			$ii++;
		}
	}
	
	if($order_info['PRICE_DELIVERY']>0)
	{
		$products[$ii]['ProductId'] 	 = '00001';
		$products[$ii]['ProductName'] 	 = 'Delivery';
		$products[$ii]['ProductPrice'] 	 = number_format($order_info['PRICE_DELIVERY'], 2, '.', '');
		$products[$ii]['ProductItemsNum'] = number_format(1, 2, '.', '');
		$quantitys += 1;
	}

	$ii = 0;
	$db_props = CSaleOrderPropsValue::GetOrderProps($order_id);
	while ($arProps = $db_props->Fetch())
	{

		if($arProps['CODE'] == 'PHONE')
		{
			$userEnteredFields[$ii]['FieldTag'] = 'PhoneNumber';
			$userEnteredFields[$ii]['FieldValue'] = $arProps['VALUE'];
			$user_phone = $arProps['VALUE'];
		}
		
		if($arProps['CODE'] == 'FIO')
		{
			$DeliveryLastname = $arProps['VALUE'];
		}			
		
		if($arProps['CODE'] == 'ADDRESS')
		{
			$DeliveryStreet = $arProps['VALUE'];
		}	
		
		if($arProps['CODE'] == 'EMAIL')
		{
			$userEnteredFields[$ii]['FieldTag'] = 'E-Mail';
			$userEnteredFields[$ii]['FieldValue'] = $arProps['VALUE'];
			$user_email = $arProps['VALUE'];
		}
		$ii++;
	}

	$quantitys = number_format($quantitys, 2, '.', '');	
	
	$signature_u = md5(md5(
		$merchantGuid.
		$merchnatSecretKey.
		"$amount".
		"$quantitys".
		$order_id
	));
	
	$BuyerFirstname 	= $DeliveryFirstname = "";
	$BuyerLastname		= $DeliveryLastname;
	$BuyerStreet		= $DeliveryStreet;

    $paymentDetails = Array(
       "MerchantInternalPaymentId"=>"$order_id",
       "MerchantInternalUserId"=>"$user_id",
       "EMail"=>"$user_email",
       "PhoneNumber"=>"$user_phone",
       "CustomMerchantInfo"=>"$signature_u",
       "StatusUrl"=>"$path_done",
       "ReturnUrl"=>"$path_success", 
       "BuyerCountry"=>"$BuyerCountry",
       "BuyerFirstname"=>"$BuyerFirstname",
       "BuyerPatronymic"=>"$BuyerPatronymic",
       "BuyerLastname"=>"$BuyerLastname",
       "BuyerStreet"=>"$BuyerStreet",
       "BuyerZone"=>"$BuyerZone",
       "BuyerZip"=>"$BuyerZip",
       "BuyerCity"=>"$BuyerCity",
       "DeliveryFirstname"=>"$DeliveryFirstname",
       "DeliveryLastname"=>"$DeliveryLastname",
       "DeliveryZip"=>"$DeliveryZip",
       "DeliveryCountry"=>"$DeliveryCountry",
       "DeliveryPatronymic"=>"$DeliveryPatronymic",
       "DeliveryStreet"=>"$DeliveryStreet",
       "DeliveryCity"=>"$DeliveryCity",
       "DeliveryZone"=>"$DeliveryZone",
    );
	
	$signature = md5(
		$merchantGuid.
		"$amount".
		"$quantitys".
		$paymentDetails["MerchantInternalUserId"].
		$paymentDetails["MerchantInternalPaymentId"].
		$selectedPaySystemId.
		$merchnatSecretKey
	);	
	
	$request = Array(
        "MerchantGuid"=>$merchantGuid,
        "Signature"=>$signature,
        "PaymentDetails"=>$paymentDetails, 
        "SelectedPaySystemId"=>$selectedPaySystemId,
        "Products"=>$products,
        "Fields"=>$userEnteredFields,
		"Currency"=>CSalePaySystemAction::GetParamValue("KaznacheyCurrency"),
    );

$res = sendRequestKaznachey($urlGetMerchantInfo, json_encode($request));
$result = json_decode($res,true);
print base64_decode($result["ExternalForm"]);

function GetMerchnatInfo($id = false, $def = false)
{
	$urlGetClientMerchantInfo = 'http://payment.kaznachey.net/api/PaymentInterface/GetMerchatInformation';
	$merchantGuid = CSalePaySystemAction::GetParamValue("MerchantId");
	$merchnatSecretKey =  CSalePaySystemAction::GetParamValue("SecretKey");

	$requestMerchantInfo = Array(
		"MerchantGuid"=>$merchantGuid,
		"Signature"=>md5($merchantGuid.$merchnatSecretKey)
	);

	$resMerchantInfo = json_decode(sendRequestKaznachey($urlGetClientMerchantInfo , json_encode($requestMerchantInfo)),true); 
	
	if($id)
	{
		foreach ($resMerchantInfo["PaySystems"] as $key=>$paysystem)
		{
			if($paysystem['Id'] == $id)
			{
				return $paysystem;
			}
		}
	}elseif($def)
	{
		foreach ($resMerchantInfo["PaySystems"] as $key=>$paysystem)
		{
			return $paysystem['Id'];
		}
	}else{
		return $resMerchantInfo;
	}
}	

function sendRequestKaznachey($url,$data)
{

/*   	array_walk_recursive($data, function(&$value,$key){
	   $value=iconv("CP1251","UTF-8",$value);
	});  */

	$curl =curl_init();
	if (!$curl)
		return false;
	curl_setopt($curl, CURLOPT_URL,$url );
	curl_setopt($curl, CURLOPT_POST,true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, 
			array("Expect: ","Content-Type: application/json; charset=UTF-8",'Content-Length: ' 
				. strlen($data)));
	curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,True);
	$res =  curl_exec($curl);
	curl_close($curl);

	return $res;
}

?>