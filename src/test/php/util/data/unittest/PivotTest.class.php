<?php namespace util\data\unittest;

use util\data\Sequence;
use util\data\Collectors;
use util\data\Pivot;
use lang\IllegalArgumentException;

class PivotTest extends \unittest\TestCase {

  /** @return var[] */
  private function measurements() {
    return [
      ['type' => 'good', 'status' => 200, 'date' => '2015-05-10', 'bytes' => 2000, 'occurrences' => 100],
      ['type' => 'good', 'status' => 200, 'date' => '2015-05-11', 'bytes' => 2020, 'occurrences' => 101],
      ['type' => 'ok',   'status' => 200, 'date' => '2015-05-10', 'bytes' => 200,  'occurrences' => 9],
      ['type' => 'bad',  'status' => 401, 'date' => '2015-05-10', 'bytes' => 1024, 'occurrences' => 1],
      ['type' => 'bad',  'status' => 404, 'date' => '2015-05-10', 'bytes' => 1024, 'occurrences' => 4],
      ['type' => 'bad',  'status' => 500, 'date' => '2015-05-10', 'bytes' => 1280, 'occurrences' => 5],
    ];
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function groupingBy_cannot_be_omitted() {
    Sequence::of($this->measurements())->collect(Collectors::toPivot());
  }

  #[@test]
  public function rows() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()->groupingBy('type'));
    $this->assertEquals(['good', 'ok', 'bad'], $pivot->rows());
  }

  #[@test]
  public function row() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()->groupingBy('type'));
    $this->assertEquals(
      [Pivot::COUNT => 2, Pivot::TOTAL => [], Pivot::ROWS => [], Pivot::COLS => []],
      $pivot->row('good')
    );
  }

  #[@test]
  public function row_with_sum() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->summing('bytes')
    );
    $this->assertEquals(
      [Pivot::COUNT => 2, Pivot::TOTAL => ['bytes' => 4020], Pivot::ROWS => [], Pivot::COLS => []],
      $pivot->row('good')
    );
  }

  #[@test]
  public function row_with_sums() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->summing('bytes')
      ->summing('occurrences')
    );
    $this->assertEquals(
      [Pivot::COUNT => 2, Pivot::TOTAL => ['bytes' => 4020, 'occurrences' => 201], Pivot::ROWS => [], Pivot::COLS => []],
      $pivot->row('good')
    );
  }

  #[@test]
  public function row_with_spreading() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    $this->assertEquals(
      [Pivot::COUNT => 2, Pivot::TOTAL => ['occurrences' => 201], Pivot::ROWS => [], Pivot::COLS => [
        '2015-05-10' => [Pivot::COUNT => 1, Pivot::TOTAL => ['occurrences' => 100]],
        '2015-05-11' => [Pivot::COUNT => 1, Pivot::TOTAL => ['occurrences' => 101]]
      ]],
      $pivot->row('good')
    );
  }

  #[@test]
  public function total() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->summing('occurrences')
    );
    $this->assertEquals(220, $pivot->total()['occurrences']);
  }

  #[@test]
  public function total_by_date() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    $this->assertEquals(119, $pivot->total('2015-05-10')['occurrences']);
  }

  #[@test]
  public function count() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
    );
    $this->assertEquals(6, $pivot->count());
  }

  #[@test, @values([['good', 2], ['ok', 1], ['bad', 3]])]
  public function count_of($category, $expect) {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
    );
    $this->assertEquals($expect, $pivot->count($category));
  }

  #[@test, @values([['good', 201], ['ok', 9], ['bad', 10]])]
  public function sum_of($category, $expect) {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->summing('occurrences')
    );
    $this->assertEquals($expect, $pivot->sum($category)['occurrences']);
  }

  #[@test, @values([['good', 91.364], ['ok', 4.091], ['bad', 4.545]])]
  public function percentage_of($category, $expect) {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->summing('occurrences')
    );
    $this->assertEquals($expect, round($pivot->percentage($category)['occurrences'], 3));
  }

  #[@test, @values([['good', 100.500], ['ok', 9.000], ['bad', 3.333]])]
  public function average_of($category, $expect) {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->summing('occurrences')
    );
    $this->assertEquals($expect, round($pivot->average($category)['occurrences'], 3));
  }

  #[@test]
  public function columns_empty_when_used_without_spreading() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->summing('occurrences')
    );
    $this->assertEquals([], $pivot->columns());
  }

  #[@test]
  public function columns() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    $this->assertEquals(['2015-05-10', '2015-05-11'], $pivot->columns());
  }

  #[@test]
  public function column() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    $this->assertEquals(
      [Pivot::COUNT => 5, Pivot::TOTAL => ['occurrences' => 119]],
      $pivot->column('2015-05-10')
    );
  }

  #[@test, @values([[401, 1], [404, 4], [500, 5]])]
  public function grouping_by_multiple_columns($status, $occurrences) {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->groupingBy('status')
      ->summing('occurrences')
    );
    $this->assertEquals(10, $pivot->sum('bad')['occurrences']);
    $this->assertEquals([401, 404, 500], $pivot->rows('bad'));
    $this->assertEquals($occurrences, $pivot->sum('bad', $status)['occurrences']);
    $this->assertEquals($occurrences / 220 * 100, $pivot->percentage('bad', $status)['occurrences']);
  }

  #[@test]
  public function summing_multiple_colums() {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->summing('occurrences')
      ->summing('bytes')
    );
    $this->assertEquals(['occurrences' => 10, 'bytes' => 3328], $pivot->sum('bad'));
  }

  #[@test, @values([
  #  [null, [10]],
  #  [0, [10]],
  #  ['occurrences', ['occurrences' => 10]]
  #])]
  public function summing_with_function_and_names($key, $expect) {
    $pivot= Sequence::of($this->measurements())->collect(Collectors::toPivot()
      ->groupingBy('type')
      ->summing(function($row) { return $row['occurrences']; }, $key)
    );
    $this->assertEquals($expect, $pivot->sum('bad'));
  }
}