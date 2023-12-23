<?php

/**
 * Edit indicator action
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

session_start();

require "../../../../../config/config.settings.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/MySQL/MySQL.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/App.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/AppModule.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/User/User.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/Lang/Lang.php";
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
      throw new Exception("User is not logged", 1);
  }

  if(empty($_POST) || empty($_POST['symb']) || empty($_POST['currency'])) throw new Exception("Error : Args missing", 1);

  $CryptoApi = new CryptoApi($User, [$_POST['currency'], '$'], $App);
  $Coin = new CryptoCoin($CryptoApi, $_POST['symb'], null, $App);

  $CoinPrice = $Coin->_getPrice();

  if($_POST['click'] > 10) $_POST['click'] = round($_POST['click'], 2);
  else $_POST['click'] = round($_POST['click'], 5);

  $CryptoNotification = new CryptoNotification($Coin->_getSymbol(), $User);
  $listNotification = $CryptoNotification->_getListCryptoNotifications();

} catch (Exception $e) {
  die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>
<section class="createalert-popup kr-ov-nblr">
  <section style="<?php echo (count($listNotification) <= 0 ? 'width:450px;' : ''); ?>">
    <header>
      <span>Create a new alert</span>
      <div>
        <svg class="lnr lnr-cross"><use xlink:href="#lnr-cross"></use></svg>
      </div>
    </header>
    <section>
      <form class="createalert-popup-frm" method="post">
        <section class="createalert-infos-pair">
          <ul>
            <li><span><?php echo $Coin->_getSymbol(); ?></span></li>
            <li><span><?php echo $_POST['currency']; ?></span></li>
          </ul>
          <div>
            <label>Current price</label>
            <span class="kr-mono"><?php echo $App->_formatNumber($CoinPrice, ($CoinPrice > 10 ? 2 : 5)).' '.$_POST['currency']; ?></span>
          </div>
        </section>
        <section class="createalert-valert">
          <span>Create an alert when ...</span>
          <div>
            <div>
              <span>Price is above</span>
              <div>
                <input type="text" name="price_above_alert" value="<?php if(isset($_POST['click']) && $CoinPrice <= $_POST['click']) echo $_POST['click']; ?>">
                <div>
                  <span><?php echo $_POST['currency']; ?></span>
                </div>
              </div>
            </div>
            <div>
              <span>Price is bellow</span>
              <div>
                <input type="text" name="price_bellow_alert" value="<?php if(isset($_POST['click']) && $CoinPrice > $_POST['click']) echo $_POST['click']; ?>">
                <div>
                  <span><?php echo $_POST['currency']; ?></span>
                </div>
              </div>
            </div>
          </div>
        </section>
        <footer>
          <input type="button" class="btn btn-small" name="" value="Cancel">
          <input type="hidden"  name="symbol_alert" value="<?php echo $Coin->_getSymbol(); ?>">
          <input type="submit" class="btn btn-small btn-orange" name="" value="Add">
        </footer>
      </form>
      <?php if(count($listNotification) > 0): ?>
        <section>
          <ul class="kr-list-notification-coin">
            <?php foreach ($listNotification as $Notification) { ?>
            <li kr-notification-id="<?php echo App::encrypt_decrypt('encrypt', $Notification['id']); ?>">
              <div>
                <svg class="lnr lnr-arrow-<?php echo ($Notification['type'] == 0 ? 'up' : 'down'); ?>"><use xlink:href="#lnr-arrow-<?php echo ($Notification['type'] == 0 ? 'up' : 'down'); ?>"></use></svg>
                <span class="kr-mono"><?php echo $App->_formatNumber($Notification['value'], ($Notification['value'] > 10 ? 2 : 5)); ?> $</span>
              </div>
              <svg class="lnr lnr-trash"><use xlink:href="#lnr-trash"></use></svg>
            </li>
            <?php } ?>
          </ul>
        </section>
      <?php endif; ?>
    </section>
  </section>
</section>
