<?php

/**
 * WatchingList item view
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

session_start();

require "../../../../../config/config.settings.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/MySQL/MySQL.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/User/User.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/App.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/AppModule.php";
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
  if(!$User->_isLogged()) throw new Exception("User are not logged", 1);

  // Check args
  if(empty($_POST) || empty($_POST['symb'])) throw new Exception("Error : Args missing", 1);

  // Init CryptoApi object
  $CryptoApi = new CryptoApi(null, null, $App);

  // Get coin data
  $Coin = $CryptoApi->_getCoin($_POST['symb']);

  // Get graph data
  $ShortGraph = $Coin->_getHistoShortGraph($Coin->_getHistoMin(1440));

  // Init watching list
  $WatchingList = new WatchingList($CryptoApi, $User);

  // If item need to be added --> add
  if(!empty($_POST['t']) && $_POST['t'] == "add"){
    $WatchingList->_addItem($Coin->_getSymbol());
  }

} catch (Exception $e) { // If error detected, show error
  die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>
<li class="kr-wtchl-item animated flipInX <?php if($Coin->_getCoin24Evolv() < 0) echo 'kr-wtchl-neg'; ?>" symbol="<?php echo $Coin->_getSymbol(); ?>" currency="<?php echo $CryptoApi->_getCurrency(); ?>" pasth="<?php echo $Coin->_getOldPrice(); ?>">
  <div>
    <div class="kr-wtchl-inf">
        <div class="kr-wtchl-inf-pic" <?php if(is_null($Coin->_getIcon())) echo 'style="width:10px;"'; ?>>
          <?php if(!is_null($Coin->_getIcon())): ?>
            <?php echo file_get_contents($Coin->_getIcon()); ?>
          <?php endif; ?>
        </div>

      <div class="kr-wtchl-inf-nm kr-mono">
        <label><?php echo $Coin->_getCoinName(); ?></label>
        <span>(<?php echo $Coin->_getSymbol(); ?>)</span>
      </div>
    </div>
    <div class="kr-wtchl-cls">
      <label class="kr-mono" kr-data="CHANGE24HOURPCT">
        <span><?php echo round($Coin->_getCoin24Evolv(), 2); ?></span>%
      </label>
    </div>
  </div>
  <div class="kr-wtchl-data">
    <div class="kr-wtchl-cls">
      <div>
        <span class="kr-mono" kr-data="PRICE"><?php echo $App->_formatNumber($Coin->_getPrice(), 2); ?></span>
        <label class="kr-mono" kr-data="TOSYMBOL"><?php echo $CryptoApi->_getCurrency(); ?></label>
      </div>
    </div>

    <div class="kr-wtchl-data-grph kr-coin-graph" symbol="<?php echo $Coin->_getSymbol(); ?>" yv="<?php echo $Coin->_getChartValue('y', $ShortGraph); ?>" xv="<?php echo $Coin->_getChartValue('x', $ShortGraph); ?>">
      <canvas></canvas>
    </div>
  </div>
</li>
