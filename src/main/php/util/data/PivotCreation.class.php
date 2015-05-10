<?php namespace util\data;

class PivotCreation extends \lang\Object {
  private $groupBy= [], $spreadOn= null, $aggregate= [];

  /**
   * Helper for groupingBy() and spreadingOn()
   *
   * @param  var $arg
   * @return function(var): var
   */
  private function select($arg) {
    if (is_string($arg) || is_int($arg)) {
      return function($value) use($arg) { return $value[$arg]; };
    } else {
      return Functions::$UNARYOP->cast($arg);
    }
  }

  /**
   * Sets what to group by
   *
   * @param  var $arg
   * @return self
   */
  public function groupingBy($arg) {
    $this->groupBy[]= $this->select($arg);
    return $this;
  }

  /**
   * Sets what to spread on
   *
   * @param  var $arg
   * @return self
   */
  public function spreadingOn($arg) {
    $this->spreadOn= $this->select($arg);
    return $this;
  }

  /**
   * Applies sum() aggregate
   *
   * @param  var $arg
   * @param  string $name
   * @return self
   */
  public function summing($arg, $name= null) {
    if (is_int($arg) || is_string($arg)) {
      $this->aggregate[null === $name ? $arg : $name]= function($value) use($arg) { return $value[$arg]; };
    } else {
      $this->aggregate[$name ?: sizeof($this->aggregate)]= Functions::$UNARYOP->cast($arg);
    }
    return $this;
  }

  /** @return util.data.Pivot */
  public function create() {
    return new Pivot($this->groupBy, $this->spreadOn, $this->aggregate);
  }
}