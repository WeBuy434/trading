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

    if($App->_hiddenThirdpartyActive()){

      $Balance = new Balance($User, $App);
      $CurrentBalance = $Balance->_getCurrentBalance();

      $CryptoApi = new CryptoApi($User, ['USD', '$'], 'gdax');
      $Coin = new CryptoCoin($CryptoApi, $_POST['from']);


      $thirdPartyChoosen = $Trade->_getThirdParty($App->_hiddenThirdpartyServiceCfg()[$CurrentBalance->_getType()])[$App->_hiddenThirdpartyService()];


    } else {

      $CryptoApi = new CryptoApi($User, [$_POST['to'], '$'], $_POST['thirdparty']);
      $Coin = new CryptoCoin($CryptoApi, $_POST['from']);

      $listThirdPartyAvailable = $Trade->_thirdparySymbolTrading($_POST['from'], $_POST['to']);


      foreach ($listThirdPartyAvailable as $key => $thirdparty) {
        if($thirdparty->_getExchangeName() == $_POST['thirdparty']){
          $thirdPartyChoosen = $thirdparty;
          break;
        }
      }

    }

    if(is_null($thirdPartyChoosen)) throw new Exception("Error : Thirdparty not available", 1);

    if($App->_hiddenThirdpartyActive()){
      $ListTrade = $CurrentBalance->_getListTrade($_POST['from'], (isset($_POST['type']) && $_POST['type'] == 'update' ? (time() - 60) : 0));
      $ListTradeFormated = [];
      foreach ($ListTrade as $infosTrade) {
        $infosOrder = $thirdPartyChoosen->_getApi()->fetch_order($infosTrade['order_key_internal_order']);
        //error_log(json_encode($infosOrder));
        $DateTime = new DateTime($orderInfos['info']['created_at']);
        $ListTradeFormated[] = [
          'id' => $infosOrder['id'],
          'size' => $App->_formatNumber($infosTrade['usd_amount_internal_order'], 2).' $',
          'amount' => $App->_formatNumber($infosTrade['amount_internal_order'], ($infosTrade['amount_internal_order'] < 10 ? 5 : 2)).' '.$Coin->_getSymbol(),
          'side' => $infosOrder['info']['side'],
          'status' => $infosOrder['info']['status'],
          'date' => $DateTime->format('d/m/Y H:i:s'),
          'done_reason' => $infosOrder['info']['done_reason']
        ];
      }


    } else {

      $ListTradeFormated = [];
      foreach ($thirdPartyChoosen->_getOrderBook($thirdPartyChoosen->_formatPair($_POST['from'], $_POST['to'])) as $key => $infosOrder) {
        $ListTradeFormated[] = [
          'id' => $infosOrder['id'],
          'size' => $App->_formatNumber($infosOrder['total'], 2).' '.$infosOrder['total_currency'],
          'amount' => $App->_formatNumber($infosOrder['size'], ($infosOrder['size'] < 10 ? 5 : 2)).' '.$Coin->_getSymbol(),
          'date' => $infosOrder['date'],
          'side' => $infosOrder['type']
        ];
      }

    }



    die(json_encode([
      'error' => 0,
      'list_filled' => array_reverse($ListTradeFormated),
      'list_inc' => array_reverse($ListTradeFormated)
    ]));



} catch (\Exception $e) {
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>
