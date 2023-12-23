<?php

/**
 * Admin class
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */
class Admin extends MySQL {

  public function __construct(){ }

  /**
   * Get number visit on the app since an starting date
   * @param  String $from Timestamp
   * @return Array       Visit list
   */
  public function _getVisitNum($from){
    return parent::querySqlRequest("SELECT * FROM visits_krypto WHERE time_visits > :time_visits", ['time_visits' => $from]);
  }

  /**
   * Get user list (order by the last user signup)
   * @return Array List of users
   */
  public function _getUsersList(){
    $listUser = [];
    foreach (parent::querySqlRequest("SELECT * FROM user_krypto ORDER BY id_user DESC", []) as $key => $dataUser) {
      $listUser[] = new User($dataUser['id_user']);
    }
    return $listUser;
  }

  /**
   * Get list coins
   * @return Array Coins list
   */
  public function _getListCoins(){
    return parent::querySqlRequest("SELECT * FROM coinlist_krypto ORDER BY id_coinlist", []);
  }

  /**
   * Get list admin section available
   * @return Array Admin section
   */
  public function _getListSection(){
    return ['Dashboard', 'Users', 'General settings', 'Coins', 'Currencies', 'Mail settings', 'Payment', 'Subscriptions', 'News - Social', 'Intro', 'Trading', 'Withdraw'];
  }

  /**
   * Get all list blocks stats on dashboard
   * @return Array Array blocks
   */
  public function _getListBlockStats(){

    $todayDate = new DateTime();
    $todayDate->setTime(0, 0, 0);
    $sevendayData = new DateTime();
    $sevendayData->sub(new DateInterval('P7D'));

    return [
      [
        "title" => "Today's visits",
        "value" => number_format(count($this->_getVisitNum($todayDate->getTimestamp())), 0, ',', ' ')
      ],
      [
        "title" => "7 days visits",
        "value" => number_format(count($this->_getVisitNum($sevendayData->getTimestamp())), 0, ',', ' ')
      ],
      [
        "title" => "Number of users",
        "value" => number_format(count($this->_getUsersList()), 0, ',', ' ')
      ],
      [
        "title" => "Number of coins",
        "value" => number_format(count($this->_getListCoins()), 0, ',', ' ')
      ]
    ];
  }

  public function _getIntroAvailable(){

    return [
      ".kr-wtchl left" => "Watching list",
      "[kr-module='dashboard'] left" => "Board",
      "[kr-side='kr-orderbook'] left" => "Order book",
      "[kr-module='marketanalysis'] left" => "Market",
      "[kr-module='blockfolio'] left" => "Blockfolio",
      "[kr-side='kr-leaderboard'] leftleft" => "Leader board",
      "[kr-side='kr-calculator'] left" => "Calculator",
      "[kr-side='kr-infosside'] left" => "News",
      ".kr-toggle-white top" => "Theme switch",
      ".kr-current-time top" => "Time",
      ".kr-wallet-top bottom" => "Account trading wallet",
      "[kr-action='kr-notification-center'] bottom" => "Notifications",
      ".kr-change-dashboard bottom" => "Dashboard manage",
      ".kr-addgraph-dashboard bottom" => "Add item to dashboard",
      ".kr-account bottom" => "Account profile",
      ".kr-live-dash-trade top" => "Market history",
      ".kr-chat-right right" => "Chat bar"
    ];

  }

  public function _getWithdrawList(){

    $res = [];
    foreach (parent::querySqlRequest("SELECT * FROM widthdraw_history_krypto ORDER BY id_widthdraw_history DESC") as $key => $value) {
      $itemWith = $value;
      $itemWith['user_details'] = new User($value['id_user']);
      $res[] = $itemWith;
    }

    return $res;


  }

}

?>
