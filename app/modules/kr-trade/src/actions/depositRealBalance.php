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


    $Balance = new Balance($User, $App);

} catch (\Exception $e) {
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>
<div class="spinner" style="display:none;"></div>
<section class="kr-balance-credit-drel">
  <h3>Deposit amount</h3>
  <div class="kr-balance-range">
    <input type="text" id="kr-credit-chosamount" kr-chosamount-max="<?php echo $App->_getMaximalDeposit(); ?>" kr-chosamount-min="<?php echo $App->_getMinimalDeposit(); ?>" name="kr-credit-chosamount" value="" />
  </div>
  <div class="kr-credit-feescalc">
    <div kr-credit-calcfees="amount">
      <label>Amount</label>
      <span><i><?php echo $App->_formatNumber($App->_getMinimalDeposit(), 2); ?></i> $</span>
    </div>
    <div kr-credit-calcfees="fees" kr-credit-calcfees-am="2">
      <label>Fees (<?php echo $App->_formatNumber($App->_getFeesDeposit(), 2); ?> %)</label>
      <span><i><?php echo $App->_formatNumber($App->_getMinimalDeposit() * ($App->_getFeesDeposit() / 100), 2); ?></i> $</span>
    </div>
    <div kr-credit-calcfees="total">
      <label>Total</label>
      <input type="hidden" kr-charges-payment-vamdepo="cvmps" name="" value="<?php echo $App->_getMinimalDeposit(); ?>">
      <span><i><?php echo $App->_formatNumber($App->_getMinimalDeposit() + ($App->_getMinimalDeposit() * ($App->_getFeesDeposit() / 100)), 2); ?></i> $</span>
    </div>
  </div>
  <ul>
    <?php if($App->_paypalEnabled()):
      ?>
      <li kr-charges-payment="paypal">
        <a>
          <img src="<?php echo APP_URL.'/assets/img/icons/payment/paypal.svg'; ?>" alt="">
        </a>
      </li>
    <?php endif; ?>
    <?php if($App->_creditCardEnabled()): ?>
      <li kr-charges-payment="creditcard">
        <a>
          <img src="<?php echo APP_URL.'/assets/img/icons/payment/creditcard.svg'; ?>" alt="">
        </a>
      </li>
    <?php endif; ?>
    <?php if($App->_coingateEnabled()): ?>
    <li kr-charges-payment="coingate" kr-cng-lt="<?php echo time() - 2; ?>">
      <a>
        <img src="<?php echo APP_URL.'/assets/img/icons/payment/bitcoin.png'; ?>" alt="">
      </a>
    </li>
    <?php endif; ?>
    <?php if($App->_mollieEnabled()): ?>
      <li kr-charges-payment="mollie">
        <a>
          <img src="<?php echo APP_URL.'/assets/img/icons/payment/mollie.png'; ?>" alt="">
        </a>
      </li>
    <?php endif; ?>
  </ul>
</section>
