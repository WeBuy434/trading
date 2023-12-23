<?php

class Exchange extends MySQL {

  private $User = null;
  private $App = null;
  private $Exchange = null;
  private $AuthValue = null;

  private $ExchangeName = null;

  private $Credentials = null;


  public function __construct($User, $App, $Exchange, $Credentials = null){
    $this->User = $User;
    $this->App = $App;
    $this->Exchange = $Exchange;
    $this->Credentials = $Credentials;
  }

  public function _getUser(){ return $this->User; }
  public function _getApp(){ return $this->App; }
  public function _getExchange(){ return $this->Exchange; }


  public function _setExchangeName($name){ return $this->ExchangeName = $name; }
  public function _getExchangeName(){ return $this->ExchangeName; }

  public function _isActivated(){

    //if(!$this->_getApp()->_hiddenThirdpartyActive()) return false;

    if(!is_null($this->Credentials)) return $this->Credentials;

    if(!is_null($this->AuthValue)) return $this->AuthValue;

    $r = parent::querySqlRequest("SELECT * FROM ".$this->_getExchange()->_getTable()." WHERE id_user=:id_user",
                                  [
                                    'id_user' => $this->_getUser()->_getUserID()
                                  ]);

    if(count($r) == 0) return false;
    $this->AuthValue = $r[0];

    try {
      $this->_getBalance();
    } catch (Exception $e) {
      return false;
    }


    return $this->AuthValue;

  }

  public function _createOrder($symbol, $type, $side, $price = null, $params = [], $Balance = null){

    if($this->_isActivated() == false) throw new Exception($this->_getExchange()->_getName().' is not enable on your account', 1);

    if($side == 'buy'){
      $priceUnit = 1 / $this->_getPriceTrade($symbol);
      $priceTrading = round($priceUnit * $price, 6);
    } else {
      $priceTrading = $price;
    }

    $order = $this->_getExchange()->_getApi()->create_order($symbol, $type, $side, $priceTrading, $params);

    $this->_saveOrder($symbol, $type, $side, $price, $params, $Balance, $order);

  }

  public function _createOrderLimit($symbol, $amount_limit, $price_limit, $side){
    if($this->_isActivated() == false) throw new Exception($this->_getExchange()->_getName().' is not enable on your account', 1);
    $order = $this->_getExchange()->_getApi()->create_order($symbol, 'limit', $side, floatval($amount_limit), floatval($price_limit));
  }

  public function _saveOrder($symbol, $type, $side, $price = null, $params = [], $Balance = null, $order){
    $Trade = new Trade($this->_getUser(), $this->_getApp());
    $symbolInfos = explode('/', $symbol);
    if($this->_getApp()->_hiddenThirdpartyActive()){

      $PriceInfos = $this->_getPriceTrade($symbol, 1);
      $symbol = explode('/', $symbol);

      if(strtoupper($side) == "BUY"){
        $amount = (1 / $PriceInfos) * $price;
        $usd_amount = $price;
      } else {
        $usd_amount = $PriceInfos * $price;
        $amount = $price;
      }

      $Balance->_saveOrder($this, $amount, $usd_amount, strtoupper($side), $symbol[0], $order);
      //$Trade->_saveOrder($side, $price, $symbolInfos[0], $symbolInfos[1], $this->_getExchangeName());
    } else {
      $Trade->_saveOrder($side, $price, $symbolInfos[0], $symbolInfos[1], $this->_getExchangeName());
    }
  }

  public function _getPriceTrade($symbol, $amount = 1){
    $price = $this->_getExchange()->_getApi()->fetch_ticker($symbol)['info'];
    if(array_key_exists('price', $price)) return $price['price'];
    if(array_key_exists('ask', $price) && $price['ask'] != null) return $price['ask'];
    if(array_key_exists('bid', $price) && $price['bid'] != null) return $price['bid'];
    $price = $this->_getExchange()->_getApi()->parse_ticker($this->_getExchange()->_getApi()->fetch_ticker($symbol)['info']);
    return $price['bid'];
    return [
      'symbol' => $symbol,
      'amount' => $amount,
      'price_unit' => $price,
      'price_total' => $price * $amount
    ];
  }

  public static function _formatPair($from, $to){
    return $from.'/'.$to;
  }

  public function _balanceSort($a, $b){
    //error_log(json_encode($a));
    if($a['free'] > $b['free']) return -1;
    if($a['free'] < $b['free']) return 1;
    return 0;
  }

