<?php
/** 
 * Syntax/Generator tree classes and supporting clases. Language definition:
 *  expression  -> ( ( literal | alt | group | class | escape ) [ repeatition ] )*
 *  alternation -> "[" literal | class | escape "]"
 *  repeatition -> "{" alpha [ "," alpha ] "}"
 *  group       -> "(" expression ")" 
 *  special     -> "{" "}" "[" "]" "(" ")" "\"
 *  escape      -> "\" special
 *  class       -> "\" ( "w" | "d" )
 *  literal     -> ...
 *  alpha       -> ...
 *
 * @see Expression.
 */
require_once 'Logger.php';
require_once 'SidewaysIterator.php';

class ParseException extends Exception {
}

/**
 * Abstract base class for all symbols.
 * Static build() method is responsible for deserializing an string expression into a symbols tree.
 * Symbols must be Traversable. Iterator is expected to iterate over all substring in given subtree.
 */
abstract class Symbol implements IteratorAggregate {
  abstract public static function build(StringIterator $p);
}

/**
 * Static container for shared vocab.
 */
class Characters {
  public static $alpha = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '_', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
  public static $digit = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
  public static $special = ['\\','[',']','{','}','(',')'];
}

/**
 *
 */
class Expression extends Symbol implements IteratorAggregate {
  private $childs = [];
  
  public function __construct($childs) {
    debug(__METHOD__ . print_r($childs, true));
    $this->childs = $childs;
  }
  
  public function getIterator() {
    return new SidewaysIterator($this->childs, function($n){return join("", $n);});
  }
  
  public static function build(StringIterator $p) {
    $nodes  = [];
    $childSymbols = [
      "Literal",
      "CharacterClass",
      "EscapeClass",
      "Alternation",
      "GroupExpression",
    ];
    
    while($p->valid()) {
      $hit = false;
      $mark = $p->pos();
      foreach($childSymbols as $symbol) {
        try {
          debug("Try $symbol {$p->current()}");
          $node = $symbol::build($p);
          $mark = $p->pos();
          debug("\tHit");
          try {
            $node = Repeatition::build($p, $node);
          }
          catch(ParseException $e) {
            $p->seek($mark);
          }
          $hit = true;
          $nodes[] = $node;
          break;
        }
        catch(ParseException $e) {
          $p->seek($mark);
          continue;
        }
      }
      if(!$hit) {
        break;
      }
    }
    return new Expression($nodes);
  }
}


/**
 * 
 */
class GroupExpression extends Symbol implements IteratorAggregate {
  private $child = null;
  
  public function __construct(Expression $child) {
    debug(__METHOD__);
    $this->child = $child;
  }
  
  public function getIterator() {
    return $this->child->getIterator();
  }

  public static function build(StringIterator $p) {    
    $child = null;
    if($p->current() != "(") {
      throw new ParseException("Expected '('. Found {$p->current()}");
    }
    $p->next();
    
    $child = Expression::build($p);
    
    if($p->current() != ")") {
      throw new ParseException("Unterminated Expression Group");
    }
    $p->next();
    
    return new GroupExpression($child);
  }
}


/** 
 * 
 */
class Alternation implements IteratorAggregate {
  private $childs = [];
  
  public function __construct(array $childs = []) { 
    debug(__METHOD__);
    $this->childs = $childs;
  }  
  
  public function getIterator() {
    $it = new AppendIterator();
    foreach($this->childs as $child) {
      $it->append($child->getIterator());
    }
    return $it;
  }
  
  public static function build(StringIterator $p) {
    $nodes  = [];
    $childSymbols = [
      "Literal",
      "CharacterClass",
      "EscapeClass",
    ];

    if($p->current() != "[") {
      throw new ParseException("Expected '['. Found {$p->current()}");
    }
    $p->next();
    
    while($p->valid() && $p->current() != "]") {
      $hit = false;
      $mark = $p->pos();
      foreach($childSymbols as $symbol) {
        try {
          $node = $symbol::build($p);
          $hit = true;
          $nodes[] = $node;
          break;
        }
        catch(ParseException $e) {
          $p->seek($mark);
          continue;
        }
      }
      if(!$hit) {
        throw new ParseException("Invalid Alternation Expression");
      }
    }
    
    if($p->current() != "]") {
      throw new ParseException("Unterminated Alternation Expression");
    }
    else if(empty($nodes)) {
      throw new ParseException("Found empty Alternation");
    }
    $p->next();
    
    return new Alternation($nodes);
  }
}


