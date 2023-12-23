<?php

class Trade extends MySQL {

  private $User = null;
  private $App = null;

  private $ThirdParty = null;

  private $listThirdparty = [
    'binance',
    //'bitstamp',
    'gdax',
    'gemini',
    'hitbtc2',
    //'bitfinex2',
    'kraken',
    'kucoin',
    'poloniex',
    'bittrex',
    'cex'
    //'ethfinex'
  ];

  private $selectedThirdparty = 'none';

  public function __construct($User, $App){

    $this->User = $User;
    $this->App = $App;

  }

  public function _getUser(){ return $this->User; }
  public function _getApp(){ return $this->App; }

  public function _getThirdPartyConfig(){
    return [
      'gdax' => [
        'key_gdax' => 'GDAX Key',
        'pass_gdax' => 'GDAX Pass',
        'secret_gdax' => 'GDAX Secret',
        'sandbox' => 'live_gdax'
      ],
      'binance' => [
        'key_binance' => 'Binance Key',
        'secret_binance' => 'Binance Secret',
        'sandbox' => null
      ],
      // 'bitstamp' => [
      //   'key_bitstamp' => 'Bitstamp Key',
      //   'secret_bitstamp' => 'Bitstamp Secret',
      //   'uid_bitstamp' => 'Bitstamp UID',
      //   'sandbox' => null
      // ],
      'gemini' => [
        'key_gemini' => 'Gemini Key',
        'secret_gemini' => 'Gemini Secret',
        'sandbox' => 'live_gemini'
      ],
      'kraken' => [
        'key_kraken' => 'Kraken Key',
        'private_kraken' => 'Kraken Private',
        'sandbox' => null
      ],
      'kucoin' => [
        'key_kucoin' => 'Kucoin Key',
        'secret_kucoin' => 'Kucoin Private',
        'sandbox' => null
      ],
      'bittrex' => [
        'api_key_bittrex' => 'Bittrex Key',
        'api_secret_bittrex' => 'Bittrex Secret',
        'sandbox' => null
      ],
      'cex' => [
        'key_cex' => 'CEX Key',
        'secret_cex' => 'CEX Secret',
        'uid_cex' => 'CEX UID',
        'sandbox' => null
      ],
      'poloniex' => [
        'key_poloniex' => 'Poloniex Key',
        'secret_poloniex' => 'Poloniex Secret',
        'sandbox' => null
      ],
      'hitbtc2' => [
        'key_hitbtc2' => 'Hitbtc Key',
        'secret_hitbtc2' => 'Hitbtc Secret',
        'sandbox' => null
      ]
    ];
  }

  public function _getThirdParty($params = null){

    if(!is_null($this->ThirdParty) && is_null($params)) return $this->ThirdParty;

    
    $this->ThirdParty = [
      'binance' => new Binance($this->_getUser(), $this->_getApp()),
      //'bitstamp' => new Bitstamp($this->_getUser(), $this->_getApp()),
      'gdax' => new Gdax($this->_getUser(), $this->_getApp(), $params),
      'gemini' => new Gemini($this->_getUser(), $this->_getApp()),
      //'bitfinex2' => new Bitfinex($this->_getUser(), $this->_getApp()),
      'hitbtc2' => new Hitbtc($this->_getUser(), $this->_getApp()),
      'kraken' => new Kraken($this->_getUser(), $this->_getApp()),
      'kucoin' => new Kucoin($this->_getUser(), $this->_getApp()),
      'poloniex' => new Poloniex($this->_getUser(), $this->_getApp()),
      'bittrex' => new Bittrex($this->_getUser(), $this->_getApp()),
      'cex' => new Cex($this->_getUser(), $this->_getApp())
      //'ethfinex' => new Ethfinex($this->_getUser(), $this->_getApp())
    ];

    return $this->ThirdParty;

  }

  public function _syncListCrypto(){

    foreach ($this->listThirdparty as $exchangeName) {
      $exchange = '\\ccxt\\' . $exchangeName;
      $exchange = new $exchange ();
      foreach ($exchange->load_markets() as $ksymbol => $pair) {
        $r = parent::execSqlRequest("INSERT INTO thirdparty_crypto_krypto (symbol_thirdparty_crypto, to_thirdparty_crypto, name_thirdparty_crypto)
                                    VALUES (:symbol_thirdparty_crypto, :to_thirdparty_crypto, :name_thirdparty_crypto)",
                                    [
                                      'symbol_thirdparty_crypto' => $pair['base'],
                                      'to_thirdparty_crypto' => $pair['quote'],
                                      'name_thirdparty_crypto' => strtolower($exchangeName)
                                    ]);
      }
    }

  }