  public function _getFormatedBalance(){
    if($this->_isActivated() == false) throw new Exception($this->_getExchange()->_getName().' is not enable on your account', 1);
    return $this->_getApi()->fetch_balance();
  }

  public function _getBalance($fetchall = false){
    $balanceList = $this->_getFormatedBalance();
    //error_log(json_encode($balanceList));
    $balanceListRes = [];
    foreach ($balanceList as $key => $value) {
      //error_log(json_encode($value));
      if($value['free'] > 0 || $value['used'] > 0 || $fetchall){
        $balanceListRes[$key] = $value;
      }
    }

    if(count($balanceListRes) == 0){
      $listAvailable = ['USD', 'BTC', 'EUR', 'LTC', 'ETH'];
      foreach ($listAvailable as $cur) {
        if(array_key_exists($cur, $balanceList)){
          $balanceListRes[$cur] = $balanceList[$cur];
        }
      }
    }
    uasort($balanceListRes, array( $this, '_balanceSort' ));
    return $balanceListRes;
  }

  public function _getOrderBook($symbol = null){

    return $this->_getApi()->fetch_my_trades($symbol);

  }

  public function _getOrderSymbol(){
    $r = parent::querySqlRequest("SELECT * FROM order_krypto WHERE id_user=:id_user AND thirdparty_order=:thirdparty_order GROUP BY symbol_order, currency_order",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'thirdparty_order' => $this->_getExchangeName()
                                ]);
    $res = [];
    foreach ($r as $key => $value) {
      if(!in_array($this->_formatPair($value['symbol_order'], $value['symbol_order']), $res)){
        $res[] = $this->_formatPair($value['symbol_order'], $value['currency_order']);
      }
    }
    return $res;
  }

  public function _sortOrderBook($a, $b){
    if($a['time'] == $b['time']) return 0;
    return ($a['time'] > $b['time'] ? -1 : 1);
  }

  public function _formatTradingDate($time){
      $time = time() - $time; // to get the time since that moment
      $time = ($time<1)? 1 : $time;
      $tokens = array (
          31536000 => 'year',
          2592000 => 'month',
          604800 => 'week',
          86400 => 'day',
          3600 => 'hour',
          60 => 'minute',
          1 => 'second'
      );

      foreach ($tokens as $unit => $text) {
          if ($time < $unit) continue;
          $numberOfUnits = floor($time / $unit);
          return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
      }

  }

  public function _filterOrderPublicBook($v){
    if($v[1] > 1) return true;
    return false;
  }

  public function _getOrderPublicBook($from, $to){
    $book = $this->_getApi()->fetch_order_book($this->_formatPair($from, $to), 100);

    if(count($book['asks']) > 150) $book['asks'] = array_filter($book['asks'], array($this, '_filterOrderPublicBook'));
    if(count($book['asks']) > 150) $book['bids'] = array_filter($book['bids'], array($this, '_filterOrderPublicBook'));

    $book['asks'] = array_reverse($book['asks']);
    $sum = 0;
    $askMax = 0;
    $bidMax = 0;
    foreach ($book['asks'] as $key => $v) {
      if($v[1] > $askMax) $askMax = $v[1];
    }
    foreach ($book['bids'] as $key => $v) {
      if($v[1] > $bidMax) $bidMax = $v[1];
    }
    foreach ($book['asks'] as $key => $v) {
      $sum += $v[1];
      $book['asks'][$key]['sum'] = number_format($sum, 3, '.', '');
      $book['asks'][$key]['percentage'] = 100 - abs(($v[1] - $askMax) / $askMax * 100);
    }
    $sum = 0;
    foreach ($book['bids'] as $key => $v) {
      $sum += $v[1];
      $book['bids'][$key]['sum'] = number_format($sum, 3, '.', '');
      $book['bids'][$key]['percentage'] = 100 - abs(($v[1] - $bidMax) / $bidMax * 100);
    }


    return $book;
  }

  public function _getDepthGraphValue($orderBook){

    $chartValue = [ 'price' => [], 'value' => ['ask' => [], 'bid' => []] ];
    foreach (array_reverse($orderBook['asks']) as $key => $v) {
      $chartValue['price'][] = $v[0];
      $chartValue['value']['ask'][] = $v['sum'];
    }

    foreach ($orderBook['bids'] as $key => $v) {
      $chartValue['price'][] = $v[0];
      $chartValue['value']['bid'][] = $v['sum'];
    }
    for ($i=0; $i < (count($chartValue['value']['ask']) - count($chartValue['value']['bid'])); $i++) {
      array_unshift($chartValue['value']['bid'], 0);
    }
    return $chartValue;

  }



}

?>
