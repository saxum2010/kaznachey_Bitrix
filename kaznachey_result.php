<?
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');
    
$hrpd = json_decode($HTTP_RAW_POST_DATA);
error_log(serialize($hrpd));
if(isset($_GET['status']))
{
	$status = $_GET['status'];
	if(($status == 'success')||($status == 'fail')||($status == 'deferred'))
	{
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
		$mm = ($status == 'success')?iconv("UTF-8","CP1251","Ваш заказ оплачен"):iconv("UTF-8","CP1251","Произошла ошибка во время оплаты заказа");
		
		if($status == 'deferred'){
			$mm = iconv("UTF-8","CP1251","Спасибо за Ваш заказ.	Вы сможете оплатить его после проверки менеджером. Ссылка на оплату будет выслана Вам по электронной почте.");
		}
		
		$APPLICATION->SetTitle($mm);
			print "<h1>$mm</h1>"; 

		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
	
	}elseif($status == 'done')
	{

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(isset($hrpd->MerchantInternalPaymentId)){
	if($hrpd->ErrorCode == 0)
	{
		if (CModule::IncludeModule('sale'))
		{

		  $order_id = intval($hrpd->MerchantInternalPaymentId);
		  if ($arOrder = CSaleOrder::GetByID(IntVal($order_id)))
		  {
			CSalePaySystemAction::InitParamArrays($arOrder, $arOrder["ID"]);
			
			$urlGetMerchantInfo = 'http://payment.kaznachey.net/api/PaymentInterface/CreatePayment';
			$urlGetClientMerchantInfo = 'http://payment.kaznachey.net/api/PaymentInterface/GetMerchatInformation';
			$merchantGuid = CSalePaySystemAction::GetParamValue("MerchantId");
			$merchnatSecretKey =  CSalePaySystemAction::GetParamValue("SecretKey");
			$order_id = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]); 
			$selectedPaySystemId = 1;
			$quantitys = 0;

			$order_info = CSaleOrder::GetByID($order_id);
			$user_info = CSaleOrderUserProps::GetByID($order_info['USER_ID']);
			$user_fullinfo = CSaleOrderUserPropsValue::GetByID($order_info['USER_ID']);
			$user_email = $USER->GetParam("EMAIL");
			$user_id	= $order_info['USER_ID'];
			$amount = number_format($order_info['PRICE'], 2, '.', '');
			
			$dbBasketItems = CSaleBasket::GetList(Array(),Array("ORDER_ID"=>$order_id));
			while ($arItems = $dbBasketItems->Fetch())
			{
				$quantitys += $arItems['QUANTITY'];
			}

			if($order_info['PRICE_DELIVERY']>0)
			{
				$quantitys += 1;
			}

			$quantitys = number_format($quantitys, 2, '.', '');	

			$signature_u = md5(md5(
				$merchantGuid.
				$merchnatSecretKey.
				"$amount".
				"$quantitys".
				$order_id
			));
			
			if($hrpd->CustomMerchantInfo == $signature_u)
			{
				$arFields = array(
					"PS_STATUS" => "Y",
					"PS_STATUS_CODE" => "-",
					"PS_STATUS_DESCRIPTION" => $strPS_STATUS_DESCRIPTION,
					"PS_STATUS_MESSAGE" => $strPS_STATUS_MESSAGE,
					"PS_SUM" => $out_summ,
					"PS_CURRENCY" => "",
					"PS_RESPONSE_DATE" => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG))),
					"USER_ID" => $arOrder["USER_ID"]
				);
					
			$arFields["PAYED"] = "Y";
			$arFields["DATE_PAYED"] = Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG)));
			$arFields["EMP_PAYED_ID"] = false;

			if (CSaleOrder::Update($arOrder["ID"], $arFields))
			{
				if ($err == '')
				{
					echo 'Ok';
				}
			}else{
				$err = 'Error on update order';
				}
			}
				
		  }
		}
	}
}
	}
}


?>