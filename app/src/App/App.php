<?php

/**
 * Main application class
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */
class App extends MySQL {

  /**
   * Module list available
   * @var Array Module Array
   */
  private $modulesList = [];

  /**
   * Settings data
   * @var Array List Krypto settings
   */
  private $settingsData = null;

  /**
   * Application constructor
   * @param boolean $loadmodules If load module or just access to config data
   */
  public function __construct($loadmodules = false){

    if(!defined('MYSQL_HOST') && file_exists('install')) header('Location: '.(defined('FILE_PATH') ? APP_URL : '').'/install/');

    // If loadmodule, load modules
    if($loadmodules) $this->_loadModules();

    // Load application settings in Database
    $this->_loadAppSettings();

  }

  public function _installDirectoryExist(){
    return file_exists('install');
  }

  /**
   * Load module function
   */
  public function _loadModules(){

    // Get list modules available in application
    foreach (scandir($_SERVER['DOCUMENT_ROOT'].FILE_PATH.'/app/modules') as $directory) {

      // Check if file is an file
      if($directory == "." || $directory == "..") continue;

      // Get directory path
      $directoryPath = $_SERVER['DOCUMENT_ROOT'].FILE_PATH.'/app/modules/'.$directory;

      // Check if file parsed is a directory (module need to be a directory)
      if(!is_dir($directoryPath)){

        // Save error in log file
        error_log('Fail to load module : '.$directory.' --> is not a directory');
        continue;
      }

      // Load module
      $ModuleLoad = new AppModule($directory);

      // Check module configuratino file
      if(!$ModuleLoad->_checkConfig()){

        // Save error in log file
        error_log('Fail to load module : '.$directory.' --> wrong configuration');
        continue;
      }

      // Check if module is enabled
      if($ModuleLoad->_isEnable()){
        // If enabled, save in module list
        $this->modulesList[$directory] = $ModuleLoad;
      }
    }
  }

  /**
   * Get assets list for all modules enabled
   * @param  string $typeAssets Type assets loaded (css, js)
   * @return String             Assets path
   */
  public function _getAssetsList($typeAssets = "css"){
    $res = "";
    // Get list modules
    foreach ($this->modulesList as $moduleObject) {
      // Load assets for current module
      foreach ($moduleObject->_loadAssets($typeAssets) as $asset) {
        $res .= $asset."\n\r"; // Add module assets in return data
      }
    }
    return $res;
  }

  /**
   * Load modules controllers
   */
  public function _loadModulesControllers(){

    // Get list modules
    foreach ($this->modulesList as $moduleObject) {
      // Get list modules controllers
      foreach ($moduleObject->_loadControllers() as $controlers) {
        // Require controllers class
        require $moduleObject->_getModulePath().'/src/'.$controlers;
      }
    }

  }

  /**
   * Load application settings from database
   */
  private function _loadAppSettings(){

    // Get list settings saved in database
    $r = parent::querySqlRequest("SELECT * FROM settings_krypto", []);

    // Reset all settings & set as an array
    $this->settingsData = [];

    // Get list settings
    foreach ($r as $key => $vSettings) {

      // If settings was en encrypted settings ==> decrypt
      if($vSettings['encrypted_settings'] == 1) $vSettings['value_settings'] = App::encrypt_decrypt('decrypt', $vSettings['value_settings']);

      // Save settings in object
      $this->settingsData[$vSettings['key_settings']] = $vSettings['value_settings'];
    }
  }

  /**
   * Change settings attribute
   * @param  String $key Settings key
   * @param  String $val Settings value
   */
  private function _saveSettingsAttribute($key, $val, $encrypt = false){
    if($encrypt) $val = App::encrypt_decrypt('encrypt', $val);
    $r = parent::execSqlRequest("UPDATE settings_krypto SET value_settings=:nval WHERE key_settings=:key_settings", ['nval' => $val, 'key_settings' => $key]);
    if(!$r) throw new Exception("Error : Fail to update settings key : ".$key, 1);
    return true;
  }

  /**
   * Get settings attribute from saved
   * @param  String $key Settings key needed
   * @return String      Settings value
   */
  private function _getSettingsAttribute($key){
    // If is null or not exist, return null
    if(is_null($this->settingsData) || !array_key_exists($key, $this->settingsData)) return null;

    // Return associate value
    return $this->settingsData[$key];
  }

