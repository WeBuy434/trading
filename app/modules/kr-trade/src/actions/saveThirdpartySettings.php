<?php

/**
 * Save thirdparty settings
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

session_start();

require "../../../../../config/config.settings.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/vendor/autoload.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/MySQL/MySQL.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/App.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/App/AppModule.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/User/User.php";

require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoOrder.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoNotification.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoIndicators.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoGraph.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoHisto.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoCoin.php";
require $_SERVER['DOCUMENT_ROOT'].FILE_PATH."/app/src/CryptoApi/CryptoApi.php";

// Load app modules
$App = new App(true);
$App->_loadModulesControllers();

try {

    // Check if user is logged
    $User = new User();
    if (!$User->_isLogged()) {
        throw new Exception("Error : User is not logged", 1);
    }

    $Trade = new Trade($User, $App);

    if(empty($_POST) || !isset($_POST['thirdparty_name']) || empty($_POST['thirdparty_name'])) throw new Exception("Permission denied", 1);


    $exchangeName = App::encrypt_decrypt('decrypt', $_POST['thirdparty_name']);

    $Exchange = $Trade->_getExchange($exchangeName);
    if(is_null($Exchange)) throw new Exception("Error: Unable to find exchange", 1);

    $configFieldExchange = $Trade->_getThirdPartyConfig()[$exchangeName];

    $requestString = "id_user";
    $updateString = "";
    $requestArgsString = ":id_user";
    $requestArgs = ['id_user' => $User->_getUserID()];

    foreach ($configFieldExchange as $settingsKey => $value) {
      if($settingsKey == "sandbox" && is_null($value)) continue;
      if(!isset($_POST[$settingsKey])) throw new Exception("Error : Wrong format", 1);
      if($settingsKey == "sandbox" && !is_null($value)){
        $requestString .= ", ".$value;
        $requestArgsString .= ", :".$value;
        $requestArgs[$value] = $_POST[$settingsKey];
        if(!empty($updateString)) $updateString .= ", ";
        $updateString .= $value."=:".$value;
      } else {
        if(empty($_POST[$settingsKey])) die(json_encode([
          'error' => 2,
          'msg' => 'empty field'
        ]));
        $requestString .= ", ".$settingsKey;
        $requestArgsString .= ", :".$settingsKey;
        $requestArgs[$settingsKey] = App::encrypt_decrypt('encrypt', $_POST[$settingsKey]);
        if(!empty($updateString)) $updateString .= ", ";
        $updateString .= $settingsKey."=:".$settingsKey;
      }

    }

    $Trade->_saveThirdpartySettings($exchangeName, $requestString, $requestArgsString, $requestArgs, $updateString);

    die(json_encode([
      'error' => 0,
      'msg' => 'Done !'
    ]));

} catch (\Exception $e) {
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>
