<?php

/**
 * Admin news social settings
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
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoApi.php";

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

// Init dashboard object
$Dashboard = new Dashboard(new CryptoApi(null, null, $App), $User);

$TradingCredentials = $App->_hiddenThirdpartyServiceCfg();

?>
<form class="kr-admin kr-adm-post-evs" action="<?php echo APP_URL; ?>/app/modules/kr-admin/src/actions/saveTrading.php" method="post">
  <nav class="kr-admin-nav">
    <ul>
      <?php foreach ($Admin->_getListSection() as $key => $section) { // Get list admin section
        echo '<li type="module" kr-module="admin" kr-view="'.strtolower(str_replace(' ', '', $section)).'" '.($section == 'Trading' ? 'class="kr-admin-nav-selected"' : '').'>'.$Lang->tr($section).'</li>';
      } ?>
    </ul>
  </nav>

  <div class="kr-admin-line kr-admin-line-cls">
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Enable native trading'); ?></label>
      </div>
      <div>
        <div class="ckbx-style-14">
            <input type="checkbox" id="kr-adm-chk-enablenativetrading" <?php echo ($App->_hiddenThirdpartyActive() ? 'checked' : ''); ?> name="kr-adm-chk-enablenativetrading">
            <label for="kr-adm-chk-enablenativetrading"></label>
        </div>
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('Native engine'); ?></label>
      </div>
      <div>
        <select name="kr-adm-nativetradingengine">
          <option value="gdax">Gdax</option>
        </select>
      </div>
    </div>
  </div>
  <div class="kr-admin-line kr-admin-line-cls">
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('GDAX Api key (real account)'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your GDAX Api key (real account)'); ?>" name="kr-adm-gdaxapikeyreal" value="<?php echo(array_key_exists('real', $TradingCredentials)
                                                                                                                                                    && array_key_exists('0', $TradingCredentials['real'])
                                                                                                                                                    && !empty($TradingCredentials['real'][0]) ? '**************' : ''); ?>">
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('GDAX Api pass (real account)'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your GDAX Api pass (real account)'); ?>" name="kr-adm-gdaxapipassreal" value="<?php echo(array_key_exists('real', $TradingCredentials)
                                                                                                                                                    && array_key_exists('0', $TradingCredentials['real'])
                                                                                                                                                    && !empty($TradingCredentials['real'][1]) ? '**************' : ''); ?>">
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('GDAX Api secret (real account)'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your GDAX Api secret (real account)'); ?>" name="kr-adm-gdaxapisecretreal" value="<?php echo(array_key_exists('real', $TradingCredentials)
                                                                                                                                                    && array_key_exists('0', $TradingCredentials['real'])
                                                                                                                                                    && !empty($TradingCredentials['real'][2]) ? '**************' : ''); ?>">
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('GDAX Api (real account) sandbox'); ?></label>
      </div>
      <div>
        <div class="ckbx-style-14">
            <input type="checkbox" id="kr-adm-chk-gdaxapirealsandbox" <?php echo(array_key_exists('real', $TradingCredentials)
                                                                                                  && array_key_exists('0', $TradingCredentials['real'])
                                                                                                  && $TradingCredentials['real'][3] == 0 ? 'checked' : ''); ?> name="kr-adm-chk-gdaxapirealsandbox">
            <label for="kr-adm-chk-gdaxapirealsandbox"></label>
        </div>
      </div>
    </div>
  </div>
  <div class="kr-admin-line kr-admin-line-cls">
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('GDAX Api key (practice account)'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your GDAX Api key (practice account)'); ?>" name="kr-adm-gdaxapikeypractice" value="<?php echo(array_key_exists('practice', $TradingCredentials)
                                                                                                                                                    && array_key_exists('0', $TradingCredentials['practice'])
                                                                                                                                                    && !empty($TradingCredentials['practice'][0]) ? '**************' : ''); ?>">
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('GDAX Api pass (practice account)'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your GDAX Api pass (practice account)'); ?>" name="kr-adm-gdaxapipasspractice" value="<?php echo(array_key_exists('practice', $TradingCredentials)
                                                                                                                                                    && array_key_exists('0', $TradingCredentials['practice'])
                                                                                                                                                    && !empty($TradingCredentials['practice'][1]) ? '**************' : ''); ?>">
      </div>
    </div>
    <div class="kr-admin-field">
      <div>
        <label><?php echo $Lang->tr('GDAX Api secret (practice account)'); ?></label>
      </div>
      <div>
        <input type="text" placeholder="<?php echo $Lang->tr('Your GDAX Api secret (practice account)'); ?>" name="kr-adm-gdaxapisecretpractice" value="<?php echo(array_key_exists('practice', $TradingCredentials)
                                                                                                                                                    && array_key_exists('0', $TradingCredentials['practice'])
                                                                                                                                                    && !empty($TradingCredentials['practice'][2]) ? '**************' : ''); ?>">
      </div>
    </div>
  </div>

    <h3><?php echo $Lang->tr('Referal system configuration'); ?></h3>
    <div class="kr-admin-line kr-admin-line-cls">
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Enable referal (native trading need to be enabled)'); ?></label>
        </div>
        <div>
          <div class="ckbx-style-14">
              <input type="checkbox" id="kr-adm-chk-enablereferal" <?php echo ($App->_referalEnabled() ? 'checked' : ''); ?> name="kr-adm-chk-enablereferal">
              <label for="kr-adm-chk-enablereferal"></label>
          </div>
        </div>
      </div>
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Referal comission (in $, fixed amount)'); ?></label>
        </div>
        <div>
          <input type="text" placeholder="<?php echo $Lang->tr('Referal comission (in $, fixed amount) ex : When a referal signup & deposit real cash, the refer win 5 $ (value = 5)'); ?>" name="kr-adm-referalcomission" value="<?php echo $App->_getReferalWinAmount(); ?>">
        </div>
      </div>
    </div>

    <h3><?php echo $Lang->tr('Deposit configuration'); ?></h3>

    <div class="kr-admin-line kr-admin-line-cls">
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Deposit fees (in %)'); ?></label>
        </div>
        <div>
          <input type="text" placeholder="<?php echo $Lang->tr('Deposit fees (in %)'); ?>" name="kr-adm-depositfees" value="<?php echo $App->_getFeesDeposit(); ?>">
        </div>
      </div>
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Deposit minimum (in $)'); ?></label>
        </div>
        <div>
          <input type="text" placeholder="<?php echo $Lang->tr('Deposit minimum (in $)'); ?>" name="kr-adm-depositminimum" value="<?php echo $App->_getMinimalDeposit(); ?>">
        </div>
      </div>
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Deposit maximum (in $)'); ?></label>
        </div>
        <div>
          <input type="text" placeholder="<?php echo $Lang->tr('Deposit maximum (in $)'); ?>" name="kr-adm-depositmaximum" value="<?php echo $App->_getMaximalDeposit(); ?>">
        </div>
      </div>
  </div>
  <h3><?php echo $Lang->tr('Withdraw configuration'); ?></h3>
  <div class="kr-admin-line kr-admin-line-cls">
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Withdraw minimum (in $)'); ?></label>
        </div>
        <div>
          <input type="text" placeholder="<?php echo $Lang->tr('Withdraw minimum (in $)'); ?>" name="kr-adm-widthdrawmin" value="<?php echo $App->_getMinimumWidthdraw(); ?>">
        </div>
      </div>
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Withdraw processing time (in days)'); ?></label>
        </div>
        <div>
          <input type="text" placeholder="<?php echo $Lang->tr('Withdraw processing time (in days) = ex : 3'); ?>" name="kr-adm-widthdrawdays" value="<?php echo $App->_getNumberDaysWidthdrawProcess(); ?>">
        </div>
      </div>
    </div>
    <h3><?php echo $Lang->tr('Trading configuration'); ?></h3>
    <div class="kr-admin-line kr-admin-line-cls">
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Trading fees (in %)'); ?></label>
        </div>
        <div>
          <input type="text" placeholder="<?php echo $Lang->tr('Trading fees (in %)'); ?>" name="kr-adm-tradingfees" value="<?php echo $App->_hiddenThirdpartyTradingFee(); ?>">
        </div>
      </div>
    </div>

    <h3><?php echo $Lang->tr('Real account configuration'); ?></h3>
    <div class="kr-admin-line kr-admin-line-cls">
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Enable real account'); ?></label>
        </div>
        <div>
          <div class="ckbx-style-14">
              <input type="checkbox" id="kr-adm-chk-enablerealaccount" <?php echo ($App->_getTradingEnableRealAccount() ? 'checked' : ''); ?> name="kr-adm-chk-enablerealaccount">
              <label for="kr-adm-chk-enablerealaccount"></label>
          </div>
        </div>
      </div>
    </div>

    <h3><?php echo $Lang->tr('Practice account configuration'); ?></h3>
    <div class="kr-admin-line kr-admin-line-cls">
      <div class="kr-admin-field">
        <div>
          <label><?php echo $Lang->tr('Maximum free deposit (in $)'); ?></label>
        </div>
        <div>
          <input type="text" placeholder="<?php echo $Lang->tr('Maximum free deposit (in $) ex : 10000'); ?>" name="kr-adm-maximumfreedeposit" value="<?php echo $App->_getMaximalFreeDeposit(); ?>">
        </div>
      </div>
    </div>
    <div class="kr-admin-action">
      <input type="submit" class="btn btn-orange" name="" value="<?php echo $Lang->tr('Save'); ?>">
    </div>
</form>
