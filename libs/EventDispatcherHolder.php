<?php

require_once(dirname(__FILE__).'/../vendor/symfony/EventDispatcher/sfEventDispatcher.php');

class EventDispatcherHolder {
  private static $dispatcher = null;
  static function getDispatcher()
  {
    if (!self::$dispatcher) {
      self::$dispatcher=new sfEventDispatcher();
    }
    return self::$dispatcher;
  }
}
