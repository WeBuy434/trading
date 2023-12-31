<?php

class Ethfinex extends Exchange {

  private $Api = null;

  public function __construct($User, $App){
    parent::__construct($User, $App, $this);
    parent::_setExchangeName('ethfinex');
  }

  public function _getApi(){

    if(!is_null($this->Api)) return $this->Api;

    error_log(json_encode($this->_isActivated()));

    $this->Api = new \ccxt\ethfinex([
      'apiKey' => App::encrypt_decrypt('decrypt', $this->_isActivated()['key_ethfinex']),
      'secret' => App::encrypt_decrypt('decrypt', $this->_isActivated()['secret_ethfinex'])
    ]);

    return $this->Api;

  }

  public function _getName(){ return 'Ethfinex'; }
  public function _getTable(){ return 'ethfinex_krypto'; }
  public function _getLogo(){ return 'ethfinex.svg'; }
  public function _isActivated(){ return parent::_isActivated(); }

  public static function _formatPair($from, $to){
    return $from.'/'.$to;
  }


}

?>
