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

try {

  if(empty($_POST) || !isset($_POST['symbol']) || empty($_POST['symbol'])) throw new Exception("Error : Args missing", 1);

  if(isset($_POST['currency']) && !empty($_POST['currency'])){
    $CryptoApi->_setCurrency([$_POST['currency'], $_POST['currency']]);
  }

  $Coin = $CryptoApi->_getCoin($_POST['symbol']);

  $GraphContainer = uniqid().rand().uniqid();

  $availableMarketGiven = true;

  $Trade = new Trade($User, $App);

  $availableTrading = null;
  if($App->_hiddenThirdpartyActive()){

    $CryptoApi = new CryptoApi($User, ['USD', '$']);
    $CryptoApi->_setCurrency(['USD', '$']);
    $Coin = new CryptoCoin($CryptoApi, $_POST['symbol']);

    //error_log($CryptoApi->_getCurrencySymbol());


    $Balance = new Balance($User, $App);
    $CurrentBalance = $Balance->_getCurrentBalance();

    $thirdPartyChoosen = $Trade->_getThirdParty($App->_hiddenThirdpartyServiceCfg()['real'])[$App->_hiddenThirdpartyService()];

    $availableMarketGiven = $Trade->_thirdparySymbolTrading($_POST['symbol'], 'USD', $thirdPartyChoosen->_getExchangeName());

    if(count($availableMarketGiven) > 0) $availableTrading = $availableMarketGiven[0];

  } else {
    $availableMarketGiven = null;
    if(isset($_POST['market'])){
      $availableMarketGiven = $Trade->_thirdparySymbolTrading($_POST['symbol'], $_POST['currency'], $_POST['market']);
      if(count($availableMarketGiven) > 0) $availableTrading = $availableMarketGiven[0];
    } else {
      $availableMarketGiven = $Trade->_thirdparySymbolTrading($_POST['symbol'], $_POST['currency']);
      $availableTrading = $availableMarketGiven[0];
    }

    if($availableMarketGiven != null && !isset($_POST['market'])){
      $listMarketAvailable = $availableMarketGiven;
    } else {
      $listMarketAvailable = $Trade->_thirdparySymbolTrading($_POST['symbol'], (isset($_POST['currency']) ? $_POST['currency'] : $CryptoApi->_getCurrency()));
      if(count($listMarketAvailable) > 0 && is_null($availableTrading)) $availableTrading = $listMarketAvailable[0];
    }
  }

  if(is_null($availableTrading) && $App->_hiddenThirdpartyActive()){
    $CryptoApi = new CryptoApi($User);
    $Coin = new CryptoCoin($CryptoApi, $_POST['symbol']);
  }



} catch (Exception $e) {
  die('<span style="color:#fff;">'.$e->getMessage().'</span>');
}


$OrderBook = null;
try {
  //$DepthGraphValue = $Coin->_getDephGraphValue();
  $OrderBook = $availableTrading->_getOrderPublicBook($Coin->_getSymbol(), $CryptoApi->_getCurrency());
  $DepthGraphValue = $availableTrading->_getDepthGraphValue($OrderBook);
} catch (Exception $e) {
}

?>

