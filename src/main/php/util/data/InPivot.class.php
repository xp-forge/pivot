<?php namespace util\data;

/**
 * A collector
 *
 * @test  xp://util.data.unittest.PivotTest
 */
class InPivot implements ICollector {
  private $creation;

  /**
   * Creates a new collector instance
   */
  public function __construct() {
    $this->creation= new PivotCreation();
  }

  /**
   * Sets what to group by
   *
   * @param  var $arg
   * @return self
   */
  public function groupingBy($arg) {
    $this->creation->groupingBy($arg);
    return $this;
  }

  /**
   * Sets what to spread on
   *
   * @param  var $arg
   * @return self
   */
  public function spreadingOn($arg) {
    $this->creation->spreadingOn($arg);
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
    $this->creation->summing($arg, $name);
    return $this;
  }

  /** @return function(): var */
  public function supplier() {
    return function() { return $this->creation->create(); };
  }

  /** @return function(var: var): void */
  public function accumulator() {
    return function($pivot, $row) { $pivot->add($row); };
  }

  /** @return function(var): var */
  public function finisher() {
    return null;
  }
}