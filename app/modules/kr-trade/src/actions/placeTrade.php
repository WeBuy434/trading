<?php

/**
 * Load chart data
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

session_start();

require "../../../../../config/config.settings.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/vendor/autoload.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/MySQL/MySQL.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/App.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/AppModule.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/User/User.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoOrder.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoNotification.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoIndicators.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoGraph.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoHisto.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoCoin.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoApi.php";

// Load app modules
$App = new App(true);
$App->_loadModulesControllers();

try {

    // Check if user is logged
    $User = new User();
    if (!$User->_isLogged()) {
        throw new Exception("Error : User is not logged", 1);
    }


    $thirdPartyChoosen = null;
    $Trade = new Trade($User, $App);
    $CurrentBalance = null;

    if(empty($_POST) || !isset($_POST['from'])) throw new Exception("Permission denied", 1);


    if($App->_hiddenThirdpartyActive()){

      $Balance = new Balance($User, $App);
      $CurrentBalance = $Balance->_getCurrentBalance();

      if(strtolower($_POST['side']) == "buy" && $CurrentBalance->_getBalanceValue() < $_POST['amount']) throw new Exception("Insufficient funds", 1);

      if(strtolower($_POST['side']) == "sell" && $CurrentBalance->_getAmountCrypto($_POST['from']) < $_POST['amount']) throw new Exception('Insufficient '.$_POST['from'].' ('.$CurrentBalance->_getAmountCrypto($_POST['from']).' '.$_POST['from'].')', 1);


      $CryptoApi = new CryptoApi($User, ['USD', '$']);
      $Coin = new CryptoCoin($CryptoApi, $_POST['from']);

      $thirdPartyChoosen = $Trade->_getThirdParty($App->_hiddenThirdpartyServiceCfg()[$CurrentBalance->_getType()])[$App->_hiddenThirdpartyService()];



    } else {

      $listThirdPartyAvailable = $Trade->_thirdparySymbolTrading($_POST['from'], $_POST['to']);

      if(!isset($_POST['to'])) throw new Exception("Permission denied", 1);

      foreach ($listThirdPartyAvailable as $key => $thirdparty) {
        if($thirdparty->_getExchangeName() == $_POST['thirdparty']){
          $thirdPartyChoosen = $thirdparty;
          break;
        }
      }

    }


    if(is_null($thirdPartyChoosen)) throw new Exception("Error : Thirdparty not available", 1);

    try {

      if(!$App->_hiddenThirdpartyActive()){
        if(!$thirdPartyChoosen->_isActivated()) die(json_encode([
          'error' => 3,
          'thirdparty' => $thirdPartyChoosen->_getExchangeName()
        ]));
      }

      if($_POST['type'] == "market"){
        $result = $thirdPartyChoosen->_createOrder($thirdPartyChoosen::_formatPair($_POST['from'], $_POST['to']), $_POST['type'], $_POST['side'], $_POST['amount'], [], $CurrentBalance);
      } else {
        if($App->_hiddenThirdpartyActive()) throw new Exception("Limit order not available", 1);
      
        $result = $thirdPartyChoosen->_createOrderLimit($thirdPartyChoosen::_formatPair($_POST['from'], $_POST['to']), $_POST['amount_limit'], $_POST['price_limit'], $_POST['side']);
      }
    } catch (\Exception $e) {
      die(json_encode([
        'error' => 2,
        'msg' => $e->getMessage()
      ]));
    }

    // if($_POST['type'] == "market"){
    //   $CryptoApi = new CryptoApi(null, null, $App);
    //
    //   // Init coin associate to the graph
    //   $Coin = new CryptoCoin($CryptoApi, $_POST['from'], null, $App);
    //
    //   $CryptoOrder = new CryptoOrder($Coin);
    //   $CryptoOrder->_createOrder($User, $_POST['date'], $_POST['side'], $_POST['amount'], $_POST['to']);
    // }

    die(json_encode([
      'error' => 0,
      'msg' => 'Success !'
    ]));



} catch (\Exception $e) {
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>
