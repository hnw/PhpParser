<?php

require_once(dirname(__FILE__).'/EventDispatcherHolder.php');
require_once(dirname(__FILE__).'/../vendor/symfony/EventDispatcher/sfEvent.php');

class PhpSyntaxNode
{
  public $zendToken = null;
  public $beginLine;
  public $endLine;
  public $children;
  public $tokenName;

  /**
   * Leafに対して渡したいもの：
   *   ZendTokenのインスタンス
   *   開始行
   * not-Leafに対して渡したいもの
   *   このノードの呼び名
   *   子供のNodeたち
   */
  function __construct($token = '', $children = null, $current_line  = 0)
  {
    if ($children === null) {
      // 木構造でいうleaf
      $zend_token = $token;
      $this->zendToken = $zend_token;
      $this->endLine = $current_line;
      $this->beginLine = $current_line - $this->zendToken->getNewLines();
      $this->children = array();
      $token_name = $zend_token->tokenValue;
    } elseif (is_array($children)) {
      // 木構造でいうleaf以外
      if ($children !== array()) {
        $first_child = $children[0];
        $last_child = $children[sizeof($children)-1];
        $this->zendToken = null;
        $this->beginLine = $first_child->beginLine;
        $this->endLine = $first_child->endLine;
        $this->children = $children;
      }
      $token_name = $token;
    } else {
    }
    $this->tokenName = $token_name;

    $ev = new sfEvent($this, $token_name);
    EventDispatcherHolder::getDispatcher()->notify($ev);
    $ev_all = new sfEvent($this, 'ALL_NODE');
    EventDispatcherHolder::getDispatcher()->notify($ev_all);
  }
}