  public function _sortThirdpartyListSymbolSelected($a, $b){
    //error_log($a->_getExchangeName().' - '.$this->_getNameSelectedThirdPartyUser());
    if($a->_getExchangeName() == $this->_getNameSelectedThirdPartyUser()) return -1;
    return 1;
  }

  public function _thirdparySymbolTrading($from, $to, $service = null){

    if(is_null($service)){
      $r = parent::querySqlRequest("SELECT * FROM thirdparty_crypto_krypto WHERE symbol_thirdparty_crypto=:symbol_thirdparty_crypto AND to_thirdparty_crypto=:to_thirdparty_crypto",
                                   [
                                     'symbol_thirdparty_crypto' => $from,
                                     'to_thirdparty_crypto' => $to
                                   ]);
    } else {
      $r = parent::querySqlRequest("SELECT * FROM thirdparty_crypto_krypto WHERE symbol_thirdparty_crypto=:symbol_thirdparty_crypto AND to_thirdparty_crypto=:to_thirdparty_crypto AND name_thirdparty_crypto=:name_thirdparty_crypto",
                                   [
                                     'symbol_thirdparty_crypto' => $from,
                                     'to_thirdparty_crypto' => $to,
                                     'name_thirdparty_crypto' => $service
                                   ]);
    }

    $listThirdparty = [];
    $listThirdpartyActivated = [];
    foreach ($r as $key => $value) {
      if(!array_key_exists($value['name_thirdparty_crypto'], $this->_getThirdParty())) continue;
      $tp = $this->_getThirdParty()[$value['name_thirdparty_crypto']];
      if($tp->_isActivated() != false) $listThirdpartyActivated[] = $tp;
      else $listThirdparty[] = $tp;
    }

    $listThirdpartyOrdered = [];

    usort($listThirdpartyActivated, array($this, '_sortThirdpartyListSymbolSelected'));

    foreach ($listThirdpartyActivated as $tp) { $listThirdpartyOrdered[] = $tp; }
    foreach ($listThirdparty as $tp) { $listThirdpartyOrdered[] = $tp; }

    return $listThirdpartyOrdered;

  }

  public function _getMarketTradeAvailable($CryptoApi, $limit = 30, $search = null){
    $res = [];
    if($this->_getApp()->_hiddenThirdpartyActive()){
      $req = parent::querySqlRequest("SELECT * FROM thirdparty_crypto_krypto WHERE name_thirdparty_crypto=:name_thirdparty_crypto AND to_thirdparty_crypto=:to_thirdparty_crypto LIMIT ".$limit,
                                    [
                                      'name_thirdparty_crypto' => $this->_getApp()->_hiddenThirdpartyService(),
                                      'to_thirdparty_crypto' => 'USD'
                                    ]);
    } else {

      if(is_null($search)){
        $req = parent::querySqlRequest("SELECT * FROM thirdparty_crypto_krypto WHERE name_thirdparty_crypto=:name_thirdparty_crypto LIMIT ".$limit,
                                      [
                                        'name_thirdparty_crypto' => $this->_getNameSelectedThirdPartyUser()
                                      ]);

        if(count($req) < $limit){
          $req = array_merge($req, parent::querySqlRequest("SELECT * FROM thirdparty_crypto_krypto LIMIT ".($limit - count($req))));
        }
      } else {
        $infosSearch = explode('/', $search);
        if(count($infosSearch) == 2){
          $req = parent::querySqlRequest("SELECT * FROM thirdparty_crypto_krypto WHERE symbol_thirdparty_crypto=:symbol_thirdparty_crypto AND to_thirdparty_crypto=:to_thirdparty_crypto LIMIT ".$limit,
                                        [
                                          'symbol_thirdparty_crypto' => $infosSearch[0],
                                          'to_thirdparty_crypto' => $infosSearch[1]
                                        ]);
        } else {
          $req = parent::querySqlRequest("SELECT * FROM thirdparty_crypto_krypto WHERE name_thirdparty_crypto=:search_query_second OR symbol_thirdparty_crypto=:search_query OR to_thirdparty_crypto=:search_query LIMIT ".$limit, [
            'search_query' => $search,
            'search_query_second' => strtolower($search)
          ]);
        }
      }

      //$req = parent::querySqlRequest("SELECT * FROM thirdparty_crypto_krypto LIMIT ".$limit);
    }
    foreach ($req as $key => $dataMarket) {
      $CryptoApi->_setCurrency([$dataMarket['to_thirdparty_crypto'], $dataMarket['to_thirdparty_crypto']]);
      try {
        $marketS = [];
        $marketS['coin'] = new CryptoCoin($CryptoApi, $dataMarket['symbol_thirdparty_crypto']);
        $marketS['market'] = $dataMarket;
        $marketS['cryptoapi'] = clone $CryptoApi;
        $res[] = $marketS;
      } catch (Exception $e) {}

    }
    return $res;
  }

