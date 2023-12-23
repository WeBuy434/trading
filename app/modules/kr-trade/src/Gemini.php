<?php

class Gemini extends Exchange {

  private $Api = null;

  public function __construct($User, $App){
    parent::__construct($User, $App, $this);
    parent::_setExchangeName('gemini');
  }

  public function _getApi(){

    if(!is_null($this->Api)) return $this->Api;

      $this->Api = new \ccxt\gemini([
        'apiKey' => App::encrypt_decrypt('decrypt', $this->_isActivated()['key_gemini']),
        'secret' => App::encrypt_decrypt('decrypt', $this->_isActivated()['secret_gemini'])
      ]);

      if($this->_isActivated()['live_gemini'] == 0) $this->Api->urls['api'] = 'https://api.sandbox.gemini.com';


    return $this->Api;

  }

  public function _getName(){ return 'Gemini'; }
  public function _getTable(){ return 'gemini_krypto'; }
  public function _getLogo(){ return 'gemini.svg'; }
  public function _isActivated(){ return parent::_isActivated(); }


  public function _createOrder($symbol, $type, $side, $price = null, $params = [], $Balance = null){

    if($this->_isActivated() == false) throw new Exception($this->_getExchange()->_getName().' is not enable on your account', 1);

    $priceUnit = parent::_getPriceTrade($symbol);

    $priceUnit = round(1 / $priceUnit, 6, PHP_ROUND_HALF_DOWN) - 0.000001;
    $order = $this->_getExchange()->_getApi()->create_order($symbol, 'exchange limit', $side, round($priceUnit * $price, 6, PHP_ROUND_HALF_DOWN), $price, $params);

    parent::_saveOrder($symbol, $type, $side, $price, $params, $Balance, $order);
  }

  public function _getOrderBook($symbol = null){

    if($this->_isActivated() == false) throw new Exception($this->_getExchange()->_getName().' is not enable on your account', 1);

    $orderList = [];

    $orderList = [];
    if(is_null($symbol)){
      foreach ($this->_getOrderSymbol() as $symbolOrdered) {
        foreach ($this->_getApi()->fetch_my_trades($symbolOrdered) as $orderInfos) {

          $symbolInfos = explode('/', $orderInfos['symbol']);
          $orderList[] = [
            'id' => $orderInfos['id'],
            'market' => $orderInfos['symbol'],
            'market_price_buyed' => $orderInfos['price'],
            'symbol' => $symbolInfos[0],
            'date' => $this->_formatTradingDate($orderInfos['info']['timestamp']),
            'time' => $orderInfos['info']['timestamp'],
            'type' => strtolower($orderInfos['info']['type']),
            'size' => $orderInfos['amount'],
            'total' => $orderInfos['cost'],
            'total_currency' => $symbolInfos[1],
            'fees' => $orderInfos['fee']['cost']
          ];
        }
      }
    } else {
      foreach ($this->_getApi()->fetch_my_trades($symbol) as $orderInfos) {

        $symbolInfos = explode('/', $orderInfos['symbol']);
        $orderList[] = [
          'id' => $orderInfos['id'],
          'market' => $orderInfos['symbol'],
          'market_price_buyed' => $orderInfos['price'],
          'symbol' => $symbolInfos[0],
          'date' => $this->_formatTradingDate($orderInfos['info']['timestamp']),
          'time' => $orderInfos['info']['timestamp'],
          'type' => strtolower($orderInfos['info']['type']),
          'size' => $orderInfos['amount'],
          'total' => $orderInfos['cost'],
          'total_currency' => $symbolInfos[1],
          'fees' => $orderInfos['fee']['cost']
        ];
      }
    }
    usort($orderList, array($this, '_sortOrderBook'));
    return $orderList;

  }

  public function _getFormatedBalance(){
    if($this->_isActivated() == false) throw new Exception($this->_getExchange()->_getName().' is not enable on your account', 1);

    $balance = $this->_getApi()->fetch_balance();

    $res = [];
    foreach ($balance as $key => $value) {
      if($key == 'info' || $key == 'used' || $key == 'free' || $key == 'total') continue;
      $res[$key] = [
        'free' => $value['free'],
        'used' => $value['locked']
      ];
    }
    return $res;
  }

  public function _getBalance($fetchall = false){
    $balanceList = $this->_getFormatedBalance();
    uasort($balanceList, array( $this, '_balanceSort' ));
    return $balanceList;
  }


}

?>
