<?
include_once $_SERVER['DOCUMENT_ROOT'] . 'amo/create_lead.php';
//print_r($_SERVER['DOCUMENT_ROOT'] . 'amo/create_lead.php');

AddEventHandler("iblock", "OnAfterIBlockElementAdd", "OnAfterIBlockElementAddHandler");

function OnAfterIBlockElementAddHandler(&$arFields) {

  if(!$arFields["RESULT"])
      return false;

  if(in_array($arFields['IBLOCK_ID'], [22, 23, 38, 24])){

    $cities = [
      'Тула',
      'Троицк',
      'Санкт-Петербург',
      'Екатеринбург',
      'Тюмень',
      'Пермь',
      'Самара',
      'Тольятти',
      'Уфа',
      'Челябинск',
    ];
    $amoCrm = new AmoCRM();
    $lead_data = array();

    $lead_data['NAME'] =  $arFields["PROPERTY_VALUES"]["NAME"];
    $lead_data['PHONE'] =  $arFields["PROPERTY_VALUES"]["PHONE"];
    $lead_data['EMAIL'] = $arFields["PROPERTY_VALUES"]["EMAIL"];
    $lead_data['COMPANY'] = $arFields["PROPERTY_VALUES"]["COMPANY"];
    $lead_data['TEXT'] = $arFields["PROPERTY_VALUES"]["MESSAGE"]["VALUE"]["TEXT"];

    if(in_array($arFields["PROPERTY_VALUES"]["CITY"], $cities)) {
      $lead_data['CITY'] = $arFields["PROPERTY_VALUES"]["CITY"];
    } else {
      $lead_data['TEXT'] .= ' Город: ' .  $arFields["PROPERTY_VALUES"]["CITY"];
    }

    $lead_data['LEAD_NAME'] = 'Заявка с сайта docker-service';

    amoCRM::add_lead($lead_data);
  }
}
