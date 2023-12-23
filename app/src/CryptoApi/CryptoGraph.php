<?php
/**
 * CryptoGraph class
 *
 * @package Krypto
 * @author Ovrley <hello@ovrley.com>
 */
class CryptoGraph {

    /**
     * Graph data
     * @var Array
     */
    private $data = null;

    /**
     * CryptoGraph constructor
     * @param Array $data CryptoGraph data, given by CryptoCoin
     */
    public function __construct($data){
        $this->data = $data;
    }

    /**
     * Get CryptoGraph data
     * @return Array CryptoGraph data
     */
    private function _getData(){
        if (is_null($this->data)) { // Check if data is null
            throw new Exception("Error : Data is null in CryptoGraph", 1);
        }
        return $this->data;
    }

    /**
     * Get candles with given data
     * @return Array Candles associate to CryptoHisto
     */
    public function _getCandles(){

        $res = [];
        foreach ($this->_getData() as $timestamp => $CryptoHisto) {
            $res[] = [
            'date' => date('d/m/Y H:i:s', $timestamp),
            'open' => $CryptoHisto->_getOpen(),
            'close' => $CryptoHisto->_getClose(),
            'low' => $CryptoHisto->_getLow(),
            'high' => $CryptoHisto->_getHigh(),
            'value' => $CryptoHisto->_getValueto(),
            'volume' => $CryptoHisto->_getValueto()
          ];
        }
        return $res;
    }
}

?>
