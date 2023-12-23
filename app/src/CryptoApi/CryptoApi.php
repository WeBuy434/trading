<?php

/**
 * CryptoApi CLass
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */
class CryptoApi extends MySQL {

  /**
   * API Url called
   * @var Array
   */
  private $ApiURL = [
    'www' => 'https://www.cryptocompare.com/api/data/',
    'min-api' => 'https://min-api.cryptocompare.com/data/'
  ];

  /**
   * User logged
   * @var User
   */
  private $User = null;

  /**
   * Currency
   * @var String
   */
  private $Currency = null;

  /**
   * Coin list available
   * @var Array
   */
  private $CoinList = [];

  private $Market = null;

  /**
   * CryptoApi constructor
   * @param User $User Logged user
   */
  public function __construct($User = null, $Currency = null, $App = null, $market = 'CCCAGG'){
    if(is_null($User)) $this->User = new User();
    else $this->User = $User;

    $this->Market = $market;

    $this->Currency = $Currency;
  }

  /**
   * Get user associate to CryptoApi
   * @return User User associate
   */
  public function _getUser(){
    if(is_null($this->User)) throw new Exception("Error : User is null in CryptoApi", 1);
    return $this->User;
  }

  /**
   * Get currency selected
   * @return String Currency (ex : USD)
   */
  public function _getCurrency(){
    if(!is_null($this->Currency)) return $this->Currency[0];
    return $this->_getUser()->_getCurrency();
  }

  public function _setCurrency($data){
    $this->Currency = $data;
  }

  public function _getMarket(){
    return $this->Market;
  }

  /**
   * Get currency selected symbol
   * @return String Currency symbol (ex : $)
   */
  public function _getCurrencySymbol(){
    if(!is_null($this->Currency)) return $this->Currency[1];
    return $this->_getUser()->_getCurrencySymbol();
  }

  /**
   * Get API URl
   * @param  string $tapi Type api (www or min-apî)
   * @return String Api URL
   */
  private function _getApiURL($tapi = "min-api"){ return $this->ApiURL[$tapi]; }

