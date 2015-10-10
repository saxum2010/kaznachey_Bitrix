<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) 
  die();

include(GetLangFileName(dirname(__FILE__)."/", "/payment.php"));

$psTitle = GetMessage("SPCP_DTITLE");
$psDescription = GetMessage("SPCP_DDESCR");
$path = 'http://' . $_SERVER['HTTP_HOST'];

$arPSCorrespondence = array(
	"MerchantId" => array(
		"NAME" => GetMessage("MerchantId"),
		"DESCR" => GetMessage("MerchantId_DESCR"),
		"VALUE" => "",
		"TYPE" => ""
		),
	"SecretKey" => array(
		"NAME" => GetMessage("SecretKey"),
		"DESCR" => GetMessage("SecretKey_DESCR"),
		"VALUE" => "",
		"TYPE" => ""
		),
	"ResultURL" => array(
		"NAME" => GetMessage("ResultURL"),
		"DESCR" => GetMessage("ResultURL_DESCR"),
		"VALUE" => "$path/kaznachey_result.php",
		"TYPE" => ""
	),
	"KaznacheyCurrency" => array(
		"NAME" => GetMessage("CURRENCY"),
		"DESCR" => GetMessage("CURRENCY_DESCR"),
		"VALUE" => array(
			'UAH' => array('NAME' => "UAH"),
			'USD' => array('NAME' => "USD"),
			'EUR' => array('NAME' => "EUR"),
			'RUB' => array('NAME' => "RUB"),
		),
		"TYPE" => "SELECT"
	)
	
	);
?>