  /**
   * Get if app allow signup
   * @return Boolean
   */
  public function _allowSignup(){ return $this->_getSettingsAttribute('allow_signup') == 1; }

  /**
   * Get if the app is in maintenance mode
   * @return Boolean
   */
  public function _isMaintenanceMode(){ return $this->_getSettingsAttribute('maintenance_mode') == 1; }

  /**
   * Get support email
   * @return String Support email
   */
  public function _getSupportEmail(){ return $this->_getSettingsAttribute('support_email'); }

  public function _getSupportPhone(){ return $this->_getSettingsAttribute('support_phone'); }

  public function _getSupportAddress(){ return $this->_getSettingsAttribute('support_address'); }

  public function _getDPOEmail(){ return $this->_getSettingsAttribute('dpo_email'); }

  public function _getDPOPhone(){ return $this->_getSettingsAttribute('dpo_phone'); }

  /**
   * Get if app enable google authentification
   * @return Boolean
   */
  public function _enableGooglOauth(){ return $this->_getSettingsAttribute('google_oauth') == 1; }

  public function _enableFacebookOauth(){ return $this->_getSettingsAttribute('facebook_oauth') == 1; }

  public function _getFacebookAppID(){ return $this->_getSettingsAttribute('facebook_appid'); }
  public function _getFacebookAppSecret(){ return $this->_getSettingsAttribute('facebook_appsecret'); }

  /**
   * Get app title
   * @return String Application title
   */
  public function _getAppTitle(){ return $this->_getSettingsAttribute('title_app'); }

  /**
   * Get app description
   * @return String Application description
   */
  public function _getAppDescription(){ return $this->_getSettingsAttribute('description_app'); }

  /**
   * Get google analytic code
   * @return String Google analytic
   */
  public function _getGoogleAnalytics(){ return $this->_getSettingsAttribute('google_analytic'); }

  /**
   * Get number format
   * @return String Number format
   */
  public function _getNumberFormat(){ return $this->_getSettingsAttribute('number_format'); }

  /**
   * Get if smtp is enabled
   * @return Boolean
   */
  public function _smtpEnabled(){ return $this->_getSettingsAttribute('smtp_enabled') == 1; }

  /**
   * Get smtp server host
   * @return String Stmp server
   */
  public function _getSmtpServer(){ return $this->_getSettingsAttribute('smtp_server'); }

  /**
   * Get smtp user
   * @return String Smtp user
   */
  public function _getSmtpUser(){ return $this->_getSettingsAttribute('smtp_user'); }

  /**
   * Get smtp password
   * @return String Smtp password
   */
  public function _getSmtpPassword(){ return $this->_getSettingsAttribute('smtp_password'); }

  /**
   * Get smtp port
   * @return String smtp port
   */
  public function _getSmtpPort(){ return $this->_getSettingsAttribute('smtp_port'); }

  /**
   * Get smtp security
   * @return String smtp security
   */
  public function _getSmtpSecurity(){
    $security = $this->_getSettingsAttribute('smtp_security');
    if($security != "0" && $security != "tls" && $security != "ssl") return "0";
    return $security;
  }

  /**
   * Get smtp from name
   * @return String Smtp from name
   */
  public function _getSmtpFrom(){ return $this->_getSettingsAttribute('smtp_from'); }

  /**
   * Get if app enable free trial
   * @return Boolean
   */
  public function _freetrialEnabled(){ return intval($this->_getSettingsAttribute('freetrial_enabled')) == 1; }

  /**
   * Get number free trial day
   * @return Int Number day free trial
   */
  public function _getChargeTrialDay(){ return intval($this->_getSettingsAttribute('charge_trial_nbdays')); }

  /**
   * Get if app allow credit card payment
   * @return Boolean
   */
  public function _creditCardEnabled(){
    if(is_null($this->_getPrivateStripeKey()) || empty($this->_getPrivateStripeKey())) return false;
    return intval($this->_getSettingsAttribute('creditcard_enabled')) == 1;
  }

  /**
   * Get if app enabled subscription
   * @return Boolean
   */
  public function _subscriptionEnabled(){ return intval($this->_getSettingsAttribute('subscription_enabled')) == 1; }

  /**
   * Get app premium name
   * @return String premium name
   */
  public function _getPremiumName(){ return $this->_getSettingsAttribute('premium_name'); }

