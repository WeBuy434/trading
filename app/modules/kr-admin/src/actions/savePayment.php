<?php

/**
 * Change payment settings
 *
 * This actions permit to admin to change payment settings in Krypto
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
        throw new Exception("Your are not logged", 1);
    }
    if (!$User->_isAdmin()) {
        throw new Exception("Error : Permission denied", 1);
    }

    if($App->_isDemoMode()) throw new Exception("App currently in demo mode", 1);

    // Check data available
    if (empty($_POST)) {
        throw new Exception("Error : Args not valid", 1);
    }

    // Check if stripe key was changed
    if ($_POST['kr-adm-stripekey'] != '*********************' && !empty($_POST['kr-adm-stripekey'])) {

        // Check stripe key
        \Stripe\Stripe::setApiKey($_POST['kr-adm-stripekey']);
        \Stripe\Balance::retrieve();
    }

    // Save payment in Krypto configuration
    $App->_savePayment([
      'creditcard_enabled' => isset($_POST['kr-adm-chk-creditcard']) && $_POST['kr-adm-chk-creditcard'] == "on", // Credit card enable
      "paypal_enabled" => isset($_POST['kr-adm-chk-enablepaypal']) && $_POST['kr-adm-chk-enablepaypal'] == "on", // Paypal payment enable

      // Save Stripe encrypted private key
      "stripe_privatekey" => ($_POST['kr-adm-stripekey'] != '*********************' ? App::encrypt_decrypt('encrypt', $_POST['kr-adm-stripekey']) : $_POST['kr-adm-stripekey']),

      // Save paypal encrypted client id
      "paypal_clientid" => ($_POST['kr-adm-paypalclientid'] != '*********************' ? App::encrypt_decrypt('encrypt', $_POST['kr-adm-paypalclientid']) : $_POST['kr-adm-paypalclientid']),

      // Save paypal encrypted secret
      "paypal_secret" => ($_POST['kr-adm-paypalclientsecret'] != '*********************' ? App::encrypt_decrypt('encrypt', $_POST['kr-adm-paypalclientsecret']) : $_POST['kr-adm-paypalclientsecret']),

      "payment_success" => $_POST['kr-adm-paymentdoneresult'],
      "paypal_live" => isset($_POST['kr-adm-chk-enablepaypallive']) && $_POST['kr-adm-chk-enablepaypallive'] == "on", // Paypal live mode
      "fortumo_service" => ($_POST['kr-adm-fortumoservicekey'] != '*********************' ? App::encrypt_decrypt('encrypt', $_POST['kr-adm-fortumoservicekey']) : $_POST['kr-adm-fortumoservicekey']),
      "fortumo_secret" => ($_POST['kr-adm-fortumosecretkey'] != '*********************' ? App::encrypt_decrypt('encrypt', $_POST['kr-adm-fortumosecretkey']) : $_POST['kr-adm-fortumosecretkey']),
      "fortumo_enabled" => isset($_POST['kr-adm-chk-enablefortumo']) && $_POST['kr-adm-chk-enablefortumo'] == "on",
      "coingate_enabled" => isset($_POST['kr-adm-chk-enablecoingate']) && $_POST['kr-adm-chk-enablecoingate'] == "on",
      "coingate_live_mode" => isset($_POST['kr-adm-chk-coingatelivemode']) && $_POST['kr-adm-chk-coingatelivemode'] == "on",
      "coingate_app_id" => ($_POST['kr-adm-coingateappid'] != '*********************' ? App::encrypt_decrypt('encrypt', $_POST['kr-adm-coingateappid']) : $_POST['kr-adm-coingateappid']),
      "coingate_api_key" => ($_POST['kr-adm-coingateapikey'] != '*********************' ? App::encrypt_decrypt('encrypt', $_POST['kr-adm-coingateapikey']) : $_POST['kr-adm-coingateapikey']),
      "coingate_api_secret" => ($_POST['kr-adm-coingateapisecret'] != '*********************' ? App::encrypt_decrypt('encrypt', $_POST['kr-adm-coingateapisecret']) : $_POST['kr-adm-coingateapisecret']),
      "mollie_enabled" => isset($_POST['kr-adm-chk-enablemollie']) && $_POST['kr-adm-chk-enablemollie'] == "on",
      "mollie_key" => ($_POST['kr-adm-molliekey'] != '*********************' ? App::encrypt_decrypt('encrypt', $_POST['kr-adm-molliekey']) : $_POST['kr-adm-molliekey'])
    ]);

    // Return success message
    die(json_encode([
      'error' => 0,
      'msg' => 'Done',
      'title' => 'Success'
    ]));

} catch (\Exception $e) { // If throw exception, return error message
    die(json_encode([
    'error' => 1,
    'msg' => $e->getMessage()
  ]));
}
