<?php
/**
 * WatchingList class
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */
class WatchingList extends MySQL {

  /**
   * User object
   * @var User
   */
  private $user = null;

  /**
   * CryptoApi object
   * @var CryptoApi
   */
  private $CryptoApi = null;

  /**
   * WatchingList constructor
   * @param CryptoApi $CryptoApi CryptoApi object
   * @param User $user           User object
   */
  public function __construct($CryptoApi, $user){
    $this->user = $user;
    $this->CryptoApi = $CryptoApi;
  }

  /**
   * Get user object
   * @return User User associate to the watching list
   */
  public function _getUser(){
    return $this->user;
  }

  /**
   * Get crypto api
   * @return CryptoApi CryptoApi associate to the watching list
   */
  public function _getCryptoApi(){
    return $this->CryptoApi;
  }

  /**
   * Get list coins
   * @return Array CryptoCoin Array
   */
  public function _getListCoins(){
    $resCoin = [];
    // Fetch list coins in database
    foreach (parent::querySqlRequest("SELECT * FROM watching_krypto WHERE id_user=:id_user", ['id_user' => $this->_getUser()->_getUserID()]) as $key => $itemWatching) {
      // Create & append CryptoCoin object in result
      $resCoin[$itemWatching['symbol']] = new CryptoCoin($this->_getCryptoApi(), $itemWatching['symbol']);
    }
    return $resCoin;

  }

  /**
   * Remove watching list item
   * @param  String $symbol Symbol item (ex : BTC)
   * @return Boolean
   */
  public function _removeItem($symbol){

    // Delete item in Database
    $r = parent::execSqlRequest("DELETE FROM watching_krypto WHERE symbol=:symbol AND id_user=:id_user", [
                                'symbol' => $symbol,
                                'id_user' => $this->_getUser()->_getUserID()
                              ]);

    // Check delete status
    if(!$r) throw new Exception("Error : Fail to delete watching list item (SQL Error)", 1);
    return true;
  }

  /**
   * Add watching list item
   * @param String $symbol Symbol item (ex : BTC)
   */
  public function _addItem($symbol){

    // Check if item is alreayd in watching list
    $r = parent::querySqlRequest("SELECT * FROM watching_krypto WHERE symbol=:symbol AND id_user=:id_user", [
                                'symbol' => $symbol,
                                'id_user' => $this->_getUser()->_getUserID()
                              ]);

    // If item not exist in watching list
    if(count($r) == 0){

      // Add item in watching list in database
      $s = parent::execSqlRequest("INSERT INTO watching_krypto (symbol, id_user) VALUES (:symbol, :id_user)",
                                  [
                                    'symbol' => $symbol,
                                    'id_user' => $this->_getUser()->_getUserID()
                                  ]);

      // Check insert sql status
      if(!$s) throw new Exception("Error : Fail to add to watching list (SQL Error)", 1);
     }

  }

}

?>