  public function _symbolAvailableTrading($from, $to, $service = null){
    return count($this->_thirdparySymbolTrading($from, $to, $service)) > 0;
  }

  public function _saveOrder($type, $amount, $symbol, $to, $tp){

    $r = parent::execSqlRequest("INSERT INTO order_krypto (id_user, time_order, type_order, amount_order, symbol_order, currency_order, thirdparty_order)
                                 VALUES (:id_user, :time_order, :type_order, :amount_order, :symbol_order, :currency_order, :thirdparty_order)",
                                 [
                                   'id_user' => $this->_getUser()->_getUserID(),
                                   'time_order' => date('d/m/Y H:i:00', time()),
                                   'type_order' => strtoupper($type),
                                   'amount_order' => $amount,
                                   'symbol_order' => $symbol,
                                   'currency_order' => $to,
                                   'thirdparty_order' => $tp
                                 ]);

      if(!$r) throw new Exception("Error SQL : Fail to add order in sql", 1);

      return true;

  }

  public function _getNameSelectedThirdPartyUser(){
    if($this->selectedThirdparty != 'none') return $this->selectedThirdparty;
    $r = parent::querySqlRequest("SELECT * FROM user_thirdparty_selected_krypto WHERE id_user=:id_user", ['id_user' => $this->_getUser()->_getUserID()]);
    if(count($r) == 0) $this->selectedThirdparty = null;
    else $this->selectedThirdparty = $r[0]['name_user_thirdparty_selected'];
    return $this->selectedThirdparty;
  }

  public function _sortListThirdpartyAvailable($a, $b){
    if($this->_getNameSelectedThirdPartyUser() == $a->_getExchangeName()) return -1;
    return 1;
  }

  public function _getThirdPartyListAvailable(){
    $r = [];
    foreach ($this->_getThirdParty() as $Thirdparty) {
      if($Thirdparty->_isActivated()) $r[] = $Thirdparty;
    }
    usort($r, array($this, '_sortListThirdpartyAvailable'));
    return $r;
  }

  public function _getExchange($exchange){
    if(!array_key_exists($exchange, $this->_getThirdParty())) return null;
    return $this->_getThirdParty()[$exchange];
  }