  /**
   * Get app charge currency
   * @return String Charge currency (ex : USD)
   */
  public function _getChargeCurrency(){ return ($this->_getSettingsAttribute('charge_currency') == null ? 'USD' : $this->_getSettingsAttribute('charge_currency')); }

  /**
   * Get app charge currency symbol
   * @return String Charge currency symbol (ex : $)
   */
  public function _getChargeCurrencySymbol(){

    // Search currnecy in database
    $r = parent::querySqlRequest("SELECT * FROM currency_krypto WHERE code_iso_currency=:code_iso_currency", ['code_iso_currency' => $this->_getChargeCurrency()]);

    // If not found return default symbol : $
    if(count($r) == 0) return '$';

    // Return symbol currency
    return $r[0]['symbol_currency'];
  }

  /**
   * Get app charge text features
   * @return String Charge text features
   */
  public function _getChargeText(){ return $this->_getSettingsAttribute('premium_features'); }

  /**
   * Get app payment successfull text
   * @return String payment success text
   */
  public function _getPaymentResultDone(){ return $this->_getSettingsAttribute('payment_success'); }

  /**
   * Get app private Stripe Key
   * @return String Stripe Private Key
   */
  public function _getPrivateStripeKey(){ return $this->_getSettingsAttribute('stripe_privatekey'); }

  /**
   * Get if paypal is enabled
   * @return Boolean
   */
  public function _paypalEnabled(){
    if(is_null($this->_getPaypalClientID()) || empty($this->_getPaypalClientID()) || is_null($this->_getPaypalClientSecret()) || empty($this->_getPaypalClientSecret())) return false;
    return intval($this->_getSettingsAttribute('paypal_enabled')) == 1;
  }

  /**
   * Get if paypal is enabled as live mode
   * @return Boolean
   */
  public function _paypalLiveModeEnabled(){
    return intval($this->_getSettingsAttribute('paypal_live')) == 1;
  }

  /**
   * Get app Paypal client ID
   * @return String Paypal client ID
   */
  public function _getPaypalClientID(){ return $this->_getSettingsAttribute('paypal_clientid'); }

  /**
   * Get app Paypal client Secret
   * @return String Paypal client Secret
   */
  public function _getPaypalClientSecret(){ return $this->_getSettingsAttribute('paypal_secret'); }

  /**
   * Get Fortumo secret key
   * @return String Secret key
   */
  public function _getFortumoSecretKey(){ return $this->_getSettingsAttribute('fortumo_secret'); }

  /**
   * Get Fortumo service key
   * @return String Service key
   */
  public function _getFortumoServiceKey(){ return $this->_getSettingsAttribute('fortumo_service'); }

  /**
   * Get if Fortumo is enabled
   * @return Boolean
   */
  public function _fortumoEnabled(){ return $this->_getSettingsAttribute('fortumo_enabled') == 1; }

  /**
   * Get if CoinGate is enabled
   * @return Boolean
   */
  public function _coingateEnabled(){ return $this->_getSettingsAttribute('coingate_enabled') == 1; }

  /**
   * Get if Coingate is on live mode
   * @return Boolean
   */
  public function _coingateLiveMode(){ return $this->_getSettingsAttribute('coingate_live_mode') == 1; }

  /**
   * Get Coingate app id
   * @return String
   */
  public function _getCoingateAppID(){ return $this->_getSettingsAttribute('coingate_app_id'); }

  /**
   * Get Coingate api secret
   * @return String
   */
  public function _getCoingateApiSecret(){ return $this->_getSettingsAttribute('coingate_api_secret'); }

  /**
   * Get Coingate api key
   * @return String
   */
  public function _getCoingateApiKey(){ return $this->_getSettingsAttribute('coingate_api_key'); }

  /**
   * Get if mollie is enabled
   * @return Boolean
   */
  public function _mollieEnabled(){ return $this->_getSettingsAttribute('mollie_enabled') == 1; }

  /**
   * Get Mollie key
   * @return String
   */
  public function _getMollieKey(){ return $this->_getSettingsAttribute('mollie_key'); }

  /**
   * Get default dashboard num
   * @return String default dashboard configuration
   */
  public function _getDefaultDashboardNum(){ return $this->_getSettingsAttribute('default_dashboard'); }

  /**
   * Get default language
   * @return String default language (ex : fr)
   */
  public function _getDefaultLanguage(){ return $this->_getSettingsAttribute('default_language'); }

