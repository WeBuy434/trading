<?php

class Balance extends MySQL {

  private $User = null;
  private $App = null;
  private $Type = null;
  private $BalanceTypeList = ['practice', 'real'];
  private $BalanceList = null;

  private $BalanceLimit = [
    'practice' => 10000,
    'real' => 500000000000
  ];

  private $BalanceData = null;

  private $CurrentBalance = null;

  public function __construct($User, $App, $Type = null){
    $this->User = $User;
    $this->App = $App;
    $this->Type = $Type;
    $this->BalanceLimit['practice'] = $App->_getMaximalFreeDeposit();
    $this->_checkBalanceUser();
    if(!is_null($Type) && in_array($Type, $this->BalanceTypeList)){
      $this->_loadBalance();
    }

  }

  public function _getUser(){ return $this->User; }
  public function _getApp(){ return $this->App; }

  public function _getType(){ return $this->Type; }

  public function _checkBalanceUser(){
    foreach ($this->BalanceTypeList as $type) {
      $r = parent::querySqlRequest("SELECT * FROM balance_krypto WHERE id_user=:id_user AND type_balance=:type_balance",
                                   [
                                     'id_user' => $this->_getUser()->_getUserID(),
                                     'type_balance' => $type
                                   ]);
      if(count($r) == 0){
        $r = parent::execSqlRequest("INSERT INTO balance_krypto (id_user, type_balance, created_balance)
                                     VALUES (:id_user, :type_balance, :created_balance)",
                                     [
                                       'id_user' => $this->_getUser()->_getUserID(),
                                       'type_balance' => $type,
                                       'created_balance' => time()
                                     ]);
        if(!$r) throw new Exception("Error SQL : Fail to create user balance", 1);

        if($type == 'practice'){
          $Balance = new Balance($this->_getUser(), $this->_getApp(), 'practice');
          $Balance->_addDeposit($this->_getApp()->_getMaximalFreeDeposit(), 'Initial');
        }

      }



    }
  }

  public function _getCurrentBalance(){
    if(!is_null($this->CurrentBalance)) return $this->CurrentBalance;
    $r = parent::querySqlRequest("SELECT * FROM balance_krypto WHERE id_user=:id_user AND active_balance=:active_balance",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'active_balance' => "1"
                                ]);
    if(count($r) == 0) $this->CurrentBalance = new Balance($this->_getUser(), $this->_getApp(), 'practice');
    else $this->CurrentBalance = new Balance($this->_getUser(), $this->_getApp(), $r[0]['type_balance']);

