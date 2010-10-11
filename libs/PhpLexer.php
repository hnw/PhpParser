<?php

require_once(dirname(__FILE__).'/PhpParser.php');
require_once(dirname(__FILE__).'/PhpSyntaxNode.php');
require_once(dirname(__FILE__).'/ZendToken.php');

class PhpLexer
{
  protected $phpSource = null; // PHPソースコードを表す文字列
  protected $alreadyParsed = false;
  protected $lineNumber = 1;
  protected $tokens = array();

  protected $ignoredTokenName =
    array('T_CLOSE_TAG', 'T_COMMENT', 'T_DOC_COMMENT', 'T_DOUBLE_COLON',
          'T_INLINE_HTML', 'T_OPEN_TAG', 'T_WHITESPACE');
  protected $ignoredZendTokenValue = array();

  public function __construct()
  {
    $this->buildIgnoredZendTokenValue();
  }

  public function buildIgnoredZendTokenValue()
  {
    foreach ($this->ignoredTokenName as $token_name) {
      if (defined($token_name)) {
        $this->ignoredZendTokenValue[constant($token_name)] = true;
      }
    }
  }

  /*
   * token_get_all関数が返すPHPのトークン値を、kmyaccのトークン値にして返す
   *
   * @see http://php.net/manual/ja/tokens.php
   */
  private static function getKmyaccTokenValueFromZendTokenValue($zend_token_value)
  {
    return constant($zend_token_value == T_DOUBLE_COLON ?
                    'PhpParser::TT_PAAMAYIM_NEKUDOTAYIM' :
                    'PhpParser::T'.token_name($zend_token_value));
  }

  public function getLineNumber()
  {
    if (!$this->alreadyParsed) {
      return 0;
    }
    return $this->lineNumber;
  }

  public function setPHPSource($php_source)
  {
    $this->phpSource = $php_source;
    $this->alreadyParsed = false;
  }
  private function tokenizePHP($force_tokenize = false)
  {
    if ($force_tokenize ||
        (!$this->alreadyParsed && $this->phpSource !== null)) {
      $this->tokens = token_get_all($this->phpSource);
      $this->alreadyParsed = true;
      return true;
    } else {
      return false;
    }
  }
  public function getNextValidZendToken()
  {
    $zend_token = $this->getNextZendToken();
    if ($zend_token !== null) {
      if (isset($this->ignoredZendTokenValue[$zend_token->tokenValue])) {
        return $this->getNextValidZendToken();
      }
    }
    return $zend_token;
  }

  public function getNextZendToken()
  {
    if ($this->tokens === array()) {
      $this->tokenizePHP();
    }
    if ($this->tokens === array()) {
      return null;
    }
    $zend_raw_token = array_shift($this->tokens);
    if ($zend_raw_token === false) {
      return null;
    }
    $zend_token = new ZendToken($zend_raw_token);
    $num_newline = $zend_token->getNewLines();
    if ($num_newline) {
      $this->lineNumber += $num_newline;
    }
    return $zend_token;
  }

  public function createPhpSyntaxNode($zend_token)
  {
    $node = new PhpSyntaxNode($zend_token, null, $this->getLineNumber());
    return $node;
  }

  public function getKmyaccTokenValue($zend_token)
  {
    $zend_token_value = $zend_token->tokenValue;
    if (is_integer($zend_token_value)) {
      return $this->getKmyaccTokenValueFromZendTokenValue($zend_token_value);
    } elseif (is_string($zend_token_value)) {
      return ord($zend_token_value);
    } else {
      return null;
    }
  }

  public function yylex(&$yylval)
  {
    $zend_token = $this->getNextValidZendToken();
    if ($zend_token === null) {
      $yylval = null;
      $kmyacc_token_value = 0;
    } else {
      $yylval = $this->createPhpSyntaxNode($zend_token);
      $kmyacc_token_value = $this->getKmyaccTokenValue($zend_token);
    }
    return $kmyacc_token_value;
  }

}