  /**
   * Get google app id (for google oauth)
   * @return String Google App ID
   */
  public function _getGoogleAppID(){ return $this->_getSettingsAttribute('google_app_id'); }

  /**
   * Get google app secret (for google oauth)
   * @return String Google App Secret
   */
  public function _getGoogleAppSecret(){ return $this->_getSettingsAttribute('google_app_secret'); }

  /**
   * Get if app require captcha to signup
   * @return Boolean
   */
  public function _captchaSignup(){ return $this->_getSettingsAttribute('captcha_signup') == 1; }

  /**
   * Get google recaptcha site key
   * @return String Google recaptcha site key
   */
  public function _getGoogleRecaptchaSiteKey(){ return $this->_getSettingsAttribute('google_recaptcha_sitekey'); }

  /**
   * Get google recaptcha secret key
   * @return String Google recaptcha secret key
   */
  public function _getGoogleRecaptchaSecretKey(){ return $this->_getSettingsAttribute('google_recaptcha_secretkey'); }

  /**
   * Get if google ad is enabled
   * @return Boolean
   */
  public function _GoogleAdEnabled(){ return $this->_getSettingsAttribute('google_ad_enabled') == 1; }

  /**
   * Get Google ad client
   * @return String
   */
  public function _getGoogleAdClient(){ return $this->_getSettingsAttribute('google_ad_client'); }

  /**
   * Get Google ad slot
   * @return String
   */
  public function _getGoogleAdSlot(){ return $this->_getSettingsAttribute('google_ad_slot'); }


  /**
   * Get if app need to send welcome email
   * @return Boolean
   */
  public function _sendWelcomeEmail(){ return $this->_getSettingsAttribute('send_welcomeemail'); }

  /**
   * Get welcome subject
   * @return String Welcome subject
   */
  public function _getWelcomeSubject(){ return $this->_getSettingsAttribute('welcome_subject'); }

  /**
   * Get if language is autodetected
   * @return Boolean
   */
  public function _getAutodectionLanguage(){ return $this->_getSettingsAttribute('autodetect_language') == 1; }

  /**
   * Get number day when user is alerted for re-new their subscription
   * @return Int
   */
  public function _nbDaysSendMailWhenTrialSubsEnded(){ return intval($this->_getSettingsAttribute('nb_days_subscription_needed')); }

  public function _getNumberDaysWidthdrawProcess(){
    return $this->_getSettingsAttribute('widthdraw_processing_days');
  }

  public function _getMinimumWidthdraw(){
    return $this->_getSettingsAttribute('widthdraw_minimum');
  }

  public function _referalEnabled(){
    return intval($this->_getSettingsAttribute('referal_enable')) == 1 && $this->_hiddenThirdpartyActive();
  }

  public function _getReferalWinAmount(){
    return $this->_getSettingsAttribute('referall_win_amount');
  }

  public function _getWidthdrawFees(){
    return $this->_getSettingsAttribute('widthdraw_fees');
  }

  public function _getMinimalDeposit(){
    return $this->_getSettingsAttribute('deposit_minimal');
  }

  public function _getMaximalDeposit(){
    return $this->_getSettingsAttribute('deposit_maximal');
  }

  public function _getFeesDeposit(){
    return floatval($this->_getSettingsAttribute('deposit_fees'));
  }

  public function _getMaximalFreeDeposit(){
    return floatval($this->_getSettingsAttribute('trading_maximum_free_deposit'));
  }

  public function _getTradingEnableRealAccount(){
    return $this->_getSettingsAttribute('trading_enable_real_account') == 1;
  }

  public function _getIntroShow(){
    return $this->_getSettingsAttribute('intro_show') == 1;
  }

  public function _getIntroList(){
    return json_decode($this->_getSettingsAttribute('intro_list'), true);
  }

  public function _getNewsPopup(){
    return $this->_getSettingsAttribute('newspopup_show') == 1;
  }

  public function _getNewsPopupLastUpdate(){
    return $this->_getSettingsAttribute('newspopup_lastupdate');
  }

  public function _getNewsPopupVideo(){
    if(strlen($this->_getSettingsAttribute('newspopup_video')) == 0) return null;
    return $this->_getSettingsAttribute('newspopup_video');
  }

  public function _getNewsPopupTitle(){
    return $this->_getSettingsAttribute('newspopup_title');
  }

  public function _getNewsPopupText(){
    return $this->_getSettingsAttribute('newspopup_text');
  }

