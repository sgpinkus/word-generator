<?php
/**
 * In the absence of yeild() in <= PHP 5.4.
 */
require_once 'Logger.php';

class SidewaysIterator implements Iterator
{
  private $nodes = [];
  private $valid = true;
  private $key = null;
  private $current = null;

  public function __construct($nodes = [], $mapper = null) {
    foreach($nodes as $node) {
      if($node instanceof IteratorAggregate) {
        debug(__METHOD__ . " (IteratorAggregate)");     
        $this->nodes[] = $node->getIterator();
      }      
      else if(is_array($node)) {
        debug(__METHOD__ . " (Array)");
        $this->nodes[] = new ArrayIterator($node);
      }
      else if($node instanceof Traversable) {
        debug(__METHOD__ . " (Traversable)");            
        $this->nodes[] = $node;
      }      
      else {
        throw new SidewaysIteratorException("Invalid argument to constructor. Non iterable item found");
      }
    }
    if(isset($mapper) && is_callable($mapper)) {
      $this->mapper = $mapper;
    }
    else {
      throw new SidewaysIteratorException("Invalid mapper callable");
    }
    $this->init();
  }
  
  private function init() {
    $this->valid = true;
    $this->current = null;
    $this->key = null;
    $this->updateCurrent();
  }

  public function rewind() {
    foreach($this->nodes as $node) {
      $node->rewind();
    }
    $this->init();
  }

  public function current() {
    if($this->mapper) {
      $x = $this->mapper;
      return $x($this->current);
    }
    return $this->current;
  }

  public function key() {
    return $this->key;
  }

  public function next() {
    $valid = false;
    for($i = sizeof($this->nodes) - 1; $i >= 0; $i--) {
      $node = $this->nodes[$i];
      $node->next();
      if($node->valid()) {
        $valid = true;
        $this->updateCurrent(); 
        break;
      }
      else {
        $node->rewind();
      }
    }
    $this->valid = $valid;
  }

  public function valid() {
    return $this->valid;
  }
  
  public function updateCurrent() {
    $this->current = [];  
    for($i = 0; $i < sizeof($this->nodes); $i++) {
      $node = $this->nodes[$i];
      $this->current[] = $node->current();
    }
    $this->updateKey();
  }
  
  public function updateKey() {
    $key = 0;
    $len = sizeof($this->nodes);
    for($i = 0; $i < $len; $i++) {
      $node = $this->nodes[$i];
      $base = sizeof($node);
      $key += $node->key()*pow($base, $len - $i - 1);
    }
    $this->key = (int)$key;
  }
}

class SidewaysIteratorException extends Exception {
}

