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

    if(!$App->_hiddenThirdpartyActive()) throw new Exception("Permission denied", 1);

    $Balance = new Balance($User, $App, 'real');

} catch (\Exception $e) {
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>

<section class="kr-balance-credit">
  <section>
    <header>
      <span>Ask a widthdraw</span>
      <div onclick="_closeCreditForm();"> <svg class="lnr lnr-cross"><use xlink:href="#lnr-cross"></use></svg> </div>
    </header>
    <div class="spinner" style="display:none;"> </div>
    <section class="kr-balance-widthdraw">
      <div class="kr-balance-widthdraw-available">
        <label>Balance available</label>
        <span><?php echo $App->_formatNumber($Balance->_getBalanceValue(), 2); ?> $</span>
      </div>
      <?php if($Balance->_getBalanceValue() >= $App->_getMinimumWidthdraw()): ?>
        <div class="kr-balance-range">
          <input type="text" id="kr-credit-chosamount" kr-chosamount-min="<?php echo $App->_getMinimumWidthdraw(); ?>" kr-chosamount-max="<?php echo number_format($Balance->_getBalanceValue(), 2, '.', ''); ?>" name="kr-credit-chosamount" value="" />
        </div>
        <form class="kr-createwidthdraw" action="<?php echo APP_URL; ?>/app/modules/kr-trade/src/actions/askWidthdrawAction.php" method="post">
          <div>
            <span>Paypal email</span>
            <input type="text" name="kr_widthdraw_paypal" value="<?php echo $Balance->_getAskWidthdrawEmail(); ?>">
          </div>
          <input type="hidden" name="kr_widthdraw_amount" value="<?php echo $App->_getMinimumWidthdraw(); ?>">
          <input type="submit" class="btn btn-big btn-green" name="" value="Process">
        </form>
      <?php else: ?>
        <div class="kr-balance-widthdraw-minm">
          <span>You need to have at least <i><?php echo $App->_formatNumber($App->_getMinimumWidthdraw(), 2); ?> $</i> on your available balance</span>
        </div>
      <?php endif; ?>
    </section>
  </section>
</section>