  /**
   * Parse data header
   * @param  String $header Header
   * @return Int            Header respond code
   */
  public function _parseDataHeader($header){
    foreach ($header as $keyHeader => $headerItem) {
      if(preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#", $headerItem, $out)) return intval($out[1]);
    }
    return 10;
  }

  /**
   * Get data from API
   * @param  String $service Service called
   * @param  Array $args     Service args
   * @param  string $tapi    Type api
   * @return Array           Service respond
   */
  public function _getData($service, $args = null, $tapi = "min-api", $c = 0){

    if($c >= 4){
      error_log('Exceed ressourced : '.$c.'; '. $this->_getApiURL($tapi).$service.$argsStr);
    }

    // Parse args as url args
    $argsStr = "";
    if(count($args) > 0){
      $argsStr = "?";
      foreach ($args as $argsKey => $argsValue) {
        $argsStr .= $argsKey."=".$argsValue.'&';
      }
      $argsStr = substr($argsStr, 0, -1);
    }

    if(!is_null($this->_getMarket())) $argsStr .= "&e=".$this->_getMarket();

    $cacheSystem = parent::querySqlRequest("SELECT * FROM cache_krypto WHERE service_cache=:service_cache AND last_update_cache > :last_update_cache",
                                          [
                                            'service_cache' => $this->_getApiURL($tapi).$service.$argsStr,
                                            'last_update_cache' => time()
                                          ]);

    if(count($cacheSystem) > 0) return json_decode($cacheSystem[0]['value_cache'], true);

    // Get service result
    $ch =  curl_init($this->_getApiURL($tapi).$service.$argsStr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_ENCODING,  '');
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    $s = json_decode(curl_exec($ch), true);
    //$s = json_decode(file_get_contents($this->_getApiURL($tapi).$service.$argsStr), true);

    if(array_key_exists('Data', $s)){
        $this->_saveInCache($this->_getApiURL($tapi).$service.$argsStr, $s['Data']);
    } else {
        $this->_saveInCache($this->_getApiURL($tapi).$service.$argsStr, $s);
    }

    // Check if service result is not null
    if(is_null($s)){
      throw new Exception("Error : Null given for : ".$service.'; tapi = '.$tapi.'; args = '.$argsStr, 1);
    } else {
       return $this->_getData($service, $args, 'min-api', $c++);
    }

    // Parse result header
    //if($this->_parseDataHeader($http_response_header) < 100) throw new Exception("Error while fetching data (service = ".$service.")", 1);

    // Check respond validy
    if(array_key_exists('Response', $s) && $s['Response'] == "Error"){

      if($tapi == "min-api") throw new Exception("Error get data API : Service (".$service."); ".json_encode($s), 1);
      if($service == "coinlist") $service = "all/coinlist";
      // Type with an other api
      $this->_getData($service, $args, 'min-api');
      return false;
    }


    if(array_key_exists('Data', $s)) return $s['Data'];

    return $s;
  }

  private function _saveInCache($service, $data){
    $cacheSystem = parent::querySqlRequest("SELECT * FROM cache_krypto WHERE service_cache=:service_cache",
                                          [
                                            'service_cache' => $service
                                          ]);

    if(count($cacheSystem) > 0){
      $cacheSystem = parent::execSqlRequest("UPDATE cache_krypto SET value_cache=:value_cache, last_update_cache=:last_update_cache WHERE service_cache=:service_cache",
                                  [
                                    'last_update_cache' => time() + 10,
                                    'value_cache' => json_encode($data),
                                    'service_cache' => $service
                                  ]);
      if(!$cacheSystem){
        error_log('Fail to update cache');
        throw new Exception("Error : Fail to update cache", 1);
      }

    } else {
      $cacheSystem = parent::execSqlRequest("INSERT INTO cache_krypto (service_cache, value_cache, last_update_cache) VALUES (:service_cache, :value_cache, :last_update_cache)",
                                [
                                  'service_cache' => $service,
                                  'value_cache' => json_encode($data),
                                  'last_update_cache' => time() + 10
                                ]);
      if(!$cacheSystem){
        error_log('Fail to insert cache');
        throw new Exception("Error : Fail to insert cache", 1);
      }
    }
    return true;
  }

  /**
   * Get list coins available
   * @param  Int $size           Number of coins needed
   * @param  Boolean $slice      If slice coins
   * @param  Boolean $onlysymbol Get only coins symbol
   * @param  String  $search     Get coins search
   * @param  Int $startat        Start slice at
   * @return Array               CryptoCoin array object
   */
  public function _getCoinsList($size = 50, $slice = true, $onlysymbol = false, $search = null, $startat = 0, $symbolAdavanced = false, $forceShowDisabled = false){

    // If coinlist already called & saved in var and not symbol needed
    // if(count($this->CoinList) > 0 && !$onlysymbol){
    //   if($slice) return array_slice($this->CoinList, $startat, ($size + $startat));
    //   return $this->CoinList;
    // }

    // Get list coin in Database
    $listCoinMySQL = parent::querySqlRequest("SELECT * FROM coinlist_krypto WHERE (status_coinslist=:status_coinslist OR status_coinslist=:status_coinslist_alt) AND (fullname_coinlist LIKE :sskey
                                                                                  OR symbol_coinlist LIKE :sskey
                                                                                  OR coinname_coinlist LIKE :sskey)
                                                                                  ORDER BY order_coinlist LIMIT ".($size + $startat),
                                                                                  [
                                                                                    'sskey' => '%'.$search.'%',
                                                                                    'status_coinslist' => 1,
                                                                                    'status_coinslist_alt' => ($forceShowDisabled ? 0 : 1)
                                                                                  ]);

    $listCoinAPI = null;

    // If coin list > 0
    if(count($listCoinMySQL) > 0){

      // Append database saved coins
      foreach ($listCoinMySQL as $valCoin) {
        $listCoinAPI[] = [
          'Id' => $valCoin['currencyid_coinlist'],
          'Url' => $valCoin['url_coinlist'],
          'Name' => $valCoin['symbol_coinlist'],
          'CoinName' => $valCoin['coinname_coinlist'],
          'FullName' => $valCoin['fullname_coinlist'],
          'Algorithm' => $valCoin['algorithm_coinlist'],
          'ProofType' => $valCoin['prooftype_coinlist'],
          'SortOrder' => $valCoin['order_coinlist'],
          'Symbol' => $valCoin['symbol_coinlist'],
          'Status' => $valCoin['status_coinslist'],
          'Source' => $valCoin['source_coinlist']
        ];
      }

    } else { // If coins database is empty, call in API
      if($search != null) return [];

      // Get list coins available
      $listCoinAPI = $this->_getData('coinlist', null, 'www');

      // Sort coins lisy by order weight
      usort($listCoinAPI, function($a, $b){
                              if($a['SortOrder'] == $b['SortOrder']) return 0;
                              if($a['SortOrder'] < $b['SortOrder']) return -1;
                              return 1;
                          });

      // Save list coins in database
      foreach ($listCoinAPI as $CoinSymbol => $CoinData) {
        $r = parent::execSqlRequest("INSERT INTO coinlist_krypto (currencyid_coinlist, symbol_coinlist, fullname_coinlist, coinname_coinlist, order_coinlist, algorithm_coinlist, prooftype_coinlist, url_coinlist)
                                      VALUES (:currencyid_coinlist, :symbol_coinlist, :fullname_coinlist, :coinname_coinlist, :order_coinlist, :algorithm_coinlist, :prooftype_coinlist, :url_coinlist)",
                                      [
                                        'currencyid_coinlist' => $CoinData['Id'],
                                        'symbol_coinlist' => $CoinData['Name'],
                                        'fullname_coinlist' => $CoinData['FullName'],
                                        'coinname_coinlist' => $CoinData['CoinName'],
                                        'order_coinlist' => $CoinData['SortOrder'],
                                        'algorithm_coinlist' => $CoinData['Algorithm'],
                                        'prooftype_coinlist' => $CoinData['ProofType'],
                                        'url_coinlist' => $CoinData['Url']
                                      ]);
      }
    }

    // If only symbol needed
    if($onlysymbol){
      $symbolList = [];
      foreach (array_slice($listCoinAPI, $startat, ($size + $startat)) as $CoinSymbol => $CoinData) {
        if($symbolAdavanced) $symbolList[$CoinData['Symbol']] = $CoinData;
        else $symbolList[$CoinData['Symbol']] = $CoinData['Symbol'];
      }
      return $symbolList;
    } else { // If CryptoCoin object needed
      foreach (array_slice($listCoinAPI, $startat, ($size + $startat)) as $CoinSymbol => $CoinData) {
        $this->CoinList[$CoinData['Symbol']] = new CryptoCoin($this, $CoinData['Symbol'], $CoinData);
      }
    }


    return $this->CoinList;
  }

