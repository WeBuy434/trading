<?php

class Kucoin extends Exchange {

  private $Api = null;

  public function __construct($User, $App){
    parent::__construct($User, $App, $this);
    parent::_setExchangeName('kucoin');
  }

  public function _getName(){ return 'Kucoin'; }
  public function _getTable(){ return 'kucoin_krypto'; }
  public function _getLogo(){ return 'kucoin.png'; }
  public function _isActivated(){ return parent::_isActivated(); }

  public function _getApi(){

    if(!is_null($this->Api)) return $this->Api;

    $this->Api = new \ccxt\kucoin([
      'apiKey' => App::encrypt_decrypt('decrypt', $this->_isActivated()['key_kucoin']),
      'secret' => App::encrypt_decrypt('decrypt', $this->_isActivated()['secret_kucoin'])
    ]);

    return $this->Api;

  }

  public function _getFormatedBalance(){
    if($this->_isActivated() == false) throw new Exception($this->_getExchange()->_getName().' is not enable on your account', 1);
    $balance = $this->_getApi()->fetch_balance();
    $res = [];
    foreach ($balance as $key => $value) {
      if($key == 'info' || $key == 'used' || $key == 'free' || $key == 'total') continue;
      $res[$key] = [
        'free' => $value['free'],
        'used' => $value['used']
      ];
    }
    return $res;
  }

  public function _getBalance($fetchall = false){
    $balanceList = $this->_getFormatedBalance();
    uasort($balanceList, array( $this, '_balanceSort' ));
    return $balanceList;
  }

  public function _getOrderBook($symbol = null){
    $orderList = [];
    if(is_null($symbol)){
      foreach ($this->_getOrderSymbol() as $symbolOrdered) {
        foreach ($this->_getApi()->fetch_my_trades($symbolOrdered) as $orderInfos) {
          $symbolInfos = explode('-', $orderInfos['symbol']);

          $orderList[] = [
            'id' => $orderInfos['id'],
            'market' => $orderInfos['symbol'],
            'market_price_buyed' => $orderInfos['price'],
            'symbol' => $symbolInfos[0],
            'date' => $this->_formatTradingDate($orderInfos['timestamp']),
            'time' => $orderInfos['timestamp'],
            'type' => strtolower($orderInfos['type']),
            'size' => $orderInfos['amount'],
            'total' => $orderInfos['cost'],
            'total_currency' => $symbolInfos[1],
            'fees' => $orderInfos['fee']['cost']
          ];
        }
      }
    } else {
      foreach ($this->_getApi()->fetch_my_trades($symbol) as $orderInfos) {
        $symbolInfos = explode('-', $orderInfos['symbol']);

        $orderList[] = [
          'id' => $orderInfos['id'],
          'market' => $orderInfos['symbol'],
          'market_price_buyed' => $orderInfos['price'],
          'symbol' => $symbolInfos[0],
          'date' => $this->_formatTradingDate($orderInfos['timestamp']),
          'time' => $orderInfos['timestamp'],
          'type' => strtolower($orderInfos['type']),
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

}

?>
