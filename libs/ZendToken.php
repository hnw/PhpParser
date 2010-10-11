<?php
/**
 * Zendエンジンのtoken名・token idを管理するクラス
 *
 */

class ZendToken
{
  public $phpPiece;
  public $tokenValue;

  public function __construct($zend_raw_token)
  {
    if (is_array($zend_raw_token)) {
      $this->phpPiece = $zend_raw_token[1];
      $this->tokenValue = $zend_raw_token[0];
    } else {
      $this->phpPiece = $zend_raw_token;
      $this->tokenValue = $zend_raw_token;
    }
    if (is_integer($this->tokenValue)) {
      $this->tokenName = token_name($this->tokenValue);
    } else {
      $this->tokenName = $this->tokenValue;
    }
  }
  public function getNewLines()
  {
    return substr_count($this->phpPiece, "\n");
  }
}