    return $this->CurrentBalance;
  }

  public function _getBalanceList(){
    if(!is_null($this->BalanceList)) return $this->BalanceList;
    foreach ($this->BalanceTypeList as $type) {
      if($type == 'real' && !$this->_getApp()->_getTradingEnableRealAccount()) continue;
      $this->BalanceList[] = new Balance($this->_getUser(), $this->_getApp(), $type);
    }
    return $this->BalanceList;
  }

  private function _loadBalance(){
    $r = parent::querySqlRequest("SELECT * FROM balance_krypto WHERE id_user=:id_user AND type_balance=:type_balance",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'type_balance' => $this->_getType()
                                ]);
    if(count($r) == 0) throw new Exception("Error : Fail to get balance (".$this->_getType().")", 1);

    $this->BalanceData = $r[0];
  }

  public function _getBalanceKeyData($k){
    // Check if coin data is loaded
    if(is_null($this->BalanceData)) $this->_loadBalance();

    // Check if key is founded
    if(!array_key_exists($k, $this->BalanceData)) throw new Exception("Error : ".$k." not exist in Balance (".$this->_getType().")", 1);

    // Return associate value
    return $this->BalanceData[$k];
  }

  public function _getBalanceID($encrypted = false){
    if($encrypted) return App::encrypt_decrypt('encrypt', $this->_getBalanceKeyData('id_balance'));
    return $this->_getBalanceKeyData('id_balance');
  }

  public function _getBalanceType(){
    return $this->_getBalanceKeyData('type_balance');
  }

  public function _addDeposit($amount, $payment_type = 'referal', $description = null){

    $r = parent::execSqlRequest("INSERT INTO deposit_history_krypto (id_user, amount_deposit_history, date_deposit_history, balance_deposit_history, payment_status_deposit_history, payment_type_deposit_history, description_deposit_history) VALUES
                                 (:id_user, :amount_deposit_history, :date_deposit_history, :balance_deposit_history, :payment_status_deposit_history, :payment_type_deposit_history, :description_deposit_history)",
                                 [
                                   'id_user' => $this->_getUser()->_getUserID(),
                                   'amount_deposit_history' => floatval($amount),
                                   'date_deposit_history' => time(),
                                   'balance_deposit_history' => $this->_getBalanceID(),
                                   'payment_status_deposit_history' => 1,
                                   'payment_type_deposit_history' => $payment_type,
                                   'description_deposit_history' => (!is_null($description) ? $description : 'Deposit '.$this->_getApp()->_formatNumber($amount, 2).' $')
                                 ]);

    if(!$r) throw new Exception("Error SQL : Fail to add deposit in database", 1);


    return true;

  }

  public function _getDepositHistory(){
    return parent::querySqlRequest("SELECT * FROM deposit_history_krypto WHERE balance_deposit_history=:balance_deposit_history AND id_user=:id_user AND payment_status_deposit_history=:payment_status_deposit_history",
                                   [
                                     'balance_deposit_history' => $this->_getBalanceID(),
                                     'id_user' => $this->_getUser()->_getUserID(),
                                     'payment_status_deposit_history' => 1
                                   ]);

  }

  public function _getWidthdrawHistory($onlyapproved = false, $all = false){

    if($all) return parent::querySqlRequest("SELECT * FROM widthdraw_history_krypto WHERE id_balance=:id_balance AND id_user=:id_user",
                                  [
                                    'id_balance' => $this->_getBalanceID(),
                                    'id_user' => $this->_getUser()->_getUserID()
                                  ]);

    if($onlyapproved) return parent::querySqlRequest("SELECT * FROM widthdraw_history_krypto WHERE id_balance=:id_balance AND id_user=:id_user AND status_widthdraw_history != :status_widthdraw_history",
                                  [
                                    'id_balance' => $this->_getBalanceID(),
                                    'id_user' => $this->_getUser()->_getUserID(),
                                    'status_widthdraw_history' => 0
                                  ]);

    return parent::querySqlRequest("SELECT * FROM widthdraw_history_krypto WHERE id_balance=:id_balance AND id_user=:id_user AND (:status_widthdraw_history != :status_widthdraw_history OR :date_widthdraw_history < date_widthdraw_history)",
                                  [
                                    'id_balance' => $this->_getBalanceID(),
                                    'id_user' => $this->_getUser()->_getUserID(),
                                    'status_widthdraw_history' => '0',
                                    'date_widthdraw_history' => time() - 3600
                                  ]);


  }

  public function _getOrderHistory($side = null, $symbol = null){
    if(!is_null($side)){
      if(!is_null($symbol)){
        return parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE id_balance=:id_balance AND id_user=:id_user AND side_internal_order=:side_internal_order AND symbol_internal_order=:symbol_internal_order",
                                      [
                                        'id_user' => $this->_getUser()->_getUserID(),
                                        'id_balance' => $this->_getBalanceID(),
                                        'side_internal_order' => $side,
                                        'symbol_internal_order' => $symbol
                                      ]);
      } else {
        return parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE id_balance=:id_balance AND id_user=:id_user AND side_internal_order=:side_internal_order",
                                      [
                                        'id_user' => $this->_getUser()->_getUserID(),
                                        'id_balance' => $this->_getBalanceID(),
                                        'side_internal_order' => $side
                                      ]);
      }

    }

    if(!is_null($symbol)){
      return parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE id_balance=:id_balance AND id_user=:id_user AND symbol_internal_order=:symbol_internal_order",
                                    [
                                      'id_user' => $this->_getUser()->_getUserID(),
                                      'id_balance' => $this->_getBalanceID(),
                                      'symbol_internal_order' => $symbol
                                    ]);
    }

    return parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE id_balance=:id_balance AND id_user=:id_user",
                                  [
                                    'id_user' => $this->_getUser()->_getUserID(),
                                    'id_balance' => $this->_getBalanceID()
                                  ]);
  }

  public function _getBalanceValue(){
    $total = 0;
    foreach ($this->_getDepositHistory() as $infosDeposit) {
      $total += floatval($infosDeposit['amount_deposit_history']);
    }

    foreach ($this->_getOrderHistory() as $infosOrder) {
      if($infosOrder['side_internal_order'] == "BUY") $total -= (floatval($infosOrder['usd_amount_internal_order']) + floatval($infosOrder['fees_internal_order']));
      else $total += (floatval($infosOrder['usd_amount_internal_order']) - floatval($infosOrder['fees_internal_order']));
    }

    foreach ($this->_getWidthdrawHistory() as $infosOrder) {
      $total -= floatval($infosOrder['amount_widthdraw_history']);
    }

    return $total;
  }

  public function _getBalanceInvestisment($symbol = null){
    $total = 0;
    foreach ($this->_getOrderHistory(null, $symbol) as $infosOrder) {
      if($infosOrder['side_internal_order'] == "BUY") $total += floatval($infosOrder['usd_amount_internal_order']);
      else $total -= floatval($infosOrder['usd_amount_internal_order']);
    }
    return $total;
  }

  public function _getBalanceEvolution($CryptoApi, $Symbol = null){
    $total = 0;
    $CoinPrice = [];
    $CoinValue = [];
    foreach ($this->_getOrderHistory(null, $Symbol) as $infosOrder) {
      if(!array_key_exists($infosOrder['symbol_internal_order'], $CoinValue)){
        $CoinValue[$infosOrder['symbol_internal_order']]['usd'] = 0;
        $CoinValue[$infosOrder['symbol_internal_order']]['amount'] = 0;
      }

      if($infosOrder['side_internal_order'] == "BUY") {
        $CoinValue[$infosOrder['symbol_internal_order']]['usd'] += floatval($infosOrder['usd_amount_internal_order']);
        $CoinValue[$infosOrder['symbol_internal_order']]['amount'] += floatval($infosOrder['amount_internal_order']);
      }
      else {
        $CoinValue[$infosOrder['symbol_internal_order']]['usd'] -= floatval($infosOrder['usd_amount_internal_order']);
        $CoinValue[$infosOrder['symbol_internal_order']]['amount'] -= floatval($infosOrder['amount_internal_order']);
      }

    }

    $totalContain = 0;
    foreach ($CoinValue as $SymbolFetched => $ValueOrdered) {
      if(!array_key_exists($SymbolFetched, $CoinPrice)){
        $Coin = new CryptoCoin($CryptoApi, $SymbolFetched);
        $CoinPrice[$SymbolFetched] = $Coin->_getPrice();
      }

      $Price = $CoinPrice[$SymbolFetched];
      $totalContain += floatval($ValueOrdered['amount']);
      $total += ($ValueOrdered['amount'] * $Price);

    }

    return [
      'total' => $total,
      'contain' => $totalContain,
      'evolv' => ($this->_getBalanceInvestisment($Symbol) == 0 ? '0' : ($total - $this->_getBalanceInvestisment($Symbol)) / $this->_getBalanceInvestisment($Symbol) * 100)
    ];
  }

  public function _getBalanceTotal($CryptoApi){
    return $this->_getBalanceValue() + ($this->_getBalanceEvolution($CryptoApi)['total'] - $this->_getBalanceInvestisment()) + $this->_getBalanceInvestisment();
  }

  public function _saveOrder($exchange, $amount, $usd_total, $side, $symbol, $order){
    $fees = floatval($usd_total) * ($this->_getApp()->_hiddenThirdpartyTradingFee() / 100);
    $r = parent::execSqlRequest("INSERT INTO internal_order_krypto (id_user, date_internal_order, id_balance, thirdparty_internal_order, amount_internal_order, usd_amount_internal_order, symbol_internal_order, fees_internal_order, order_key_internal_order, side_internal_order)
                                  VALUES (:id_user, :date_internal_order, :id_balance, :thirdparty_internal_order, :amount_internal_order, :usd_amount_internal_order, :symbol_internal_order, :fees_internal_order, :order_key_internal_order, :side_internal_order)",
                                  [
                                    'id_user' => $this->_getUser()->_getUserID(),
                                    'date_internal_order' => time(),
                                    'id_balance' => $this->_getBalanceID(),
                                    'thirdparty_internal_order' => $exchange->_getExchangeName(),
                                    'amount_internal_order' => $amount,
                                    'usd_amount_internal_order' => $usd_total,
                                    'symbol_internal_order' => $symbol,
                                    'side_internal_order' => $side,
                                    'order_key_internal_order' => $order['id'],
                                    'fees_internal_order' => $fees
                                  ]);
    if(!$r) throw new Exception("Error : Fail to save internal order", 1);

  }

  public function _changeActiveBalance($bid){

    $infosBalance = parent::querySqlRequest("SELECT * FROM balance_krypto WHERE id_balance=:id_balance AND id_user=:id_user",
                                [
                                  'id_balance' => $bid,
                                  'id_user' => $this->_getUser()->_getUserID()
                                ]);

    if(count($infosBalance) == 0) throw new Exception("Error : Fail to change balance", 1);

    $r = parent::execSqlRequest("UPDATE balance_krypto SET active_balance=:active_balance WHERE id_user=:id_user AND active_balance=:st_active_balance",
                                [
                                  'active_balance' => 0,
                                  'st_active_balance' => 1,
                                  'id_user' => $this->_getUser()->_getUserID()
                                ]);

    $r = parent::execSqlRequest("UPDATE balance_krypto SET active_balance=:active_balance WHERE id_user=:id_user AND id_balance=:id_balance",
                               [
                                 'active_balance' => 1,
                                 'id_user' => $this->_getUser()->_getUserID(),
                                 'id_balance' => $bid
                               ]);

    if(!$r) throw new Exception("Error : Fail to change status", 1);

    return [
      'id_balance' => $infosBalance[0]['id_balance'],
      'enc_id_balance' => App::encrypt_decrypt('encrypt', $infosBalance[0]['id_balance']),
      'type_balance' => $infosBalance[0]['type_balance'],
      'title' => $infosBalance[0]['type_balance'].' account'
    ];

  }

  public function _getLimits(){
    return $this->BalanceLimit;
  }

  public function _limitReached($showAmountNeeded = false){
    if($showAmountNeeded) return $this->_getLimits()[$this->_getBalanceType()] - $this->_getBalanceValue();
    return $this->_getBalanceValue() >= $this->_getLimits()[$this->_getBalanceType()];
  }

  public function _getBalanceByID($bid){
    $r = parent::querySqlRequest("SELECT * FROM balance_krypto WHERE id_balance=:id_balance AND id_user=:id_user",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'id_balance' => $bid
                                ]);
    if(count($r) == 0) throw new Exception("Error : Balance not found", 1);
    return new Balance($this->_getUser(), $this->_getApp(), $r[0]['type_balance']);
  }

  public function _validateDeposit($keycharge, $status, $amount, $typepayment, $datapayment, $fees = 0){
    $BalanceReal = new Balance($this->_getUser(), $this->_getApp(), 'real');
    $r = parent::execSqlRequest("INSERT INTO deposit_history_krypto (id_user, amount_deposit_history, date_deposit_history, balance_deposit_history, payment_type_deposit_history, payment_data_deposit_history, payment_status_deposit_history, description_deposit_history, fees_deposit_history)
                                  VALUES (:id_user, :amount_deposit_history, :date_deposit_history, :balance_deposit_history, :payment_type_deposit_history, :payment_data_deposit_history, :payment_status_deposit_history, :description_deposit_history, :fees_deposit_history)",
                                  [
                                    'id_user' => $this->_getUser()->_getUserID(),
                                    'amount_deposit_history' => $amount,
                                    'date_deposit_history' => time(),
                                    'balance_deposit_history' => $BalanceReal->_getBalanceID(),
                                    'payment_type_deposit_history' => $typepayment,
                                    'payment_data_deposit_history' => json_encode($datapayment),
                                    'payment_status_deposit_history' => $status,
                                    'description_deposit_history' => ucfirst($typepayment).' payment',
                                    'fees_deposit_history' => $fees
                                  ]);


      if(!$r) throw new Exception("Error SQL : Fail to add deposit in database", 1);



      if($BalanceReal->_getBalanceType() == 'real' && $this->_getApp()->_referalEnabled()){
        $r = parent::querySqlRequest("SELECT * FROM deposit_history_krypto WHERE id_user=:id_user AND balance_deposit_history=:balance_deposit_history",
                                    [
                                      'id_user' => $this->_getUser()->_getUserID(),
                                      'balance_deposit_history' => $BalanceReal->_getBalanceID()
                                    ]);


        if(count($r) == 1){

          $AssociateReferal = $BalanceReal->_getUser()->_getAssociateReferall();

          if(!is_null($AssociateReferal)){

            $Balance = new Balance($AssociateReferal, $BalanceReal->_getApp(), 'real');
            $Balance->_addDeposit($this->_getApp()->_getReferalWinAmount(), 'Referal', 'Referal commission ('.$this->_getUser()->_getEmail().')');

          }

        }
      }

  }

  public function _askWidthdraw($amount, $paypal){

    if(!filter_var($paypal, FILTER_VALIDATE_EMAIL)) throw new Exception("Please enter a valid email address", 1);
    if(!is_numeric($amount)) throw new Exception("Amount not valid", 1);
    if($amount > $this->_getBalanceValue()) throw new Exception("Amount not available on your balance", 1);

    $token = substr(str_shuffle( "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 50);


    $r = parent::execSqlRequest("INSERT INTO widthdraw_history_krypto (id_user, id_balance, amount_widthdraw_history, date_widthdraw_history, status_widthdraw_history, paypal_widthdraw_history, token_widthdraw_history, description_widthdraw_history)
                                VALUES (:id_user, :id_balance, :amount_widthdraw_history, :date_widthdraw_history, :status_widthdraw_history, :paypal_widthdraw_history, :token_widthdraw_history, :description_widthdraw_history)",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'id_balance' => $this->_getBalanceID(),
                                  'amount_widthdraw_history' => $amount,
                                  'date_widthdraw_history' => time(),
                                  'status_widthdraw_history' => 0,
                                  'paypal_widthdraw_history' => $paypal,
                                  'token_widthdraw_history' => $token,
                                  'description_widthdraw_history' => 'Widthdraw ('.$this->_getApp()->_formatNumber($amount, 2).' $) to '.$paypal
                                ]);

      if(!$r) throw new Exception("Error : Fail to create widthdraw request (please contact the support)", 1);

      $template = new Liquid\Template();
      $template->parse(file_get_contents(APP_URL.'/app/modules/kr-user/templates/confirmWidthdraw.tpl'));

      // Render & send email
      $this->_getApp()->_sendMail($this->_getUser()->_getEmail(), $this->_getApp()->_getAppTitle().' - Widthdraw confirmation needed', $template->render([
        'APP_URL' => APP_URL,
        'APP_TITLE' => $this->_getApp()->_getAppTitle(),
        'SUBJECT' => 'Password reset',
        'USER_NAME' => $this->_getUser()->_getName(),
        'CONFIRM_LINK' => APP_URL.'/app/modules/kr-trade/src/actions/askWidthdrawApprove.php?token='.App::encrypt_decrypt('encrypt', $token),
        'AMOUNT' => $this->_getApp()->_formatNumber($amount, 2).' $',
        'PAYPAL_EMAIL' => $paypal,
        'DATE' => date('d/m/Y H:i:s', time())
      ]));


  }

  public function _getAskWidthdrawEmail(){
    $r = parent::querySqlRequest("SELECT * FROM widthdraw_history_krypto WHERE id_user=:id_user AND id_balance=:id_balance",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'id_balance' => $this->_getBalanceID()
                                ]);

    if(count($r) == 0) return $this->_getUser()->_getEmail();
    return $r[0]['paypal_widthdraw_history'];
  }

  public function _askWidthdrawApprove($token){
    $r = parent::querySqlRequest("SELECT * FROM widthdraw_history_krypto WHERE id_user=:id_user AND id_balance=:id_balance AND token_widthdraw_history=:token_widthdraw_history",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'id_balance' => $this->_getBalanceID(),
                                  'token_widthdraw_history' => App::encrypt_decrypt('decrypt', $token)
                                ]);

    if(count($r) == 0) throw new Exception("Error : Wrong token", 1);

    if(time() - $r[0]['date_widthdraw_history'] > 3500) throw new Exception("Error : Widthdraw request has expire", 1);


    $rv = parent::execSqlRequest("UPDATE widthdraw_history_krypto SET status_widthdraw_history=:status_widthdraw_history WHERE id_user=:id_user AND id_balance=:id_balance AND token_widthdraw_history=:token_widthdraw_history",
                                [
                                  'status_widthdraw_history' => 1,
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'id_balance' => $this->_getBalanceID(),
                                  'token_widthdraw_history' => App::encrypt_decrypt('decrypt', $token)
                                ]);

    if(!$rv) throw new Exception("Error : Fail to change widthdraw status (contact support)", 1);

    $template = new Liquid\Template();
    $template->parse(file_get_contents(APP_URL.'/app/modules/kr-user/templates/adminEmail.tpl'));

    // Render & send email
    $this->_getApp()->_sendMail($this->_getApp()->_getSupportEmail(), $this->_getApp()->_getAppTitle().' - Withdraw asked ('.$this->_getUser()->_getEmail().')', $template->render([
      'APP_URL' => APP_URL,
      'APP_TITLE' => $this->_getApp()->_getAppTitle(),
      'SUBJECT' => 'Withdraw asked',
      'NAME' => $this->_getUser()->_getName(),
      'EMAIL' => $this->_getUser()->_getEmail(),
      'AMOUNT' => $this->_getApp()->_formatNumber($r[0]['amount_widthdraw_history'], 2).' $',
      'DATE' => date('d/m/Y H:i:s', time())
    ]));


  }

  public function _getTransactionsHistory(){
    $res = [];
    foreach ($this->_getDepositHistory() as $depositData) {
      $depositData['date_histo'] = intval($depositData['date_deposit_history']);
      $depositData['type_histo'] = 'deposit';
      $depositData['description_histo'] = $depositData['description_deposit_history'];
      $depositData['amount_histo'] = $depositData['amount_deposit_history'];
      $res[] = $depositData;
    }

    foreach ($this->_getWidthdrawHistory(false, true) as $depositData) {
      $depositData['date_histo'] = intval($depositData['date_widthdraw_history']);
      $depositData['type_histo'] = 'widthdraw';
      $depositData['description_histo'] = $depositData['description_widthdraw_history'];
      $depositData['amount_histo'] = $depositData['amount_widthdraw_history'];
      $res[] = $depositData;
    }

    function sortTransactionsHisto($a, $b){
      if($a['date_histo'] == $b['date_histo']) return 0;
      return ($a['date_histo'] > $b['date_histo']) ? -1 : 1;
    }

    usort($res, 'sortTransactionsHisto');

    return $res;
  }

  public function _getListTrade($symbol = null, $after = 0){
    if(!is_null($symbol)) return parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE id_user=:id_user AND id_balance=:id_balance AND symbol_internal_order=:symbol_internal_order AND date_internal_order > :date_internal_order ORDER BY id_internal_order DESC LIMIT 100",
                                  [
                                    'id_user' => $this->_getUser()->_getUserID(),
                                    'id_balance' => $this->_getBalanceID(),
                                    'symbol_internal_order' => $symbol,
                                    'date_internal_order' => $after
                                  ]);
    return parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE id_user=:id_user AND id_balance=:id_balance AND date_internal_order > :date_internal_order ORDER BY id_internal_order DESC",
                                  [
                                    'id_user' => $this->_getUser()->_getUserID(),
                                    'id_balance' => $this->_getBalanceID(),
                                    'date_internal_order' => $after
                                  ]);
  }

  public function _getOrderResumBySymbol($CryptoApi){

    $r = parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE id_user=:id_user AND id_balance=:id_balance GROUP BY symbol_internal_order",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'id_balance' => $this->_getBalanceID()
                                ]);

    $res = [];
    foreach ($r as $key => $symbolInternalOrder) {
      $resLine = [
        'coin' => new CryptoCoin($CryptoApi, $symbolInternalOrder['symbol_internal_order']),
        'evolv' => $this->_getBalanceEvolution($CryptoApi, $symbolInternalOrder['symbol_internal_order'])
      ];
      $res[] = $resLine;
    }
    return $res;

  }

  public function _checkPaymentResult(){
    if (empty($_GET) || empty($_GET['c']) || empty($_GET['t']) || empty($_GET['v'])) {
        return false;
    }

    if (!is_numeric($_GET['t']) || (time() - intval($_GET['t']) > 5)) {
        return false;
    }

    $listPaymentAvailable = ['paypal', 'mollie'];
    if (!in_array($_GET['c'], $listPaymentAvailable)) {
        return false;
    }


    $dataPayment = parent::querySqlRequest("SELECT * FROM deposit_history_krypto WHERE payment_data_deposit_history LIKE :payment_data_deposit_history AND id_user=:id_user AND payment_type_deposit_history=:payment_type_deposit_history",
                                        [
                                          'payment_data_deposit_history' => '%'.$_GET['v'].'%',
                                          'id_user' => $this->_getUser()->_getUserID(),
                                          'payment_type_deposit_history' => $_GET['c']
                                        ]);

    if(count($dataPayment) == 0){
      return false;
    }

    $dataPayment = $dataPayment[0];

    $keyCharge = null;
    if($_GET['c'] == "paypal"){
      $keyCharge = json_decode($dataPayment['payment_data_deposit_history'], true);
      $keyCharge = json_decode($keyCharge, true);
      $keyCharge = $keyCharge['id'];
    }

    if($_GET['c'] == "mollie"){
      $keyCharge = $_GET['v'];
    }

    echo '<script type="text/javascript">$(document).ready(function(){ showChargePopup("result_'.$_GET['c'].'", {k:"'.$keyCharge.'",t:"deposit"}); });</script>';

  }

  public function _getAmountCrypto($crypto){

    $r = parent::querySqlRequest("SELECT * FROM internal_order_krypto WHERE id_user=:id_user AND id_balance=:id_balance AND symbol_internal_order=:symbol_internal_order",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'id_balance' => $this->_getBalanceID(),
                                  'symbol_internal_order' => $crypto
                                ]);

    $valueAmount = 0;
    foreach ($r as $key => $value) {
      if($value['side_internal_order'] == "BUY"){
        $valueAmount += floatval($value['amount_internal_order']);
      } else {
        $valueAmount -= floatval($value['amount_internal_order']);
      }
    }

    return $valueAmount;

  }

  public function _validDeposit($orderid){
    $r = parent::querySqlRequest("SELECT * FROM deposit_history_krypto WHERE id_user=:id_user AND payment_status_deposit_history=:payment_status_deposit_history AND payment_data_deposit_history LIKE :payment_data_deposit_history",
                                [
                                  'id_user' => $this->_getUser()->_getUserID(),
                                  'payment_status_deposit_history' => 0,
                                  'payment_data_deposit_history' => '%'.$orderid.'%'
                                ]);

    if(count($r) == 0) throw new Exception("Error : Fail to receive order : ".$orderid, 1);


    $r = parent::execSqlRequest("UPDATE deposit_history_krypto SET payment_status_deposit_history=:payment_status_deposit_history WHERE id_deposit_history=:id_deposit_history AND payment_type_deposit_history=:payment_type_deposit_history AND id_user=:id_user",
                                [
                                  'payment_status_deposit_history' => '1',
                                  'id_deposit_history' => $r[0]['id_deposit_history'],
                                  'payment_type_deposit_history' => 'coingate',
                                  'id_user' => $this->_getUser()->_getUserID()
                                ]);

    if(!$r){
      throw new Exception("Error : Fail to change order status (".$orderid.")", 1);
    }

  }

  public function _removeDeposit($orderid){
    $r = parent::execSqlRequest("DELETE FROM deposit_history_krypto WHERE payment_data_deposit_history LIKE :payment_data_deposit_history AND payment_status_deposit_history=:payment_status_deposit_history",
                                [
                                  'payment_status_deposit_history' => '0',
                                  'payment_data_deposit_history' => '%'.$orderid.'%'
                                ]);
    if(!$r) throw new Exception("Error : Fail to remove deposit request", 1);

  }

  public function _setDoneWithdraw($request){

    $sv = explode('-', $request);
    if(count($sv) != 2) throw new Exception('Permission denied', 1);

    $request = $sv[1];

    $r = parent::querySqlRequest("SELECT * FROM widthdraw_history_krypto WHERE id_widthdraw_history=:id_widthdraw_history AND status_widthdraw_history=:status_widthdraw_history",
                                [
                                  'id_widthdraw_history' => $request,
                                  'status_widthdraw_history' => 1
                                ]);
    if(count($r) == 0) throw new Exception("Permission denied", 1);

    $rv = parent::execSqlRequest("UPDATE widthdraw_history_krypto SET status_widthdraw_history=:status_widthdraw_history WHERE id_widthdraw_history=:id_widthdraw_history",
                                [
                                  'id_widthdraw_history' => $request,
                                  'status_widthdraw_history' => 2
                                ]);

    if(!$rv) throw new Exception("Error : Fail to change status", 1);

    $template = new Liquid\Template();
    $template->parse(file_get_contents(APP_URL.'/app/modules/kr-user/templates/processWidthdraw.tpl'));

    $UserWithdraw = new User($r[0]['id_user']);

    // Render & send email
    $this->_getApp()->_sendMail($this->_getUser()->_getEmail(), $this->_getApp()->_getAppTitle().' - Your withdraw as been processed', $template->render([
      'APP_URL' => APP_URL,
      'APP_TITLE' => $this->_getApp()->_getAppTitle(),
      'SUBJECT' => 'Your withdraw as been processed',
      'USER_NAME' => $UserWithdraw->_getName(),
      'AMOUNT' => $this->_getApp()->_formatNumber($r[0]['amount_widthdraw_history'], 2).' $',
      'PAYPAL_EMAIL' => $r[0]['paypal_widthdraw_history'],
      'DATE' => date('d/m/Y H:i:s', time())
    ]));

    return true;


  }

}



?>
