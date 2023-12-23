<?php

/**
 * Leader board view
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

session_start();

require "../../../../config/config.settings.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/MySQL/MySQL.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/App.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/AppModule.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/User/User.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/Lang/Lang.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoApi.php";

// Load app modules
$App = new App(true);
$App->_loadModulesControllers();

// Check if user is logged
$User = new User();
if(!$User->_isLogged()) die('User not logged');

$Trade = new Trade($User, $App);
$UserLeaderBoard = $Trade->_getLeaderBoardUser($User);
?>

<section class="kr-rankingside-mine">
  <div class="">
    <div style="background-image:url('<?php echo APP_URL; ?>/assets/img/icons/country/<?php echo strtolower($User->_getUserLocation(true)); ?>.png')"></div>
    <label><?php echo number_format($UserLeaderBoard['rank'], 0, ' ', ' '); ?></label>
    <span>Week profit <i><?php echo $App->_formatNumber($UserLeaderBoard['benef_leader_board'], 0); ?> $</i></span>
  </div>
</section>
<ul>
  <?php

  foreach (array_slice($Trade->_getLeaderBoard(), 0, 100) as $RankingUser) {  ?>
  <li>
    <div class="kr-rankingside-urnk"><?php echo $RankingUser['rank']; ?></div>
    <div class="kr-rankingside-infs">
      <div style="background-image:url('<?php echo APP_URL; ?>/assets/img/icons/country/<?php echo strtolower($RankingUser['country']); ?>.png')"></div>
      <span><?php echo $RankingUser['name']; ?></span>
    </div>
    <div class="kr-rankingside-tbnf">
      <span><?php echo $App->_formatNumber($RankingUser['benefic'], 0); ?> $</span>
    </div>
  </li>
  <?php } ?>
</ul>
