<?php

/**
 * Admin payment settings
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

session_start();

require "../../../../config/config.settings.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/MySQL/MySQL.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/App.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/AppModule.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/User/User.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/Lang/Lang.php";

// Load app modules
$App = new App(true);
$App->_loadModulesControllers();

// Check loggin & permission
$User = new User();
if(!$User->_isLogged()) throw new Exception("User are not logged", 1);
if(!$User->_isAdmin()) throw new Exception("Permission denied", 1);

// Init language object
$Lang = new Lang($User->_getLang(), $App);

// Init admin object
$Admin = new Admin();
?>
<form class="kr-admin kr-adm-post-evs" action="<?php echo APP_URL; ?>/app/modules/kr-admin/src/actions/savePayment.php" method="post">
  <nav class="kr-admin-nav">
    <ul>
      <?php foreach ($Admin->_getListSection() as $key => $section) { // Get list admin section
        echo '<li type="module" kr-module="admin" kr-view="'.strtolower(str_replace(' ', '', $section)).'" '.($section == 'Payment' ? 'class="kr-admin-nav-selected"' : '').'>'.$Lang->tr($section).'</li>';
      } ?>
    </ul>
  </nav>
  <div class="kr-admin-line kr-admin-line-cls">
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Payment success'); ?></label>
      </div>
      <div>
        <input type="text" name="kr-adm-paymentdoneresult" value="<?php echo $App->_getPaymentResultDone(); ?>">
      </div>
    </div>
  </div>
  <div class="kr-admin-line kr-admin-line-cls">
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Enable credit card'); ?></label>
      </div>
      <div>
        <div class="ckbx-style-14">
            <input type="checkbox" id="kr-adm-chk-creditcard" <?php echo ($App->_creditCardEnabled() ? 'checked' : ''); ?> name="kr-adm-chk-creditcard">
            <label for="kr-adm-chk-creditcard"></label>
        </div>
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Stipe private key'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your Stripe API Key'); ?>" name="kr-adm-stripekey" value="<?php echo ($App->_getPrivateStripeKey() != '' ? '*********************' : ''); ?>">
        <span>Stripe dashboard > API > Secret key (click on reveal key token)</span>
      </div>
    </div>
  </div>
  <div class="kr-admin-line kr-admin-line-cls">
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Enable Paypal'); ?></label>
      </div>
      <div>
        <div class="ckbx-style-14">
            <input type="checkbox" id="kr-adm-chk-enablepaypal" <?php echo ($App->_paypalEnabled() ? 'checked' : ''); ?> name="kr-adm-chk-enablepaypal">
            <label for="kr-adm-chk-enablepaypal"></label>
        </div>
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Enable Paypal Live mode'); ?></label>
      </div>
      <div>
        <div class="ckbx-style-14">
            <input type="checkbox" id="kr-adm-chk-enablepaypallive" <?php echo ($App->_paypalLiveModeEnabled() ? 'checked' : ''); ?> name="kr-adm-chk-enablepaypallive">
            <label for="kr-adm-chk-enablepaypallive"></label>
        </div>
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Paypal client ID'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your paypal client ID'); ?>" name="kr-adm-paypalclientid" value="<?php echo ($App->_getPaypalClientID() != '' ? '*********************' : ''); ?>">
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Paypal Client Secret'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your paypal client Secret'); ?>" name="kr-adm-paypalclientsecret" value="<?php echo ($App->_getPaypalClientSecret() != '' ? '*********************' : ''); ?>">
      </div>
    </div>
  </div>
  <div class="kr-admin-line kr-admin-line-cls">
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Enable Fortumo'); ?></label>
      </div>
      <div>
        <div class="ckbx-style-14">
            <input type="checkbox" id="kr-adm-chk-enablefortumo" <?php echo ($App->_fortumoEnabled() ? 'checked' : ''); ?> name="kr-adm-chk-enablefortumo">
            <label for="kr-adm-chk-enablefortumo"></label>
        </div>
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Fortumo Secret key'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your fortumo secret key'); ?>" name="kr-adm-fortumosecretkey" value="<?php echo ($App->_getFortumoSecretKey() != '' ? '*********************' : ''); ?>">
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Fortumo Service key'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your fortumo service key'); ?>" name="kr-adm-fortumoservicekey" value="<?php echo ($App->_getFortumoServiceKey() != '' ? '*********************' : ''); ?>">
      </div>
    </div>
  </div>
  <div class="kr-admin-line kr-admin-line-cls">
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Enable Coingate'); ?></label>
      </div>
      <div>
        <div class="ckbx-style-14">
            <input type="checkbox" id="kr-adm-chk-enablecoingate" <?php echo ($App->_coingateEnabled() ? 'checked' : ''); ?> name="kr-adm-chk-enablecoingate">
            <label for="kr-adm-chk-enablecoingate"></label>
        </div>
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Enable CoinGate live mode'); ?></label>
      </div>
      <div>
        <div class="ckbx-style-14">
            <input type="checkbox" id="kr-adm-chk-coingatelivemode" <?php echo ($App->_coingateLiveMode() ? 'checked' : ''); ?> name="kr-adm-chk-coingatelivemode">
            <label for="kr-adm-chk-coingatelivemode"></label>
        </div>
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('CoinGate APP ID'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your CoinGate APP ID'); ?>" name="kr-adm-coingateappid" value="<?php echo ($App->_getCoingateAppID() != '' ? '*********************' : ''); ?>">
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('CoinGate API Key'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your CoinGate API Key'); ?>" name="kr-adm-coingateapikey" value="<?php echo ($App->_getCoingateApiKey() != '' ? '*********************' : ''); ?>">
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('CoinGate API Secret'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your CoinGate API Secret'); ?>" name="kr-adm-coingateapisecret" value="<?php echo ($App->_getCoingateApiSecret() != '' ? '*********************' : ''); ?>">
      </div>
    </div>
  </div>
  <div class="kr-admin-line kr-admin-line-cls">
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Enable Mollie'); ?></label>
      </div>
      <div>
        <div class="ckbx-style-14">
            <input type="checkbox" id="kr-adm-chk-enablemollie" <?php echo ($App->_mollieEnabled() ? 'checked' : ''); ?> name="kr-adm-chk-enablemollie">
            <label for="kr-adm-chk-enablemollie"></label>
        </div>
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Mollie Key'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your Mollie Key'); ?>" name="kr-adm-molliekey" value="<?php echo ($App->_getMollieKey() != '' ? '*********************' : ''); ?>">
      </div>
    </div>
  </div>
  <div class="kr-admin-action">
    <input type="submit" class="btn btn-orange" name="" value="<?php echo $Lang->tr('Save'); ?>">
  </div>
</form>
