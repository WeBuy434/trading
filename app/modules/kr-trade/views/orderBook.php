<?php

/**
 * Load order book
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

session_start();

require "../../../../config/config.settings.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/vendor/autoload.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/MySQL/MySQL.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/App.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/AppModule.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/User/User.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/Lang/Lang.php";

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

    $Lang = new Lang($User->_getLang(), $App);

} catch(Exception $e){
  die($e->getMessage());
}

if(!$App->_hiddenThirdpartyActive()):

  $Trade = new Trade($User, $App);
  $listThirdParty = $Trade->_getThirdPartyListAvailable();
  $selectedThirdParty = $listThirdParty[0];
  //$balanceList = $selectedThirdParty->_getBalance(true);
  $BookList = $selectedThirdParty->_getOrderBook();
  if(count($BookList) == 0){
    echo '<section>'.$Lang->tr('No order to show').'</section>';
  } else {
  echo '<ul class="kr-bookorder-thirdparty">';
  foreach ($selectedThirdParty->_getOrderBook() as $orderDetails) {
    ?>

      <li class="kr-bookorder-side-<?php echo $orderDetails['type']; ?>">
        <div>
          <div>
            <span><?php echo $orderDetails['market']; ?></span>
          </div>
          <span><?php echo $App->_formatNumber($orderDetails['size'], 5).' '.$orderDetails['symbol']; ?></span>
        </div>
        <div class="">
          <span><?php echo $orderDetails['date']; ?></span>
          <span><?php echo $App->_formatNumber($orderDetails['total'], 2).' '.$orderDetails['total_currency']; ?></span>
        </div>
      </li>

    <?php
  }

  echo '</ul>';
}

else:
  $CryptoApi = new CryptoApi($User, null, $App);

  $Balance = new Balance($User, $App);
  $CurrentBalance = $Balance->_getCurrentBalance();
?>
<div class="kr-orderbookside-resum">
  <div>
    <div>
      <span>Current Profit</span>
    </div>
    <div class="<?php echo ($CurrentBalance->_getBalanceEvolution($CryptoApi)['evolv'] < 0 ? 'negativ-profit' : ($CurrentBalance->_getBalanceEvolution($CryptoApi)['evolv'] > 0 ? 'positiv-profit' : '')); ?>">
      <span><?php echo $App->_formatNumber($CurrentBalance->_getBalanceEvolution($CryptoApi)['total'] - $CurrentBalance->_getBalanceInvestisment(), 2); ?> $</span>
      <span><?php echo $App->_formatNumber($CurrentBalance->_getBalanceEvolution($CryptoApi)['evolv'], 2); ?> %</span>
    </div>
  </div>
  <div>
    <div>
      <span>Investment</span>
    </div>
    <div>
      <span><?php echo $App->_formatNumber($CurrentBalance->_getBalanceInvestisment(), 2); ?> $</span>
    </div>
  </div>
</div>
<?php
$BookList = $CurrentBalance->_getOrderResumBySymbol($CryptoApi);
if(count($BookList) == 0):
?>
  <section><?php echo $Lang->tr('No order to show'); ?></section>
<?php else: ?>
<ul>
  <?php
  foreach ($BookList as $OrderDetails) {
    $OrderCoin = $OrderDetails['coin'];
  ?>
  <li>
    <div>
      <div>
        <img src="<?php echo $OrderCoin->_getIcon(); ?>" alt="">
        <span><?php echo $OrderCoin->_getCoinName(); ?></span>
      </div>
      <span><?php echo $App->_formatNumber($OrderDetails['evolv']['contain'], 5).' '.$OrderCoin->_getSymbol(); ?></span>
    </div>
    <div class="<?php echo ($OrderDetails['evolv']['evolv'] < 0 ? 'negativ-profit' : ($OrderDetails['evolv']['evolv'] > 0 ? 'positiv-profit' : '')); ?>">
      <span><?php echo $App->_formatNumber($OrderDetails['evolv']['total'] - $CurrentBalance->_getBalanceInvestisment($OrderCoin->_getSymbol()), 2); ?> $</span>
      <span><?php echo $App->_formatNumber($OrderDetails['evolv']['evolv'], 2); ?> %</span>
    </div>
  </li>
  <?php
}
  ?>
</ul>
<?php endif; ?>
<?php endif; ?>