  /**
   * Get list features allowed free
   * @return Array
   */
  public function _getFeaturesAllowedFree(){
    $features = [];
    foreach (json_decode($this->_getSettingsAttribute('user_permissions'), true) as $feature => $val) {
      $features[$feature] = $val;
    }
    return $features;
  }

  /**
   * Get referal link
   * @return String
   */
  public function _getReferalLink(){
    return $this->_getSettingsAttribute('buy_referal');
  }

  /**
   * Get if app is in demo mode
   * @return Boolean
   */
  public function _isDemoMode(){
    return false;
  }

  /**
   * Get if user need to activate their account
   * @return Boolean
   */
  public function _getUserActivationRequire(){
    return $this->_getSettingsAttribute('user_activation_require') == 1;
  }

  public function _hiddenThirdpartyActive(){
    return $this->_getSettingsAttribute('hidden_third_trading') == 1;
  }

  public function _hiddenThirdpartyService(){
    return $this->_getSettingsAttribute('hidden_third_trading_service');
  }

  public function _hiddenThirdpartyTradingFee(){
    return floatval($this->_getSettingsAttribute('hidden_third_trading_fee'));
  }

  public function _hiddenThirdpartyDepositFee(){
    return floatval($this->_getSettingsAttribute('hidden_third_deposit_fee'));
  }

  public function _hiddenThirdpartyServiceCfg(){
    return json_decode($this->_getSettingsAttribute('hidden_third_trading_service_cfg'), true);
  }

  public function _getCalendarEnable(){
    return $this->_getSettingsAttribute('calendar_enable');
  }

  public function _getCalendarCientID(){
    return $this->_getSettingsAttribute('calendar_cliend_id');
  }

  public function _getCalendarClientSecret(){
    return $this->_getSettingsAttribute('calendar_client_secret');
  }

  public function _getCalendarEnableCoinsEnabled(){
    return $this->_getSettingsAttribute('calendar_enable_coin_enable');
  }

  public function _getExtraPageEnable(){
    return $this->_getSettingsAttribute('extra_page_enable') == '1';
  }

  public function _getExtraPageNewTab(){
    return $this->_getSettingsAttribute('extra_page_newtab');
  }

  public function _getExtraPageUrl(){
    return $this->_getSettingsAttribute('extra_page_url');
  }

  public function _getExtraPageName(){
    return $this->_getSettingsAttribute('extra_page_name');
  }

  public function _getExtraPageIcon(){
    return $this->_getSettingsAttribute('extra_page_icon');
  }

  public function _getCookieAvertEnable(){
    return $this->_getSettingsAttribute('cookie_advert_enable') == 1;
  }

  public function _getCookieTitle(){
    return $this->_getSettingsAttribute('cookie_title');
  }

  public function _getCookieText(){
    return $this->_getSettingsAttribute('cookie_text');
  }

  /**
   * Save SMTP Settings
   *
   * @param  Int $enable      Enable smtp (1 = enabled, 0 = disabled)
   * @param  String $server   SMTP Server
   * @param  String $port     SMTP Port
   * @param  String $user     SMTP User
   * @param  String $password SMTP Password
   */
  public function _saveSmtpSettings($enable, $server, $port, $user, $password, $security){
    $this->_saveSettingsAttribute('smtp_enabled', $enable);
    $this->_saveSettingsAttribute('smtp_server', $server);
    $this->_saveSettingsAttribute('smtp_port', $port);
    $this->_saveSettingsAttribute('smtp_user', $user);
    $this->_saveSettingsAttribute('smtp_password', $password, true);
    $this->_saveSettingsAttribute('smtp_security', $security);
  }

  /**
   * Save welcome mail settings
   *
   * @param  Int $enable     Enable welcome mail
   * @param  String $subject Mail subject
   */
  public function _saveWelcomeMailSettings($enable, $subject){
    $this->_saveSettingsAttribute('send_welcomeemail', $enable);
    $this->_saveSettingsAttribute('welcome_subject', $subject);
  }

  /**
   * Save support & dpo infos
   * @param  String $email Support email
   * @param  String $phone Support phone
   * @param  String $address Support address
   * @param  String $dpoemail DPO email
   * @param  String $dpophone DPO Phone
   */
  public function _saveSupport($email, $phone, $address, $dpoemail, $dpophone){
    $this->_saveSettingsAttribute('support_email', $email);
    $this->_saveSettingsAttribute('support_phone', $phone);
    $this->_saveSettingsAttribute('support_address', $address);
    $this->_saveSettingsAttribute('dpo_email', $dpoemail);
    $this->_saveSettingsAttribute('dpo_phone', $dpophone);
  }

