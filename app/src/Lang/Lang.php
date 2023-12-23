<?php

/**
 * Lang Class
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */

class Lang {

  /**
   * Lang used
   *
   * @var String ISO Code
   */
  private $lang = null;

  /**
   * Translate file json decode
   *
   * @var Array Translate key & val
   */
  private $translate = null;

  /**
   * Application
   *
   * @var App
   */
  private $App = null;

  /**
   * Lang construct
   * @param String  $lang Lang used
   * @param App     $App  App object for get default language
   */
  public function __construct($lang = null, $App){
    $this->App = $App;
    if(is_null($lang)){
      if(isset($_COOKIE["krypto_lang"]) && !empty($_COOKIE["krypto_lang"])){
        if(Lang::languageAvailable($_COOKIE["krypto_lang"])) $lang = $_COOKIE["krypto_lang"];
        else $lang = $App->_getDefaultLanguage();
      } else {
        if($App->_getAutodectionLanguage() && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
          $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
          if(!Lang::languageAvailable($lang)) $lang = $App->_getDefaultLanguage();
        } else {
          $lang = $App->_getDefaultLanguage();
        }
      }
    }
    $this->setLang($lang);
  }

  /**
   * Get app object
   * @return App App object
   */
  private function _getApp(){
    if(is_null($this->App)) $this->App = new App(true);
    return $this->App;
  }

  /**
   * Set language
   * @param String $lang Language iso code name lowercase (ex : en | fr | de)
   */
  public function setLang($lang){

    // Check if custom language is available
    if(!is_null($lang) && file_exists($_SERVER['DOCUMENT_ROOT'].FILE_PATH."/public/lang/".$lang.".json")){
      $this->lang = $lang;
    } else { // Else, set default language
      $this->lang = $this->_getApp()->_getDefaultLanguage();
    }

    // Load languague data
    $this->loadLang();
  }

  /**
   * Get language used
   * @return String Language name used
   */
  public function getLang(){
    if(is_null($this->lang)) throw new Exception("Error : Lang is empty", 1);
    return $this->lang;
  }

  /**
   * Load language data
   */
  public function loadLang(){

    // Get file content language
    $this->translate = file_get_contents(APP_URL."/public/lang/".$this->getLang().".json");

    // If file can't be opened
    if(!$this->translate) {
      $this->translate = null;
    }
    else {
      // Parse language file
      $this->translate = json_decode($this->translate, true)['translate'];
      if(is_null($this->translate)) { // If fail to parse
        echo "Error in translate file : Can't be parsed (check coma etc), lang = ".$this->getLang();
      }
    }
  }

  /**
   * Translate function
   * @param  String $s Translate string
   * @return String    Translated string
   */
  public function tr($s){
    // If translate DB is numm
    if(is_null($this->translate)) return $s;
    // If translate key exist or result length > 0 ; return default translation
    if(array_key_exists($s, $this->translate) && strlen($this->translate[$s]) > 0) return $this->translate[$s];
    return $s;
  }

  /**
   * Check if language is available in files
   * @param  String $lang Lang file
   * @return Boolean
   */
  public static function languageAvailable($lang){
    return @file_get_contents(APP_URL."/public/lang/".strtolower($lang).".json");
  }

  /**
   * Get list language available
   * @param  String $file_path File path language directory
   * @return Array             Language list ['language_filename'] => ['language_name']
   */
  public static function getListLanguage($file_path = '../'){
    $listLanguage = [];

    // List all language available
    foreach (scandir($file_path.'public/lang/') as $langFile) {
      if($langFile == "." || $langFile == "..") continue;

      // Parse language
      $dataLang = json_decode(file_get_contents($file_path.'public/lang/'.$langFile), true);

      // Check error parse
      if(json_last_error() !== 0) continue;

      // Get file infos
      $fileInfos = pathinfo($file_path.'public/lang/'.$langFile);

      // Save in return
      $listLanguage[$fileInfos['filename']] = $dataLang['name'];
    }
    return $listLanguage;
  }

  /**
   * Define lang with cookie
   * @param String $lang ISO Lang code
   */
  public function setLangCookie($lang){
    setcookie("krypto_lang", $lang, time() + 3600);
    $this->setLang($lang);
  }

}

?>
