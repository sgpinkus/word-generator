<?php

/**
 * Basic logging class.
 */
class Logger {
  const LOG_DEBUG = 0;
  const LOG_INFO = 1;
  const LOG_NOTICE = 2;
  const LOG_WARN = 3;
  const LOG_ERROR = 4;
  private $lvl  = self::LOG_INFO;

  private static $instance = null;


  private function __construct() {
  }

  public static function logger() {
    return self::$instance ? self::$instance : new Logger();
  }

  public function debug($s, $caller = null) {
    $caller = $caller ? $caller : debug_backtrace()[1];
    $this->log($s, Logger::LOG_DEBUG, $caller);
  }

  public function info($s, $caller = null) {
    $caller = $caller ? $caller : debug_backtrace()[1];
    $this->log($s, Logger::LOG_INFO, $caller);
  }

  public function notice($s, $caller = null) {
    $caller = $caller ? $caller : debug_backtrace()[1];
    $this->log($s, Logger::LOG_NOTICE, $caller);
  }

  public function warn($s, $caller = null) {
    $caller = $caller ? $caller : debug_backtrace()[1];
    $this->log($s, Logger::LOG_WARN, $caller);
  }

  public function error($s, $caller = null) {
    $caller = $caller ? $caller : debug_backtrace()[1];
    $this->log($s, Logger::LOG_ERROR, $caller);
  }

  public function format($s, $lvl, $file, $function, $line) {
    return sprintf("%s::%s::%s::%s: '%s'\n", $lvl, $file, $function, $line, $s);
  }

  public function log($s, $lvl, $caller = null)  {
    $caller = $caller ? $caller : debug_backtrace()[1];
    extract($caller);
    $s = $this->format($s, $lvl, $file, $function, $line);
    if($this->lvl <= $lvl) {
      print $s;
    }
  }
}

function debug($s) {
  Logger::logger()->debug($s, debug_backtrace()[1]);
}

function info($s) {
  Logger::logger()->debug($s, debug_backtrace()[1]);
}

function notice($s) {
  Logger::logger()->notice($s, debug_backtrace()[1]);
}

function warn($s) {
  Logger::logger()->warn($s, debug_backtrace()[1]);
}

function error($s) {
  Logger::logger()->error($s, debug_backtrace()[1]);
}

function var_dump_s($o) {
  ob_start();
  var_dump($o);
  return ob_get_clean();
}

/** TEST
function main() {
  debug("WWADS");
}
main();
*/