  /**
   * Save email sender name
   * @param  String $email Sender name
   */
  public function _saveSenderEmailName($email){
    $this->_saveSettingsAttribute('smtp_from', $email);
  }


  /**
   * Save general settings
   * @param  String $apptitle          Application title
   * @param  String $appdescription    Application description
   * @param  String $enablesignup      Enable allow signup
   * @param  String $recaptcha_enabled Enable recaptcha signup page
   * @param  String $gogglesitekey     Google site key (for recaptcha)
   * @param  String $googlesecretkey   Google secret key (for recaptcha)
   * @param  String $enablegooglelogin Enable google signup / login
   * @param  String $googleappid       Google app id
   * @param  String $googleappsecret   Google app secret
   * @param  String $googleanalytics   Google analytics code
   * @param  String $defaultlanguage   Default language
   */
  public function _saveGeneralsettings($apptitle, $appdescription, $enablesignup, $recaptcha_enabled, $gogglesitekey,
                                      $googlesecretkey, $enablegooglelogin, $googleappid, $googleappsecret,
                                      $googleanalytics, $defaultlanguage, $googleadenabled, $googleadclient, $googleadslot, $referallink, $maintenancemode,
                                      $facebookenable, $facebookappid, $facebookappsecret, $autolanguage,
                                      $cookieenable, $cookietitle, $cookietext, $numberformart, $signupverify){
    $this->_saveSettingsAttribute('title_app', $apptitle);
    $this->_saveSettingsAttribute('description_app', $appdescription);
    $this->_saveSettingsAttribute('allow_signup', $enablesignup);
    $this->_saveSettingsAttribute('captcha_signup', $recaptcha_enabled);
    $this->_saveSettingsAttribute('google_recaptcha_sitekey', $gogglesitekey, true);
    $this->_saveSettingsAttribute('google_recaptcha_secretkey', $googlesecretkey, true);
    $this->_saveSettingsAttribute('google_oauth', $enablegooglelogin);
    $this->_saveSettingsAttribute('google_app_id', $googleappid, true);
    $this->_saveSettingsAttribute('google_app_secret', $googleappsecret, true);
    $this->_saveSettingsAttribute('google_analytic', $googleanalytics);
    $this->_saveSettingsAttribute('default_language', $defaultlanguage);
    $this->_saveSettingsAttribute('google_ad_enabled', $googleadenabled);
    $this->_saveSettingsAttribute('google_ad_client', $googleadclient);
    $this->_saveSettingsAttribute('google_ad_slot', $googleadslot);
    $this->_saveSettingsAttribute('buy_referal', $referallink);
    $this->_saveSettingsAttribute('maintenance_mode', $maintenancemode);
    $this->_saveSettingsAttribute('facebook_oauth', $facebookenable);
    $this->_saveSettingsAttribute('facebook_appid', $facebookappid, true);
    $this->_saveSettingsAttribute('facebook_appsecret', $facebookappsecret, true);
    $this->_saveSettingsAttribute('autodetect_language', $autolanguage);

    $this->_saveSettingsAttribute('cookie_advert_enable', $cookieenable);
    $this->_saveSettingsAttribute('cookie_title', $cookietitle);
    $this->_saveSettingsAttribute('cookie_text', $cookietext);

    $this->_saveSettingsAttribute('number_format', $numberformart);

    $this->_saveSettingsAttribute('user_activation_require', $signupverify);


  }

  /**
   * Save payment settings
   * @param  Array $args  List settings
   */
  public function _savePayment($args){
    foreach ($args as $attribute => $value) {
      $realValue = $value;
      if($realValue === true) $realValue = '1';
      if($realValue === false) $realValue = '0';
      if($realValue == "*********************") continue;
      $this->_saveSettingsAttribute($attribute, $realValue);
    }
  }

