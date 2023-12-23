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

    $Balance = new Balance($User, $App);

} catch (\Exception $e) {
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>

<section class="kr-balance-credit-cblance">
  <?php foreach ($Balance->_getBalanceList() as $BalanceItem) { ?>
    <section class="kr-balance-credit-choose-<?php echo $BalanceItem->_getBalanceType(); ?>">
      <img src="<?php echo APP_URL; ?>/app/modules/kr-trade/statics/img/<?php echo $BalanceItem->_getBalanceType(); ?>.svg" alt="">
      <h3><?php echo $BalanceItem->_getBalanceType(); ?> account</h3>
      <span class="kr-balance-credit-b-ammc"><?php echo $App->_formatNumber($BalanceItem->_getBalanceValue(), 2); ?> $</span>
      <div kr-balance-credit="cmd" kr-balance-idc="<?php echo $BalanceItem->_getBalanceID(true); ?>" kr-balance-type="<?php echo $BalanceItem->_getBalanceType(); ?>" class="btn btn-big btn-autowidth btn-<?php echo ($BalanceItem->_getBalanceType() == "practice" ? 'orange' : 'green'); ?> <?php echo ($BalanceItem->_limitReached() ? 'kr-balance-credit-dibl' : ''); ?>">
        <?php if($BalanceItem->_getBalanceType() == "real"): ?>
          <span>Add real funds</span>
          <span>Minimal deposit : <?php echo $App->_formatNumber($App->_getMinimalDeposit(), 2); ?> $</span>
        <?php else: ?>
          <span>Fill up to <?php echo $App->_formatNumber($App->_getMaximalFreeDeposit(), 2); ?></span>
          <span>It's free</span>
        <?php endif; ?>
      </div>
    </section>
  <?php } ?>
</section>
