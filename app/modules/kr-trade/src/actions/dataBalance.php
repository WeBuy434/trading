<?php

/**
 * Load data balance
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

    $CryptoApi = new CryptoApi($User, null, $App);

    if($App->_hiddenThirdpartyActive()){

      $thirdPartyChoosen = null;
      $Trade = new Trade($User, $App);

      $Balance = new Balance($User, $App);
      $CurrentBalance = $Balance->_getCurrentBalance();

      $BalanceReturned = [];
      foreach ($Balance->_getBalanceList() as $BalanceItem) {
        $BalanceReturned[] = [
          'enc_id' => $BalanceItem->_getBalanceID(true),
          'balance' => $BalanceItem->_getBalanceValue() * 100,
          'balance_investment' => $BalanceItem->_getBalanceInvestisment(),
        ];

      }

      die(json_encode([
        'error' => 0,
        'balance' => $BalanceReturned,
        'type' => 'native',
        'current_balance' => [
          'title' => $CurrentBalance->_getBalanceType().' account',
          'available' => $CurrentBalance->_getBalanceValue() * 100,
          'investment' => $CurrentBalance->_getBalanceInvestisment()* 100,
          'profit_dollar' => ($CurrentBalance->_getBalanceEvolution($CryptoApi)['total'] - $CurrentBalance->_getBalanceInvestisment()) * 100,
          'profit_percentage' => $CurrentBalance->_getBalanceEvolution($CryptoApi)['evolv'] * 100,
          'profit_class' => ($CurrentBalance->_getBalanceEvolution($CryptoApi)['evolv'] < 0 ? 'kr-wallet-top-negativ' : ($CurrentBalance->_getBalanceEvolution($CryptoApi)['evolv'] == 0 ? '' : 'kr-wallet-top-positiv')),
          'total' => $CurrentBalance->_getBalanceTotal($CryptoApi) * 100
        ]
      ]));

    } else {

      $Trade = new Trade($User, $App);
      $listThirdParty = $Trade->_getThirdPartyListAvailable();
      if(count($listThirdParty) > 0){
        $selectedThirdParty = $listThirdParty[0];
        $balanceList = $selectedThirdParty->_getBalance(true);
        //error_log(json_encode($balanceList));
        $balanceSelectedSymbol = null;
        $balanceSelectedAmount = null;
        foreach ($balanceList as $key => $value) {
          if(!is_null($balanceSelectedSymbol)) continue;
          $balanceSelectedSymbol = $key;
          $balanceSelectedAmount = $value['free'];
        }

        $balanceListFormated = [];
        foreach (array_slice($balanceList, 0, 12) as $symbol => $infosBalance) {
          $balanceListFormated[] = [
            'symbol' => $symbol,
            'amount' => $infosBalance['free']
          ];
        }

        die(json_encode([
          'error' => 0,
          'type' => 'external',
          'exchange_title' => $selectedThirdParty->_getName(),
          'exchange_name' => $selectedThirdParty->_getExchangeName(),
          'first_balance' => $balanceSelectedAmount,
          'first_balance_symbol' => $balanceSelectedSymbol,
          'show_more' => count($balanceList) > 12,
          'balances' => $balanceListFormated
        ]));

      } else {
        die(json_encode([
          'error' => 0,
          'type' => 'none'
        ]));
      }

    }


} catch (\Exception $e) {
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>