  /**
   * Save subscription
   * @param  Int $enable               Enable subscriptions (1 = enabled, 0 = disabled)
   * @param  Int $freetrial            Enable freetrial
   * @param  Int $freetrialduration    Free trial duration
   */
  public function _saveSubscription($enable, $freetrial, $freetrialduration, $features, $free_featues){
    $this->_saveSettingsAttribute('subscription_enabled', $enable);
    $this->_saveSettingsAttribute('freetrial_enabled', $freetrial);
    $this->_saveSettingsAttribute('charge_trial_nbdays', $freetrialduration);
    $this->_saveSettingsAttribute('premium_features', $features);
    $this->_saveSettingsAttribute('user_permissions', json_encode($free_featues));
  }

  public function _saveIntroSteps($enable, $steps){
    $this->_saveSettingsAttribute('intro_show', $enable);
    $this->_saveSettingsAttribute('intro_list', $steps);
  }
  public function _saveNewspopup($enable, $title, $video, $text, $advert = false){
    $this->_saveSettingsAttribute('newspopup_show', $enable);
    $this->_saveSettingsAttribute('newspopup_title', $title);
    $this->_saveSettingsAttribute('newspopup_video', $video);
    $this->_saveSettingsAttribute('newspopup_text', $text);
    if($advert) $this->_saveSettingsAttribute('newspopup_lastupdate', time());
  }

  public function _saveCalendarSettings($enable, $clientid, $clientsecret, $enable_coins){
    $this->_saveSettingsAttribute('calendar_enable', $enable);
    $this->_saveSettingsAttribute('calendar_cliend_id', $clientid, true);
    $this->_saveSettingsAttribute('calendar_client_secret', $clientsecret, true);
    $this->_saveSettingsAttribute('calendar_enable_coin_enable', $enable_coins);
  }

  public function _saveTrading($enable_native, $login, $deposit_fees, $deposit_min, $deposit_max, $withdraw_min, $withdraw_days, $trading_fees, $enable_realaccount, $maxfree_deposit){
    $this->_saveSettingsAttribute('hidden_third_trading', $enable_native);
    $this->_saveSettingsAttribute('deposit_fees', $deposit_fees);
    $this->_saveSettingsAttribute('deposit_minimal', $deposit_min);
    $this->_saveSettingsAttribute('deposit_maximal', $deposit_max);
    $this->_saveSettingsAttribute('widthdraw_minimum', $withdraw_min);
    $this->_saveSettingsAttribute('widthdraw_processing_days', $withdraw_days);
    $this->_saveSettingsAttribute('hidden_third_trading_fee', $trading_fees);
    $this->_saveSettingsAttribute('trading_enable_real_account', $enable_realaccount);
    $this->_saveSettingsAttribute('trading_maximum_free_deposit', $maxfree_deposit);
    $this->_saveSettingsAttribute('hidden_third_trading_service_cfg', $login);
  }

  public function _saveReferal($enable, $comission){
    $this->_saveSettingsAttribute('referal_enable', $enable);
    $this->_saveSettingsAttribute('referall_win_amount', $comission);
  }

  /**
   * Get list month name
   * @param Lang   Lang object
   * @return Array List month ordered
   */
  public function _getMonthName($Lang = null){
    if(is_null($Lang)) return ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $r = [];
    foreach ($this->_getMonthName() as $month) {
      $r[] = $Lang->tr($month);
    }
    return $r;
  }

  /**
   * Get list days name
   * @param  boolean $abrev Only get abreviation
   * @return Array          Days list orderded
   */
  public function _getDayName($abrev = false, $Lang = null){
    if(is_null($Lang)){
      if($abrev) return ['Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.', 'Sun.'];
      return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    }

    $r = [];
    foreach ($this->_getDayName($abrev) as $day) {
      $r[] = $Lang->tr($day);
    }
    return $r;

  }

  /**
   * Check domain application for redirection
   */
  public function _checkDomain(){
    $url = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://") . $_SERVER['HTTP_HOST'].$_SERVER['CONTEXT_PREFIX'];
    if(!empty($_GET) && isset($_GET['r']) && (time() - base64_decode($_GET['r'])) < 5 && APP_URL != $url && !APP_URL_FORCE) die('Application error looping, if you want force excecution, set [APP_URL_FORCE] => true in [config/config.settings.php] or change the url application [APP_URL] in [config/config.settings.php]');
    if(substr($url, -1) == '/' && $url != APP_URL) $url = substr($url, 0, -1);
    // var_dump($_SERVER);
    //die(APP_URL.' - '.$url);
    if(APP_URL != $url && !APP_URL_FORCE) header('Location: '.APP_URL.$_SERVER['PHP_SELF'].'?r='.base64_encode(time()));
  }