  public function _getInternalOrderList($symbol){

    return parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE symbol_internal_order=:symbol_internal_order AND date_internal_order > :date_internal_order",
                                  [
                                    'symbol_internal_order' => $symbol,
                                    'date_internal_order' => (time() - 86400)
                                  ]);

  }

  public function _getLeaderBoard(){
    $res = [];
    $rank = 1;
    foreach (parent::querySqlRequest("SELECT * FROM leader_board_krypto ORDER BY benef_leader_board DESC") as $key => $value) {

      $UserRank = new User($value['id_user']);

      $res[] = [
        'id_user' => $UserRank->_getUserID(),
        'benefic' => $value['benef_leader_board'],
        'rank' => $rank,
        'name' => $UserRank->_getName(),
        'country' => $UserRank->_getUserLocation(true)
      ];
      $rank++;
    }
    return $res;
  }

  public function _getLeaderBoardUser($User){
    $rank = 1;
    foreach (parent::querySqlRequest("SELECT * FROM leader_board_krypto ORDER BY benef_leader_board DESC") as $key => $value) {
      if($value['id_user'] == $User->_getUserID()){
        $value['rank'] = $rank;
        return $value;
        break;
      }
      $rank++;
    }
  }

  public function _saveThirdpartySettings($exchange, $rstring, $rargstring, $args, $updateString){
    $r = parent::querySqlRequest("SELECT * FROM ".$exchange."_krypto WHERE id_user=:id_user", ['id_user' => $this->_getUser()->_getUserID()]);
    if(count($r) == 0){
      $r = parent::execSqlRequest("INSERT INTO ".$exchange."_krypto (".$rstring.") VALUES (".$rargstring.")", $args);
      if(!$r) throw new Exception("Error : Fail to save ".$exchange, 1);
    } else {
      $r = parent::execSqlRequest("UPDATE ".$exchange."_krypto SET ".$updateString." WHERE id_user=:id_user", $args);
      if(!$r) throw new Exception("Error : Fail to update ".$exchange, 1);
    }
    return true;

  }

  public function _removeThirdparty($Exchange){
    if(!array_key_exists($Exchange->_getExchangeName(), $this->_getThirdPartyConfig())) throw new Exception("Permission denied", 1);
    $r = parent::execSqlRequest("DELETE FROM ".$Exchange->_getExchangeName().'_krypto WHERE id_user=:id_user',
                                [
                                  'id_user' => $this->_getUser()->_getUserID()
                                ]);
    if(!$r) throw new Exception("Error : Fail to delete", 1);
    if($this->_getNameSelectedThirdPartyUser() == $Exchange->_getExchangeName()){
      $r = parent::execSqlRequest("DELETE FROM user_thirdparty_selected_krypto WHERE id_user=:id_user",
                                  [
                                    'id_user' => $this->_getUser()->_getUserID()
                                  ]);
      if(!$r) throw new Exception("Error : Fail to remove seletect thirdparty", 1);

    }
    return true;
  }

  public function _changeFirstExchange($exchange){
    $r = parent::querySqlRequest("SELECT * FROM user_thirdparty_selected_krypto WHERE id_user=:id_user", ['id_user' => $this->_getUser()->_getUserID()]);
    if(count($r) > 0){
      $r = parent::execSqlRequest("UPDATE user_thirdparty_selected_krypto SET name_user_thirdparty_selected=:name_user_thirdparty_selected WHERE id_user=:id_user",
                                  [
                                    'id_user' => $this->_getUser()->_getUserID(),
                                    'name_user_thirdparty_selected' => $exchange->_getExchangeName()
                                  ]);
    } else {
      $r = parent::execSqlRequest("INSERT INTO user_thirdparty_selected_krypto (id_user, name_user_thirdparty_selected) VALUES (:id_user, :name_user_thirdparty_selected)",
                                  [
                                    'id_user' => $this->_getUser()->_getUserID(),
                                    'name_user_thirdparty_selected' => $exchange->_getExchangeName()
                                  ]);
    }
    if(!$r) throw new Exception("Error : Fail to change exchange", 1);

  }

  public function _generateLeaderBoard($delay = (7 * 24 * 60 * 60)){
    $userList = parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE date_internal_order > :date_internal_order GROUP BY id_user",
                                [
                                  'date_internal_order' => time() - $delay
                                ]);
    $benefList = [];

    $CryptoApi = new CryptoApi(null, null, $this->_getApp());

    foreach ($userList as $userInfos) {
      $UserFetched = new User($userInfos['id_user']);
      $BalanceUser = new Balance($UserFetched, $this->_getApp(), 'real');
      $BalanceEvolution = $BalanceUser->_getBalanceEvolution($CryptoApi);
      $TotalBenef = $BalanceEvolution['total'] - $BalanceUser->_getBalanceInvestisment();
      $benefList[$UserFetched->_getUserID()] = ($TotalBenef > 0 ? $TotalBenef : 0);
    }

    $User = new User();
    foreach ($User->_getUserList() as $key => $userInfos) {
      if(!array_key_exists($userInfos['id_user'], $benefList)){
        $benefList[$userInfos['id_user']] = 0;
      }
    }

    arsort($benefList);

    $r = parent::execSqlRequest("DELETE FROM leader_board_krypto");
    if(!$r) throw new Exception("Error leader board : Fail to clean table", 1);

    foreach ($benefList as $userID => $benef) {
      $r = parent::execSqlRequest("INSERT INTO leader_board_krypto (id_user, benef_leader_board) VALUES (:id_user, :benef_leader_board)",
                                  [
                                    'id_user' => $userID,
                                    'benef_leader_board' => $benef
                                  ]);
      if(!$r) throw new Exception("Error leader board : Fail to insert (".$userID.", ".$benef.")", 1);

    }

    return true;


  }


}

?>
