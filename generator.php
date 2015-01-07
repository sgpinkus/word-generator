<?php
/**
 * This script provides a language generator for finite languages specified with a limited regular exp language.
 * See Symbols.php for language definition.
 */
ini_set('include_path', ini_get('include_path') . ':./include/');
require_once 'StringIterator.php';
require_once 'Symbols.php';

if($argc != 2) {
  usage();
  exit(1);
}
$n = Expression::build(new StringIterator($argv[1]));

foreach($n as $v) {
  print $v . "\n";
}

function usage() {
  print "Usage: generator.php <expression>\n";
}

