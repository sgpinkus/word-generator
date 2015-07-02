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
$n = [];

try {
  $n = Expression::build(new StringIterator($argv[1]));
}
catch(EmptyExpressionException $e) {
  fprintf(STDERR, "WARNING: Empty Expression\n");
}

foreach($n as $v) {
  print $v . "\n";
}

function usage() {
  fprintf(STDERR, "Usage: generator.php <expression>\n");
}
