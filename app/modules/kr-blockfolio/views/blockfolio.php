<?php

/**
 * Coin list market analytic view
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
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoGraph.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoHisto.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoCoin.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoApi.php";

// Load app modules
$App = new App(true);
$App->_loadModulesControllers();

// Check if user is logged
$User = new User();
if(!$User->_isLogged()) die("You are not logged");

// Init lang object
$Lang = new Lang($User->_getLang(), $App);

// Init CryptoApi object
$CryptoApi = new CryptoApi($User, null, $App);

$BlockFolio = new Blockfolio($User);

$Holding = new Holding($User);

$listBlockFolio = $BlockFolio->_getBlockfolioItem();
// array_unshift($listBlockFolio, [
//         "id_blockfolio" => 'none',
//         'id_user' => $User->_getUserID(),
//         'symbol_blockfolio' => 'BTC',
//         'USD' => $CryptoApi->_getCurrencySymbol()
//       ]);

?>
<section class="kr-port-content">
  <div class="kr-port-add-btn">
    <section class="kr-list-coins kr-ov-nblr kr-list-coins-add-blocfolio" style="display:none;">
      <section>
        <section class="kr-dash-pan-cry-select" graph="global_add">
          <header>
            <input type="text" name="" graph="" placeholder="<?php echo $Lang->tr('Search by name or symbol'); ?>" value="">
          </header>
          <ul class="kr-dash-pan-cry-select-lst"></ul>
        </section>
      </section>
    </section>
    <div class="port-add-btn-circle">
      <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
      	 viewBox="0 0 51 51" style="enable-background:new 0 0 51 51;" xml:space="preserve">
      <polygon style="fill:#EFCE4A;" points="50.956,14.456 25.5,29 0.044,14.456 25.5,0 "/>
      <polygon style="fill:#ED8A19;" points="25.5,29 9.7,19.973 0.044,25.456 25.5,40 50.956,25.456 41.3,19.973 "/>
      <g>
      	<polygon style="fill:#EA6248;" points="25.5,40 9.7,30.973 0.044,36.456 25.5,51 50.956,36.456 41.3,30.973 	"/>
      </g>
      </svg>
    </div>
  </div>



  <section class="kr-port">
    <?php
    foreach ($listBlockFolio as $blockfolioItem) {
      $Coin = $CryptoApi->_getCoin($blockfolioItem['symbol_blockfolio']);
      $ShortGraph = $Coin->_getHistoShortGraph($Coin->_getHistoMin(1440));
    ?>
      <section iid="<?php echo App::encrypt_decrypt('encrypt', $blockfolioItem['id_blockfolio']); ?>" class="<?php echo ($Coin->_getCoin24Evolv() < 0 ? 'kr-port-negativ' : 'kr-port-positiv'); if($blockfolioItem['id_blockfolio'] == "none") echo ' port-item-blured-add-act'; ?>" symbol="<?php echo $Coin->_getSymbol(); ?>" currency="<?php echo $CryptoApi->_getCurrency(); ?>">
        <?php if($blockfolioItem['id_blockfolio'] == "none"): ?>
          <section class="port-item-overlay">
            <span class="kr-mono"><?php echo $Lang->tr('Add a new item'); ?></span>
            <div class="port-add-btn-circle">
              <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
              	 viewBox="0 0 51 51" style="enable-background:new 0 0 51 51;" xml:space="preserve">
              <polygon style="fill:#EFCE4A;" points="50.956,14.456 25.5,29 0.044,14.456 25.5,0 "/>
              <polygon style="fill:#ED8A19;" points="25.5,29 9.7,19.973 0.044,25.456 25.5,40 50.956,25.456 41.3,19.973 "/>
              <g>
              	<polygon style="fill:#EA6248;" points="25.5,40 9.7,30.973 0.044,36.456 25.5,51 50.956,36.456 41.3,30.973 	"/>
              </g>
              </svg>
            </div>
          </section>
        <?php endif; ?>
        <header class="kr-mono">
          <div>
            <span><?php echo $Coin->_getSymbol(); ?></span>
            <span class="kr-port-item-price" kr-port-d="PRICE"><?php echo number_format($Coin->_getPrice(), ($Coin->_getPrice() > 10 ? 2 : 6), ',', ' ').' '.$CryptoApi->_getCurrency(); ?></span>
          </div>
          <div class="kr-blockfolio-iact">
            <span kr-port-d="CHANGE24HOURPCT" class="<?php echo ($Coin->_getCoin24Evolv() < 0 ? 'kr-blockfolio-iact-negativ' : 'kr-blockfolio-iact-positiv'); ?>"><?php echo round($Coin->_getCoin24Evolv(), 2); ?>%</span>
            <?php if($blockfolioItem['id_blockfolio'] != "none"): ?>
              <div class="kr-blockfolio-remv" iid="<?php echo App::encrypt_decrypt('encrypt', $blockfolioItem['id_blockfolio']); ?>">
                <svg class="lnr lnr-cross"><use xlink:href="#lnr-cross"></use></svg>
              </div>
            <?php endif; ?>
          </div>
        </header>
        <div class="kr-port-graph" yv="<?php echo $Coin->_getChartValue('y', $ShortGraph); ?>" xv="<?php echo $Coin->_getChartValue('x', $ShortGraph); ?>">
          <canvas height="100px"></canvas>
        </div>
        <ul class="kr-mono">
          <li>
            <span><?php echo $Lang->tr('Direct Vol. 24H'); ?></span>
            <span><?php echo $CryptoApi->_getCurrencySymbol().' '.$Coin->_formatNumberCommarization($Coin->_getDirectVol24()); ?></span>
          </li>
          <li>
            <span><?php echo $Lang->tr('Total Vol. 24H'); ?></span>
            <span><?php echo $CryptoApi->_getCurrencySymbol().' '.$Coin->_formatNumberCommarization($Coin->_getTotalVol24()); ?></span>
          </li>
          <li>
            <span><?php echo $Lang->tr('Market Cap'); ?></span>
            <span><?php echo $CryptoApi->_getCurrencySymbol().' '.$Coin->_formatNumberCommarization($Coin->_getMarketCap()); ?></span>
          </li>
        </ul>
        <?php
        $holdingSize = $Holding->_getHoldingSize($Coin->_getSymbol());
        ?>
        <section class="kr-port-holding" kr-holding-cur="<?php echo $CryptoApi->_getCurrencySymbol(); ?>" kr-holding-size="<?php echo $holdingSize; ?>" kr-holding-buy-value="<?php echo $Holding->_getHoldingBuyValue($Coin->_getSymbol()); ?>">
          <header>
            <div>
              <span>My Holding</span>
              <ul>
                <li class="kr-port-holding-add" kr-symbol="<?php echo $Coin->_getSymbol(); ?>"><svg class="lnr lnr-plus-circle"><use xlink:href="#lnr-plus-circle"></use></svg></li>
              </ul>
            </div>
            <ul>
              <li>
                <label>Holding</label>
                <span class="kr-mono"><?php echo $holdingSize; ?> <?php echo $Coin->_getSymbol(); ?></span>
              </li>
              <li>
                <label>Market Value</label>
                <span class="kr-mono" kr-holding-market-value="1"><?php echo $App->_formatNumber($Coin->_getPrice() * $holdingSize, 2).' '.$CryptoApi->_getCurrencySymbol(); ?></span>
              </li>
              <li>
                <label>Profit / Loss</label>
                <?php
                $profitTotal = $Holding->_getProfit($Coin->_getSymbol(), $Coin->_getPrice() * $holdingSize);
                ?>
                <span kr-holding-profit-loss="1" class="kr-mono <?php echo ($profitTotal < 0 ? 'kr-block-profit-nav' : ($profitTotal == 0 ? 'kr-block-profit-neutral' : 'kr-block-profit-pos')); ?>"><?php echo $App->_formatNumber($profitTotal, 2).' '.$CryptoApi->_getCurrencySymbol(); ?></span>
              </li>
            </ul>
          </header>
          <ul>
            <?php foreach ($Holding->_getListHolding($Coin->_getSymbol()) as $HoldingItem) { ?>
            <li class="kr-port-holding-transaction-<?php echo strtolower($HoldingItem->_getType()); ?>">
              <div>
                <span><?php echo strtoupper($HoldingItem->_getType()); ?> : <?php echo $App->_formatNumber($HoldingItem->_getQuantity(), 4); ?></span>
              </div>
              <div>
                <span><?php echo $HoldingItem->_getDate()->format('d/m/Y'); ?></span>
              </div>
              <div>
                <span><?php echo $App->_formatNumber($HoldingItem->_getQuantity() * $HoldingItem->_getPriceUnit(), 2, ',', ' '); ?> $</span>
              </div>
            </li>
          <?php } ?>
          </ul>
        </section>
      </section>
    <?php } ?>
  </section>
</section>
