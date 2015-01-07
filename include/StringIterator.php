<?php

class StringIterator implements Iterator
{
    private $str = array();
    private $pos = 0;

    public function __construct($str)
    {
      $this->str = $str;
    }

    public function rewind() 
    {
      $this->pos = 0;
    }

    public function current() 
    {
      if($this->valid()) {
        return $this->str[$this->pos];
      } 
      else {
        return null;
      }
    }

    public function key() 
    {
      if($this->valid()) {
        return $this->pos;
      }
      else {
        return null;
      }
    }

    public function next() 
    {
      $this->pos++;
    }

    public function valid() 
    {
      return $this->pos < strlen($this->str);
    }
    
    public function seek($n) {
      $this->pos = abs($n);
    }
    
    public function str() {
      return $this->str;
    }
    
    public function pos() {
      return $this->pos;
    }
}

