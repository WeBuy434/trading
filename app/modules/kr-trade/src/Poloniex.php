<?php

class Poloniex extends Exchange {

  private $Api = null;

  public function __construct($User, $App){
    parent::__construct($User, $App, $this);
    parent::_setExchangeName('poloniex');
  }

  public function _getName(){ return 'Poloniex'; }
  public function _getTable(){ return 'poloniex_krypto'; }
  public function _getLogo(){ return 'poloniex.png'; }
  public function _isActivated(){ return parent::_isActivated(); }

  public function _getApi(){

    if(!is_null($this->Api)) return $this->Api;

    $this->Api = new \ccxt\poloniex([
      'apiKey' => App::encrypt_decrypt('decrypt', $this->_isActivated()['key_poloniex']),
      'secret' => App::encrypt_decrypt('decrypt', $this->_isActivated()['secret_poloniex'])
    ]);

    return $this->Api;

  }

  public function _getBalance($fetchall = false){
    $balanceList = $this->_getFormatedBalance();
    //error_log(json_encode($balanceList));
    $balanceListRes = [];
    foreach ($balanceList as $key => $value) {
      if($key == "info" || $key == "total" || $key == "used" || $key == "free") continue;
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


}

?>