  /**
   * Get coin by symbol
   * @param  String $symbol Coin symbol (ex : BTC)
   * @return CryptoCoin     Get CryptoCoin object
   */
  public function _getCoin($symbol){
    $listCoin = $this->_getCoinsList(99999, false);

    // Check if coins is founded
    if(!array_key_exists($symbol, $listCoin)) throw new Exception("Error : Coin (".$symbol.") not found", 1);
    return $listCoin[$symbol];
  }

  /**
   * Sync coin list
   */
  public function _syncCoinList(){

    $coinList = [];

    $coinmarketList = @file_get_contents('https://api.coinmarketcap.com/v1/ticker/');
    if(!$coinmarketList) throw new Exception("Error : Fail to get data from coinmarketcap", 1);
    $coinmarketList = json_decode($coinmarketList, true);
    foreach ($coinmarketList as $marketCapCoin) {
      $coinList[$marketCapCoin['symbol']] = floatval($marketCapCoin['24h_volume_usd']);
    }

    $listCoinAPI = $this->_getData('coinlist', null, 'www');

    foreach ($this->_getData('coinlist', null, 'www') as $coinSymbol => $dataCoin) {
      if(!array_key_exists($coinSymbol, $coinList)){
        $coinList[$coinSymbol] = floatval($dataCoin['SortOrder']) * -1;
      }
    }

    arsort($coinList);

    $pos = 1;
    foreach ($coinList as $symbol => $vol24) {
      $r = parent::execSqlRequest("UPDATE coinlist_krypto SET order_coinlist=:order_coinlist WHERE symbol_coinlist=:symbol_coinlist",
                                  [
                                    'order_coinlist' => $pos,
                                    'symbol_coinlist' => $symbol
                                  ]);

      if(!$r){
        throw new Exception("Error : Fail to update coin order (".$symbol.")", 1);
        break;
      }
      $pos++;
    }

  }

  public function _addCoin($currency_id, $symbol, $fullname, $coinname, $order, $algorithm, $proof, $url, $source){

    $r = parent::querySqlRequest("SELECT * FROM coinlist_krypto WHERE symbol_coinlist=:symbol_coinlist", ['symbol_coinlist' => $symbol]);
    if(count($r) > 0) return false;

    $r = parent::execSqlRequest("INSERT INTO coinlist_krypto (currencyid_coinlist, symbol_coinlist, fullname_coinlist, coinname_coinlist, order_coinlist, algorithm_coinlist, prooftype_coinlist, url_coinlist, status_coinslist, source_coinlist)
                                  VALUES (:currencyid_coinlist, :symbol_coinlist, :fullname_coinlist, :coinname_coinlist, :order_coinlist, :algorithm_coinlist, :prooftype_coinlist, :url_coinlist, :status_coinslist, :source_coinlist)",
                                  [
                                    'currencyid_coinlist' => $currency_id,
                                    'symbol_coinlist' => $symbol,
                                    'fullname_coinlist' => $fullname,
                                    'coinname_coinlist' => $coinname,
                                    'order_coinlist' => $order,
                                    'algorithm_coinlist' => $algorithm,
                                    'prooftype_coinlist' => $proof,
                                    'url_coinlist' => $url,
                                    'status_coinslist' => 1,
                                    'source_coinlist' => $source
                                  ]);
    if(!$r) throw new Exception("Error : Fail to add new coins", 1);
    return true;

  }

}

?>