<section class="kr-coin-inf">
  <header class="kr-mono">
    <div class="kr-cinf-name">
      <?php if(!is_null($Coin->_getIcon())): ?>
        <div class="kr-cinf-pic">
          <img src="<?php echo $Coin->_getIcon(); ?>" alt="">
        </div>
      <?php endif; ?>
      <div class="kr-cinf-ndt">
        <span><?php echo $Coin->_getCoinName(); ?></span>
        <span><?php echo $Coin->_getSymbol(); ?></span>
      </div>
    </div>
    <div class="kr-cinf-item">
      <label><?php echo $Lang->tr('Price'); ?></label>
      <span><i kr-cinf-v="PRICE"><?php echo $App->_formatNumber($Coin->_getPrice(), ($Coin->_getPrice() > 10 ? 2 : 4)); ?></i> <?php echo $CryptoApi->_getCurrencySymbol(); ?></span>
    </div>
    <div class="kr-cinf-item <?php echo ($Coin->_getCoin24Evolv() > 0 ? 'kr-cinf-item-positiv' : 'kr-cinf-item-negativ'); ?>">
      <label><?php echo $Lang->tr('Chg. 24H'); ?></label>
      <span><i kr-cinf-v="CHANGE24HOURPCT"><?php echo $App->_formatNumber($Coin->_getCoin24Evolv(), 2); ?></i> %</span>
    </div>
    <div class="kr-cinf-item">
      <label><?php echo $Lang->tr('Market Cap'); ?></label>
      <span><?php echo $CryptoApi->_getCurrencySymbol().' '.$Coin->_formatNumberCommarization($Coin->_getMarketCap()); ?></span>
    </div>
    <div class="kr-cinf-item">
      <label><?php echo $Lang->tr('Direct Vol. 24H'); ?></label>
      <span><?php echo $CryptoApi->_getCurrencySymbol().' '.$Coin->_formatNumberCommarization($Coin->_getDirectVol24()); ?></span>
    </div>
    <div class="kr-cinf-item">
      <label><?php echo $Lang->tr('Total Vol. 24H'); ?></label>
      <span><?php echo $CryptoApi->_getCurrencySymbol().' '.$Coin->_formatNumberCommarization($Coin->_getTotalVol24()); ?></span>
    </div>
    <?php if(!is_null($availableTrading) && !$App->_hiddenThirdpartyActive()): ?>
      <div class="kr-cinf-market">
        <img src="<?php echo APP_URL; ?>/assets/img/icons/trade/<?php echo $availableTrading->_getLogo(); ?>" alt="<?php echo $availableTrading->_getName(); ?>">
      </div>
    <?php endif; ?>
  </header>
  <section>
    <div style="<?php echo (is_null($availableTrading) ? 'width:100%;' : (is_null($OrderBook) ? 'width:85%;' : '')); ?>">
      <div class="kr-dash-pan-cry kr-dash-pan-cry-vsbl" id="<?php echo $GraphContainer; ?>" type-graph="candlestick" container="<?php echo $GraphContainer; ?>" currency="<?php echo $CryptoApi->_getCurrency(); ?>" symbol="<?php echo $Coin->_getSymbol(); ?>">

      </div>
      <?php if(!is_null($availableTrading)): ?>
      <section class="kr-cinf-order">
        <section>
          <header>
            <span><?php echo $Lang->tr('My orders'); ?></span>
          </header>
          <div>
            <div><?php echo $Lang->tr('Size'); ?></div>
            <div><?php echo $Lang->tr('Price'); ?> <i>(<?php echo $CryptoApi->_getCurrency(); ?>)</i></div>
            <div><?php echo $Lang->tr('Date'); ?></div>
          </div>
          <?php if($availableTrading->_isActivated()): ?>
            <ul class="kr-cinf-order-filledorder">
            </ul>
          <?php else: ?>
            <section>
              <span><?php echo $Lang->tr('You need to be logged at '.$availableTrading->_getName()); ?></span>
            </section>
          <?php endif; ?>
        </section>
      </section>
    <?php endif; ?>
    </div>
    <?php if(!is_null($availableTrading)): ?>
    <section>
      <?php if(!is_null($OrderBook)): ?>
        <section class="kr-cinf-depthgraph">
          <canvas id="canvas_depth" xv="<?php echo join(',', $DepthGraphValue['price']); ?>" yaskv="<?php echo join(',', $DepthGraphValue['value']['ask']); ?>" ybidv="<?php echo join(',', $DepthGraphValue['value']['bid']); ?>" ></canvas>
        </section>
      <?php endif; ?>
      <?php if(!$App->_hiddenThirdpartyActive()): ?>
        <section class="kr-cinf-changeexchange">
          <div class="kr-cinf-changeexchange-toggle">
            <img src="<?php echo APP_URL.'/assets/img/icons/trade/'.$availableTrading->_getLogo(); ?>" alt="">
            <div>
              <svg class="lnr lnr-chevron-down"><use xlink:href="#lnr-chevron-down"></use></svg>
            </div>
          </div>
          <ul>
            <?php
            foreach ($listMarketAvailable as $ExchangeMarket) {
              if($availableTrading->_getExchangeName() == $ExchangeMarket->_getExchangeName()) continue;
              ?>
            <li onclick="changeView('coin', 'coin', {symbol:'<?php echo $Coin->_getSymbol(); ?>', currency:'<?php echo $CryptoApi->_getCurrency(); ?>', market:'<?php echo $ExchangeMarket->_getExchangeName(); ?>'}, null, true);">
              <img src="<?php echo APP_URL.'/assets/img/icons/trade/'.$ExchangeMarket->_getLogo(); ?>" alt="">
            </li>
            <?php } ?>
          </ul>
        </section>
      <?php endif; ?>
      <?php if(!is_null($availableTrading)): ?>
      <section class="kr-cinf-buysell <?php if($availableTrading->_isActivated()) echo 'kr-cinf-buysell-active'; ?>">
        <?php if($availableTrading->_isActivated()): ?>
          <form action="<?php echo APP_URL; ?>/app/modules/kr-trade/src/actions/placeTrade.php" method="post" class="kr-cinf-buysell-action" from="<?php echo $Coin->_getSymbol(); ?>" to="<?php echo $CryptoApi->_getCurrency(); ?>">
            <div>
              <nav>
                <ul class="kr-cinf-buysell-trade-type" style="<?php echo ($App->_hiddenThirdpartyActive() ? 'display:none;' : ''); ?>">
                  <li class="selected-act-bs-n" kr-trade-force="0" kr-trade-totalfield="kr-cinf-amount-v-b" kr-trade-type="market">Market</li>
                  <li kr-trade-type="limit" kr-trade-force="1" kr-trade-totalfield="kr-cinf-amount-v-bvs" kr-trade-force-currency="<?php echo $CryptoApi->_getCurrency(); ?>">Limit</li>
                </ul>
              </nav>
              <div class="kr-cinf-buysell-type">
                <div class="kr-cinf-buysell-type-selected" kr-trade-symbol="<?php echo $CryptoApi->_getCurrency(); ?>" kr-conv-symbol="<?php echo $Coin->_getSymbol(); ?>" kr-trade-side="buy">Buy</div>
                <div kr-trade-side="sell" kr-trade-symbol="<?php echo $Coin->_getSymbol(); ?>" kr-conv-symbol="<?php echo $CryptoApi->_getCurrency(); ?>">Sell</div>
              </div>
              <div class="kr-cinf-buysell-action-input" kr-trade-inpt-enabled="1" kr-trade-inpt-type="market">
                <span><?php echo $Lang->tr('Amount'); ?></span>
                <div>
                  <input type="text" id="kr-cinf-amount-v-b" kr-trade-amount-field="1" placeholder="0.00" name="amount" value="">
                  <span kr-trade-dynamic-symbol="1"><?php echo $CryptoApi->_getCurrency(); ?></span>
                </div>
              </div>
              <div class="kr-cinf-buysell-action-input" kr-trade-inpt-enabled="0" kr-trade-inpt-type="limit" style="display:none;">
                <span><?php echo $Lang->tr('Amount'); ?></span>
                <div>
                  <input type="text" placeholder="0.00" name="amount_limit" value="">
                  <span><?php echo $Coin->_getSymbol(); ?></span>
                </div>
              </div>
              <div class="kr-cinf-buysell-action-input" kr-trade-inpt-enabled="0" kr-trade-inpt-type="limit" style="display:none;">
                <span><?php echo $Lang->tr('Limit price'); ?></span>
                <div>
                  <input type="text" id="kr-cinf-amount-v-bvs" kr-trade-amount-field="1" kr-trade-amount-number="1" placeholder="0.00" name="price_limit" value="">
                  <span><?php echo $CryptoApi->_getCurrency(); ?></span>
                </div>
              </div>
              <footer>
                <ul>
                  <li class="kr-cinf-trade-total" convsymbol="<?php echo $Coin->_getSymbol(); ?>" kr-cinf-trade-total-field="kr-cinf-amount-v-b">
                    <span><?php echo $Lang->tr('Total'); ?> <i>(<?php echo $Coin->_getSymbol(); ?>)</i></span>
                    <span class="kr-cinf-trade-total-value">0.00000000</span>
                  </li>
                  <?php if($App->_hiddenThirdpartyActive()): ?>
                    <li class="kr-cinf-trade-commission-total">
                      <span><?php echo $Lang->tr('Commission'); ?> <i>(<?php echo $App->_formatNumber($App->_hiddenThirdpartyTradingFee(), 2); ?>%)</i></span>
                      <span class="kr-cinf-trade-commission-value" kr-trade-commission-v="2"><b><?php echo $App->_formatNumber(0, 2).'</b> '.$CryptoApi->_getCurrencySymbol(); ?></span>
                    </li>
                    <li class="kr-cinf-trade-amount-total">
                      <span><?php echo $Lang->tr('Total'); ?> <i>(<?php echo $CryptoApi->_getCurrency(); ?>)</i></span>
                      <span class="kr-cinf-trade-total-value-wc"><b><?php echo $App->_formatNumber(0, 2).'</b> '.$CryptoApi->_getCurrencySymbol(); ?></span>
                    </li>
                  <?php endif; ?>
                </ul>
                <input type="hidden" name="thirdparty" value="<?php echo $availableTrading->_getExchangeName(); ?>">
                <input type="hidden" name="from" value="<?php echo $Coin->_getSymbol(); ?>">
                <input type="hidden" name="unit_price" value="<?php echo $availableTrading->_getPriceTrade($availableTrading->_formatPair($Coin->_getSymbol(), $CryptoApi->_getCurrency())); ?>">
                <input type="hidden" name="to" value="<?php echo $CryptoApi->_getCurrency(); ?>">
                <input type="submit" kr-trade-btn-type-flow="1" disabled name="" alt-buy="<?php echo $Lang->tr('Place buy order'); ?>" alt-sell="<?php echo $Lang->tr('Place sell order'); ?>" value="<?php echo $Lang->tr('Place buy order'); ?>">
              </footer>
              <div class="kr-cinf-trade-err">
                <div></div>
              </div>
            </div>
          </form>
        <?php else:
          ?>
          <section class="kr-cinf-wallet-inactive">
            <span><?php echo $Lang->tr('Enable live trading'); ?></span>
            <a class="btn btn-orange btn-autowidth" onclick="_showThirdpartySetup('<?php echo $availableTrading->_getExchangeName(); ?>');"><?php echo $Lang->tr('Login with '.$availableTrading->_getName()); ?></a>
          </section>

        <?php endif; ?>
      </section>
    <?php endif; ?>
    </section>
  <?php endif; ?>
    <?php if(!is_null($OrderBook)): ?>
    <section>
      <section class="kr-cinf-orderbook">
        <header>
          <span class="kr-mono"><?php echo $Lang->tr('Order book'); ?></span>
        </header>
        <?php
        try {

        ?>
        <ul class="kr-cinf-orderbook-ask">
          <?php
          foreach ($OrderBook['asks'] as $order) {
            ?>
            <li bo-price="<?php //echo ($order['price'] * 100); ?>">
              <div style="width:<?php echo $order['percentage']; ?>%;">

              </div>
              <span class="kr-cinf-orderbook-sumv"><?php echo $order['sum']; ?></span>
              <span class="kr-cinf-orderbook-sv"><?php echo $order['1']; ?></span>
              <span class="kr-cinf-orderbook-pv"><?php echo $order['0']; ?></span>
            </li>
            <?php
          }
          ?>
        </ul>
        <section>
          <h3 kr-coin-v-data="PRICE" class="kr-mono"><i><?php echo $App->_formatNumber($Coin->_getPrice(), ($Coin->_getPrice() > 10 ? 2 : 4)); ?></i> <?php echo $CryptoApi->_getCurrencySymbol(); ?></h3>
        </section>
        <ul class="kr-cinf-orderbook-bid">
          <?php
          foreach ($OrderBook['bids'] as $order) {
            ?>
            <li bo-price="<?php //echo ($order['price'] * 100); ?>">
              <div style="width:<?php echo $order['percentage']; ?>%;"></div>
              <span class="kr-cinf-orderbook-sumv"><?php echo $order['sum']; ?></span>
              <span class="kr-cinf-orderbook-sv"><?php echo $order['1']; ?></span>
              <span class="kr-cinf-orderbook-pv"><?php echo $order['0']; ?></span>
            </li>
            <?php
          }
          ?>
        </ul>
        <?php
        } catch (Exception $e) {
          echo $e->getMessage();
        }
        ?>
      </section>
    </section>
  <?php endif; ?>
  </section>
  <?php
  $lsitTopPair = $Coin->_getTopPair(true);
  if(count($lsitTopPair) > 0):
  ?>
  <footer>
    <ul class="kr-mono">
      <?php foreach ($lsitTopPair as $key => $associateCoin) { ?>
        <li>
          <label><?php echo $associateCoin['fromSymbol']; ?> / <?php echo $associateCoin['toSymbol']; ?></label>
          <span><?php echo $App->_formatNumber($associateCoin['price'], ($associateCoin['price'] > 10 ? 2 : 5)); ?></span>
        </li>
      <?php } ?>
    </ul>
  </footer>
<?php endif; ?>
</section>
