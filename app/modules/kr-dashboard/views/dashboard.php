<?php

session_start();

require "../../../../config/config.settings.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/vendor/autoload.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/MySQL/MySQL.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/User/User.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/Lang/Lang.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/App.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/AppModule.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoIndicators.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoGraph.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoHisto.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoCoin.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoApi.php";

$App = new App(true);
$App->_loadModulesControllers();

$User = new User();
if(!$User->_isLogged()) die('Error : User not logged');

$Lang = new Lang($User->_getLang(), $App);

$CryptoApi = new CryptoApi(null, null, $App);

$Dashboard = new Dashboard($CryptoApi, $User);

if(!empty($_POST) && !empty($_POST['nchart'])) $Dashboard->_changeDashboardType($_POST['nchart']);

$GraphList = $Dashboard->_getDashboardGraphList();

$nchartShown = $Dashboard->_getGraphPos();


?>
<div class="kr-dash-pannel kr-dash-chart-n" nchart="<?php echo $nchartShown; ?>">
  <?php

  $nschart = 0;

  foreach (array_slice($GraphList, 0, $Dashboard->_getNumGraph()) as $NChart => $Graph) {

    if($Graph->_isEnable()):

      $Coin = $Graph->_getAssociateItem()->_getCoinItem();

    ?>
    <div class="kr-dash-pan-cry kr-dash-pan-cry-vsbl" id="<?php echo $Graph->_getKeyGraph(); ?>" graph-id="<?php echo $Graph->_getGraphID(true); ?>" type-graph="<?php echo $Graph->_getTypeGraph(); ?>" container="<?php echo $Graph->_getKeyGraph(); ?>" currency="<?php echo $CryptoApi->_getCurrency(); ?>" symbol="<?php echo $Coin->_getSymbol(); ?>">

    </div>
    <?php else: ?>
      <div class="kr-dash-pan-cry" id="<?php echo $Graph->_getKeyGraph(); ?>" chart-init="false" graph-id="<?php echo $Graph->_getGraphID(true); ?>" type-graph="<?php echo $Graph->_getTypeGraph(); ?>" container="<?php echo $Graph->_getKeyGraph(); ?>" currency="<?php echo $CryptoApi->_getCurrency(); ?>" symbol="not_init">
        <div class="kr-dash-pan-lgl">
          <div class="kr-dash-pan-cry-select" graph="<?php echo $Graph->_getKeyGraph(); ?>">
            <header>
              <input type="text" name="" graph="<?php echo $Graph->_getKeyGraph(); ?>" placeholder="<?php echo $Lang->tr('Search by name or symbol'); ?>" value="">
            </header>
            <ul class="kr-dash-pan-cry-select-lst">
            </ul>
          </div>
          <img src="<?php echo APP_URL.'/assets/img/logo'.($User->_whiteMode() ? '_black' : '').'.svg'; ?>" alt="">
        </div>
      </div>
    <?php

    endif;

  }
?>
</div>
