<?php

/**
 * Load chart content
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
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/Lang/Lang.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoOrder.php";
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

  // Init lang object
  $Lang = new Lang($User->_getLang(), $App);

  // Check given args
  if (empty($_GET) || empty($_GET['container']) || empty($_GET['coin'])) {
      die('error');
  }

  // Init crypto api
  $CryptoApi = new CryptoApi($User, null, $App);

  // Init coin associate to the graph
  $Coin = new CryptoCoin($CryptoApi, $_GET['coin'], null, $App);

  // Get container
  $container = $_GET['container'];

  // Load indicator graph
  $Indicators = new CryptoIndicators($container);

  // Init dashboard object
  $Dashboard = new Dashboard($CryptoApi, $User);

  // Init CryptoOrder
  $OrderCoin = new CryptoOrder($Coin);

  $Trade = new Trade($User, $App);

  $availableTrading = false;
  if($App->_hiddenThirdpartyActive()){
    $availableTrading = $Trade->_symbolAvailableTrading($Coin->_getSymbol(), $CryptoApi->_getCurrency(), $App->_hiddenThirdpartyService());
  } else {
    $availableTrading = $Trade->_symbolAvailableTrading($Coin->_getSymbol(), $CryptoApi->_getCurrency());
  }



  if(!$User->_accessAllowedFeature($App, 'tradinglive')) $availableTrading = false;

} catch (\Exception $e) {
  die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

$DashboardGraph = null;
try {
  $DashboardGraph = new DashboardGraph($CryptoApi, $User, null, []);
  $DashboardGraph->_loadGraphByKey($container);
} catch (Exception $s) {
  $DashboardGraph = null;
}

?>
<div class="kr-dash-pan-lb">
  <ul class="kr-dash-pan-pa">
    <li class="kr-dash-close"><svg class="lnr lnr-cross"><use xlink:href="#lnr-cross"></use></svg></li>
    <?php if ($Dashboard->_getNumGraph() > 1): ?>
      <li class="kr-dash-tgglfullscreen"><svg class="lnr lnr-frame-expand"><use xlink:href="#lnr-frame-expand"></use></svg></li>
    <?php endif; ?>
    <?php if($User->_accessAllowedFeature($App, 'exportgraph')): ?>
      <li class="kr-dash-export"><svg class="lnr lnr-download"><use xlink:href="#lnr-download"></use></svg></li>
    <?php endif; ?>
  </ul>
  <ul class="kr-dash-pan-comvote">
    <li class="kr-dash-pan-com-t">
      <ul>
        <?php
        for ($i=10; $i > 0; $i--) {
          echo '<li style="z-index:'.($i).';" class="'.(5 >= $i ? 'kr-dash-pan-com-t-buy' : '').'"></li>';
        }
        ?>
      </ul>
    </li>
  </ul>
  <ul class="kr-dash-pan-cust">
    <li class="kr-dash-pan-ads-sld">
      <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512.001 512.001" style="enable-background:new 0 0 512.001 512.001;" xml:space="preserve"> <g> <g> <path d="M457.637,134.681c-29.976,0-54.363,24.387-54.363,54.363c0,10.933,3.255,21.114,8.831,29.647l-58.733,58.733 c-8.534-5.576-18.715-8.831-29.648-8.831c-11.329,0-21.858,3.488-30.576,9.441l-59.338-59.347 c5.575-8.533,8.828-18.713,8.828-29.644c0-29.976-24.387-54.363-54.363-54.363c-29.976,0-54.363,24.387-54.363,54.363 c0,10.931,3.254,21.108,8.827,29.641L84.004,277.42c-8.532-5.574-18.711-8.827-29.641-8.827C24.387,268.593,0,292.981,0,322.957 s24.387,54.363,54.363,54.363c29.976,0,54.363-24.387,54.363-54.363c0-10.933-3.255-21.114-8.831-29.648l58.733-58.733 c8.534,5.576,18.715,8.831,29.648,8.831c10.932,0,21.113-3.255,29.646-8.831l59.666,59.674 c-5.206,8.338-8.226,18.174-8.226,28.706c0,29.976,24.387,54.363,54.363,54.363s54.363-24.387,54.363-54.363 c0-10.931-3.254-21.109-8.827-29.641l58.736-58.736c8.533,5.574,18.712,8.827,29.641,8.827c29.976,0,54.363-24.387,54.363-54.363 C512.001,159.066,487.613,134.681,457.637,134.681z M54.363,354.849c-17.586,0-31.893-14.307-31.893-31.892 c0-17.586,14.307-31.893,31.893-31.893c17.585,0,31.893,14.307,31.893,31.893C86.256,340.542,71.949,354.849,54.363,354.849z M188.276,220.936c-17.585,0-31.893-14.307-31.893-31.893c0-17.585,14.307-31.893,31.893-31.893 c17.586,0,31.893,14.307,31.893,31.893C220.169,206.629,205.862,220.936,188.276,220.936z M323.724,354.849 c-17.585,0-31.893-14.307-31.893-31.893s14.307-31.893,31.893-31.893c17.585,0,31.893,14.307,31.893,31.893 C355.616,340.542,341.309,354.849,323.724,354.849z M457.637,220.936c-17.585,0-31.893-14.307-31.893-31.893 c0-17.585,14.307-31.893,31.893-31.893c17.585,0,31.893,14.307,31.893,31.893C489.53,206.629,475.222,220.936,457.637,220.936z"/> </g> </g> <g> <g> <path d="M176.774,272.717c-4.388-4.387-11.501-4.387-15.889,0l-22.854,22.854c-4.387,4.387-4.387,11.501,0.001,15.889 c2.194,2.194,5.069,3.291,7.944,3.291s5.751-1.098,7.944-3.291l22.854-22.854C181.163,284.219,181.163,277.104,176.774,272.717z" /> </g> </g> <g> <g> <path d="M373.962,200.939c-4.388-4.387-11.5-4.387-15.89,0.001l-22.854,22.854c-4.387,4.387-4.387,11.501,0,15.889 c2.195,2.193,5.07,3.29,7.945,3.29c2.876,0,5.75-1.098,7.944-3.291l22.854-22.854C378.349,212.44,378.349,205.326,373.962,200.939 z"/> </g> </g> </svg>
      <div class="kr-dash-pan-ads" style="display:none;">
        <header>
          <span><?php echo $Lang->tr('Indicators'); ?></span>
          <div class="kr-dash-pan-type-s">
            <span>Candlestick</span>
            <div class="<?php echo ((is_null($DashboardGraph) || $DashboardGraph->_getTypeGraph() == "candlestick") ? "" : "kr-dash-pan-type-sdone"); ?>">
              <div></div>
            </div>
            <span>Line</span>
          </div>
        </header>
        <section>
          <div class="kr-dash-pan-ads-lst">
            <ul>
              <?php
              foreach (CryptoIndicators::_getIndicatorsList() as $symbolIndicator => $indicator) {
                  echo '<li kr-indicator="'.$symbolIndicator.'" kr-graph="'.$container.'">'.$indicator['name'].'</li>';
              }
              ?>
            </ul>
          </div>
          <div class="kr-dash-pan-ads-i" kr-idic-init="false">
            <ul>
              <?php
              foreach ($Indicators->_getListIndicatorsContainer() as $Indicator) {
                  ?>
                <li kr-cid="<?php echo $Indicator->_getIndicator(); ?>" kr-id-args="<?php echo join(',', $Indicator->_getArgs()); ?>" kr-tid="<?php echo $Indicator->_getSymbol(); ?>">
                  <span><?php echo $Indicator->_getTitle(); ?></span>
                  <ul>
                    <li><svg class="lnr lnr-cog"><use xlink:href="#lnr-cog"></use></svg></li>
                    <li><svg class="lnr lnr-eye"><use xlink:href="#lnr-eye"></use></svg></li>
                    <li><svg class="lnr lnr-trash"><use xlink:href="#lnr-trash"></use></svg></li>
                  </ul>
                </li>
                <?php
              }
              ?>

            </ul>
          </div>
        </section>
      </div>
    </li>
  </ul>
</div>
<div class="kr-dash-pan-tb">
  <div>
    <div class="kr-dash-pan-tb-nopt">
      <div class="kr-dash-pan-tb-nopt-icon">
        <?php echo($Coin->_getIcon() != null ? file_get_contents($Coin->_getIcon()) : ''); ?>
      </div>
      <div class="kr-dash-pan-tb-nopt-n">
        <label><?php echo $Coin->_getCoinName(); ?><svg class="lnr lnr-chevron-down"><use xlink:href="#lnr-chevron-down"></use></svg></label>
        <span><?php echo $Coin->_getSymbol(); ?></span>
        <div class="kr-dash-pan-cry-select" graph="<?php echo $container; ?>">
          <header>
            <input type="text" name="" graph="<?php echo $container; ?>" placeholder="<?php echo $Lang->tr('Search by name or symbol'); ?>" value="">
          </header>
          <ul class="kr-dash-pan-cry-select-lst">
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
if($availableTrading){
  if($App->_hiddenThirdpartyActive()) $listThirdParty = $Trade->_thirdparySymbolTrading($Coin->_getSymbol(), $CryptoApi->_getCurrency(), $App->_hiddenThirdpartyService());
  else $listThirdParty = $Trade->_thirdparySymbolTrading($Coin->_getSymbol(), $CryptoApi->_getCurrency());
}
?>

<div class="kr-dash-pan-graph <?php if($availableTrading) echo 'kr-dash-pan-graph-trading-a'; ?>" market="<?php echo ($availableTrading ? $listThirdParty[0]->_getExchangeName() : 'CCCAGG'); ?>" scrollv="4" id="graph-<?php echo $container; ?>" id="container">

</div>
<?php if($availableTrading):

  $priceMarketUnit = $listThirdParty[0]->_getPriceTrade($listThirdParty[0]::_formatPair($Coin->_getSymbol(), $CryptoApi->_getCurrency()));
  ?>
<div class="kr-dash-pan-action" thirdparty="<?php echo $listThirdParty[0]->_getName(); ?>" container="<?php echo $container; ?>" currency="<?php echo $CryptoApi->_getCurrency(); ?>" symbol="<?php echo $Coin->_getSymbol(); ?>">
  <div class="kr-dash-pan-action-slcthird" <?php if($App->_hiddenThirdpartyActive()) echo 'style="display:none";'; ?>>
    <div kr-trading-price="<?php echo $priceMarketUnit; ?>" kr-chart-trade-tp="<?php echo $listThirdParty[0]->_getExchangeName(); ?>">
      <img src="<?php echo APP_URL; ?>/assets/img/icons/trade/<?php echo $listThirdParty[0]->_getLogo(); ?>" alt="">
      <svg class="lnr lnr-chevron-down"><use xlink:href="#lnr-chevron-down"></use></svg>
    </div>
    <ul>
      <?php
      foreach (array_slice($listThirdParty, 1) as $ThirdPartySelector) {
        ?>
        <li class="kr-dash-pan-chg-exg" kr-trading-price="<?php echo $ThirdPartySelector->_getPriceTrade($ThirdPartySelector::_formatPair($Coin->_getSymbol(), $CryptoApi->_getCurrency())); ?>" kr-chart-trade-tp="<?php echo $ThirdPartySelector->_getExchangeName(); ?>"><img src="<?php echo APP_URL; ?>/assets/img/icons/trade/<?php echo $ThirdPartySelector->_getLogo(); ?>" alt=""></li>
        <?php
      }
      ?>
    </ul>
  </div>
  <div class="kr-dash-pan-action-amount">
    <div class="kr-dash-pan-action-amount-s">
      <span><?php echo $Lang->tr('Amount'); ?></span>
      <div>
        <span><?php echo $CryptoApi->_getCurrencySymbol(); ?></span>
        <input type="number" min="1" placeholder="1" name="" value="1">
      </div>
    </div>
    <ul>
      <li trade-act="minus">-</li>
      <li trade-act="plus">+</li>
    </ul>
  </div>
  <div class="kr-dash-pan-action-qtd" kr-market-multticker="<?php echo $priceMarketUnit; ?>">
    <label><?php echo $Coin->_getSymbol(); ?> <?php echo $Lang->tr('quantity'); ?></label>
    <span><?php echo $App->_formatNumber(1 / $priceMarketUnit, 6); ?></span>
  </div>
  <div class="kr-dash-pan-action-btn kr-dash-pan-action-btn-buy">
    <span><?php echo $Lang->tr('Buy'); ?></span>
  </div>
  <div class="kr-dash-pan-action-btn kr-dash-pan-action-btn-sell">
    <span><?php echo $Lang->tr('Sell'); ?></span>
    <div class="kr-dash-pan-action-confirm">
      <header>
        <span>Confirmation</span>
        <div>
          <svg class="lnr lnr-cross"><use xlink:href="#lnr-cross"></use></svg>
        </div>
      </header>
      <ul>
        <li>
          <span>Unit price</span>
          <span kr-confirm-v="unit_price" kr-confirm-v-up="<?php echo $priceMarketUnit; ?>"><i><?php echo $App->_formatNumber($priceMarketUnit, ($priceMarketUnit > 10 ? 2 : 5)); ?></i> $</span>
        </li>
        <li>
          <span>Investment</span>
          <span kr-confirm-v="amount"><i><?php echo $App->_formatNumber(1, 2); ?></i> $</span>
        </li>
        <li kr-confirm-qntd="spvmx">
          <span><?php echo $Coin->_getSymbol(); ?> Quantity</span>
          <span kr-confirm-v="investment"><i><?php echo $App->_formatNumber(1 / $priceMarketUnit, 6); ?></i></span>
        </li>
        <?php if($App->_hiddenThirdpartyActive()): ?>
          <li>
            <span>Commission</span>
            <span kr-confirm-v="fees" kr-confirm-v-up="<?php echo $App->_hiddenThirdpartyTradingFee(); ?>"><i class="kr-confirm-sminfc"><?php echo $App->_formatNumber($App->_hiddenThirdpartyTradingFee(), 2); ?>% =</i> <i>0.03</i> $</span>
          </li>
        <?php endif; ?>
      </ul>
      <div>
        <span>Total</span>
        <?php if($App->_hiddenThirdpartyActive()): ?>
          <span kr-confirm-v="total"><i><?php echo $App->_formatNumber(1 + (1 * ($App->_hiddenThirdpartyTradingFee() / 100)), 2); ?></i> $</span>
        <?php else: ?>
          <span kr-confirm-v="total"><i><?php echo $App->_formatNumber(1, 2); ?></i> $</span>
        <?php endif; ?>
      </div>
      <a class="btn btn-green btn-kr-action-placetrade">Confirm buying</a>
    </div>
  </div>
</div>
<?php endif; ?>
<footer>
  <ul class="kr-dash-pan-ranges">
    <li rangemin="44641" rangemax="999999999999">5m</li>
    <li rangemin="20161" rangemax="44640">1m</li>
    <li rangemin="10082" rangemax="20160">2w</li>
    <li rangemin="1441" rangemax="10081">7d</li>
    <li rangemin="721" rangemax="1440">1d</li>
    <li rangemin="121" rangemax="720">12h</li>
    <li rangemin="61" rangemax="120">2h</li>
    <li rangemin="31" rangemax="60">1h</li>
    <li rangemin="0" rangemax="30">30min</li>
  </ul>
</footer>