  /**
   * Encrypt / Decrypt data with key
   * @param  String $action Type (encrypt or decrypt)
   * @param  String $string Value to encrypt or decrypt
   * @return Stirng         Value decrypted / Encrypted
   */
  public static function encrypt_decrypt($action, $string) {

      $output = null;


      $encrypt_method = "AES-256-CBC"; // Crypt method
      $secret_key = CRYPTED_KEY; // Crypt key
      $secret_iv = strrev(CRYPTED_KEY);

      // Hash method to crypt key
      $key = hash('sha256', $secret_key);
      $iv = substr(hash('sha256', $secret_iv), 0, 16);

      // If encrypt
      if( $action == 'encrypt' ) {
        // Crypt string
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
      }
      else if( $action == 'decrypt' ) $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv); // Decrypt string

      return $output;
  }

  /**
   * Check error software need to be shown
   */
  public static function _checkError(){
    if(defined('ERROR_SOFTWARE')){
      echo '<section class="kr-msg kr-msg-error" style="display:block;padding:12px 20px;">'.ERROR_SOFTWARE.'</section>';
      die();
    }

  }

  /**
   * Send email
   * @param  String $to      To mail (ex : name@domain.tld)
   * @param  String $subject Mail subject
   * @param  String $content Mail content
   */
  public function _sendMail($to, $subject, $content){

    $mail = new PHPMailer\PHPMailer\PHPMailer;

    // Enable SMTP Method
    $mail->isSMTP();

    // Disable debug mode (set = 2 for debug)
    $mail->SMTPDebug = 0;

    // Set charset mail
    $mail->CharSet = 'UTF-8';

    // Set SMTP Settings
    $mail->Host = $this->_getSmtpServer();
    $mail->Port = $this->_getSmtpPort();

    // Defined SMTP Authentification require
    $mail->SMTPAuth = true;

    if($this->_getSmtpSecurity() != "0" && ($this->_getSmtpSecurity() == "ssl" || $this->_getSmtpSecurity() == "tls")){
      $mail->SMTPSecure = ($this->_getSmtpSecurity() == "0" ? false : $this->_getSmtpSecurity());
    }

    // Set SMTP User & Password
    $mail->Username = $this->_getSmtpUser();
    $mail->Password = $this->_getSmtpPassword();

    // Set SMTP From with email & from name
    $mail->setFrom($this->_getSmtpUser(), $this->_getSmtpFrom());

    // Set to email address
    $mail->addAddress($to);

    // Set subject
    $mail->Subject = $subject;

    // Set email content
    $mail->msgHTML($content);

    // Check if mail was sended
    if(!$mail->send()) error_log("Error : Fail to send email : ".$mail->ErrorInfo);
    return true;

  }

  /**
   * Symbol market thirdparty available
   */
  public function _syncThirdpartyMarket(){

    $BittrexClient = new BittrexClient('601a6c79356041fab100a2ab81376d84', 'ccb2eed098d9434b88d093c39fc22009');
    foreach ($BittrexClient->getMarkets() as $Market) {
      if($Market->IsActive){
        $r = parent::execSqlRequest("INSERT INTO thirdparty_crypto_krypto (symbol_thirdparty_crypto, to_thirdparty_crypto, name_thirdparty_crypto)
                                    VALUES (:symbol_thirdparty_crypto, :to_thirdparty_crypto, :name_thirdparty_crypto)",
                                    [
                                      'symbol_thirdparty_crypto' => $Market->MarketCurrency,
                                      'to_thirdparty_crypto' => $Market->BaseCurrency,
                                      'name_thirdparty_crypto' => 'bittrex'
                                    ]);
      }
    }

  }

  public function _formatNumber($number, $decimal = 2){
    $infosFormat = explode(':', str_replace('"', '', $this->_getNumberFormat()));
    return number_format($number, $decimal, $infosFormat[0], $infosFormat[1]);
  }

  public function _checkReferalSource(){
    if(!$this->_referalEnabled()) return false;
    if(!empty($_GET) && isset($_GET['ref']) && !empty($_GET['ref'])){
      $code = htmlspecialchars($_GET['ref']);
      $r = parent::querySqlRequest("SELECT * FROM referal_krypto WHERE code_referal=:code_referal", ['code_referal' => $code]);
      if(count($r) > 0){
        $_SESSION['referal_source_krypto'] = $code;
      }
    }
  }


}

?>