/**
 *
 */
class Repeatition implements IteratorAggregate {
  private $node = null;
  private $min = null;
  private $max = null;
  
  public function __construct($node, $min, $max = null) {
    debug(__METHOD__ . "(.,$min, $max)");
    $this->min = $min;
    $this->max = $max ? $max: $min;
    $this->node = $node;
  }
  
  public function getIterator() {
    $itAll = [];
    for($i = $this->min; $i <= $this->max; $i++) {
      $itNum = [];
      for($j = 0; $j < $i; $j++) {
        $itNum[] = $this->node->getIterator();
      }
      $itAll[] = new SidewaysIterator($itNum, function($n){return join("", $n);});
    }
    $itFinal = new AppendIterator();
    foreach($itAll as $it) {
      $itFinal->append($it);
    }
    return $itFinal;
  }
  
  public static function build(StringIterator $p, $node) {
    if($p->current() != "{") {
      throw new ParseException("Expected '{'. Found {$p->current()}");
    }
    $m = [];
    if(preg_match("/^{(\d+)(,(\d+))?}/", substr($p->str(), $p->pos()), $m)) {
      $min = isset($m[1]) ? $m[1] : null;
      $max = isset($m[3]) ? $m[3] : null;
      $len = isset($m[0]) ? strlen($m[0]) : 0;
      $p->seek($p->pos() + $len);
    }
    else {
      throw new ParseException("Invalid Repeatition Expression");
    }
    
    return new Repeatition($node, $min, $max);
  }
} 


/**
 *
 */
class EscapeClass implements IteratorAggregate {
  private $char;
  
  public function __construct($char) {
    debug(__METHOD__ . "($char)");  
    $this->char = $char;
  }

  public function getIterator() {
    return new ArrayIterator([$this->char]);
  }
  
  public static function build(StringIterator $p) {
    $char = null;
    
    if($p->current() != '\\') {
      throw new ParseException("Expected \\.");
    }
    $p->next();
    $char = $p->current();
    if(!in_array($char, Characters::$special)) {
       throw new ParseException("Character {$char} not a legal escape");
    }
    $p->next();
    return new EscapeClass($char);
  } 
}


/**
 *
 */
class CharacterClass implements IteratorAggregate {
  public $cls;
  public static $classes = ['d', 'w'];
  
  public function __construct($cls) {
    debug(__METHOD__ . "($cls)");  
    $this->cls = $cls;
  }
  
  public function getIterator() {
    switch($this->cls) {
      case 'w':
        return new ArrayIterator(Characters::$alpha);
      case 'd':
        return new ArrayIterator(Characters::$digit);
    }
  }
  
  public static function build(StringIterator $p) {
    $char = null;
    
    if($p->current() != '\\') {
      throw new ParseException("Expected \\.");
    }
    $p->next();
    $class = $p->current();
    if(!in_array($class, ['d', 'w'])) {
       throw new ParseException("Class {$class} not a legal class");
    }
    $p->next();
    return new CharacterClass($class);
  } 
}


/**
 *
 */
class Literal implements IteratorAggregate {
  public $char = null;
  
  public function __construct($char) {
    debug(__METHOD__ . "($char)");
    $this->char = $char;
  }
  
  public function getIterator() {
    return new ArrayIterator([$this->char]);
  }
  
  public static function build(StringIterator $p) {
    $char = $p->current();
    if(in_array($char, Characters::$special)) {
     throw new ParseException("Character {$char} not a legal literal");
    }
    $p->next();    
    return new Literal($char);
  }
}
