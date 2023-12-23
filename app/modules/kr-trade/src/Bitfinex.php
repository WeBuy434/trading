<?php

class Bitfinex extends Exchange {

  private $Api = null;

  public function __construct($User, $App){
    parent::__construct($User, $App, $this);
    parent::_setExchangeName('bitfinex2');
  }

  public function _getApi(){

    if(!is_null($this->Api)) return $this->Api;

    $this->Api = new \ccxt\bitfinex2([
      'apiKey' => App::encrypt_decrypt('decrypt', $this->_isActivated()['key_bitfinex']),
      'secret' => App::encrypt_decrypt('decrypt', $this->_isActivated()['secret_bitfinex'])
    ]);

    return $this->Api;

  }

  public function _getName(){ return 'Bitfinex'; }
  public function _getTable(){ return 'bitfinex_krypto'; }
  public function _getLogo(){ return 'bitfinex.svg'; }
  public function _isActivated(){ return parent::_isActivated(); }


}

?>
