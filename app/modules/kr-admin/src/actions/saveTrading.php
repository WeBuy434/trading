<?php

/**
 * Save trading settings
 *
 * This actions permit to admin to add an plan to krypto
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

// Load app modules
$App = new App(true);
$App->_loadModulesControllers();

try {

    // Check loggin & permission
    $User = new User();
    if (!$User->_isLogged()) {
        throw new Exception("Your are not logged");
    }
    if (!$User->_isAdmin()) {
        throw new Exception("Error : Permission denied");
    }

    if($App->_isDemoMode()) throw new Exception("App currently in demo mode", 1);


    $TradingCredentials = $App->_hiddenThirdpartyServiceCfg();

    $thirdPartyLogin = [
      'real' => [
        0 => ($_POST['kr-adm-gdaxapikeyreal'] == '**************' ? $TradingCredentials['real'][0] : App::encrypt_decrypt('encrypt', $_POST['kr-adm-gdaxapikeyreal'])),
        1 => ($_POST['kr-adm-gdaxapipassreal'] == '**************' ? $TradingCredentials['real'][1] : App::encrypt_decrypt('encrypt', $_POST['kr-adm-gdaxapipassreal'])),
        2 => ($_POST['kr-adm-gdaxapisecretreal'] == '**************' ? $TradingCredentials['real'][2] : App::encrypt_decrypt('encrypt', $_POST['kr-adm-gdaxapisecretreal'])),
        3 => (array_key_exists('kr-adm-chk-gdaxapirealsandbox', $_POST) && $_POST['kr-adm-chk-gdaxapirealsandbox'] == "on" ? 0 : 1)
      ],
      'practice' => [
        0 => ($_POST['kr-adm-gdaxapikeypractice'] == '**************' ? $TradingCredentials['practice'][0] : App::encrypt_decrypt('encrypt', $_POST['kr-adm-gdaxapikeypractice'])),
        1 => ($_POST['kr-adm-gdaxapipasspractice'] == '**************' ? $TradingCredentials['practice'][1] : App::encrypt_decrypt('encrypt', $_POST['kr-adm-gdaxapipasspractice'])),
        2 => ($_POST['kr-adm-gdaxapisecretpractice'] == '**************' ? $TradingCredentials['practice'][2] : App::encrypt_decrypt('encrypt', $_POST['kr-adm-gdaxapisecretpractice'])),
        3 => 0
      ]
    ];

    $App->_saveTrading(
        (array_key_exists('kr-adm-chk-enablenativetrading', $_POST) && $_POST['kr-adm-chk-enablenativetrading'] == "on" ? 1 : 0),
        json_encode($thirdPartyLogin),
        $_POST['kr-adm-depositfees'],
        $_POST['kr-adm-depositminimum'],
        $_POST['kr-adm-depositmaximum'],
        $_POST['kr-adm-widthdrawmin'],
        $_POST['kr-adm-widthdrawdays'],
        $_POST['kr-adm-tradingfees'],
        (array_key_exists('kr-adm-chk-enablerealaccount', $_POST) && $_POST['kr-adm-chk-enablerealaccount'] == "on" ? 1 : 0),
        $_POST['kr-adm-maximumfreedeposit']);

    $App->_saveReferal((array_key_exists('kr-adm-chk-enablereferal', $_POST) && $_POST['kr-adm-chk-enablereferal'] == "on" ? 1 : 0),
                       $_POST['kr-adm-referalcomission']);

    die(json_encode([
      'error' => 0,
      'msg' => 'Done',
      'title' => 'Success'
    ]));


    // var_dump(App::encrypt_decrypt('decrypt', 'YnVNd05pRzVTRUpLeFBIWjd6bm9hb05ER1laNUwwREdlMkJudEZVZ1h5MjcyQkdiVHRmc2xJM1RDbVdkOGdBRw=='));
    // var_dump(App::encrypt_decrypt('decrypt', 'WllBL2NGQXdzK0JnWWJxV2NVZG83Zz09'));
    // var_dump(App::encrypt_decrypt('decrypt', 'VnAwMVZRYlVhTDRHNUlsY2dGckNDNFFhTmk3QnNGSXJWQ1lheTRNMWVmNWs3V3k3aktZNVhjQTV6U0QzUnN1VGRaRzRxWHNDcGkwUWNkMitHRVVFdFZ0dTR2amc2cDJTVGoxTFBGeHdrUEszNHAxdFBqeXFHS285akxVSyttVmw='));

    // // Return success message
    // die(json_encode([
    //   'error' => 0,
    //   'msg' => 'Done',
    //   'title' => 'Success'
    // ]));

} catch (\Exception $e) { // If throw exception, return error message
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}

?>
