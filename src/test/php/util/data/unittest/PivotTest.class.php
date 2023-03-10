<?php namespace util\data\unittest;

use lang\IllegalArgumentException;
use test\{Assert, Expect, Test, Values};
use util\data\{InPivot, Pivot, Sequence};

class PivotTest {

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

  #[Test, Expect(IllegalArgumentException::class)]
  public function groupingBy_cannot_be_omitted() {
    Sequence::of($this->measurements())->collect(new InPivot());
  }

  #[Test]
  public function rows() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())->groupingBy('type'));
    Assert::equals(['good', 'ok', 'bad'], $pivot->rows());
  }

  #[Test]
  public function row() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())->groupingBy('type'));
    Assert::equals(
      [Pivot::COUNT => 2, Pivot::TOTAL => [], Pivot::ROWS => [], Pivot::COLS => []],
      $pivot->row('good')
    );
  }

  #[Test]
  public function row_with_sum() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing('bytes')
    );
    Assert::equals(
      [Pivot::COUNT => 2, Pivot::TOTAL => ['bytes' => 4020], Pivot::ROWS => [], Pivot::COLS => []],
      $pivot->row('good')
    );
  }

  #[Test]
  public function row_with_sums() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing('bytes')
      ->summing('occurrences')
    );
    Assert::equals(
      [Pivot::COUNT => 2, Pivot::TOTAL => ['bytes' => 4020, 'occurrences' => 201], Pivot::ROWS => [], Pivot::COLS => []],
      $pivot->row('good')
    );
  }

  #[Test]
  public function row_with_spreading() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    Assert::equals(
      [Pivot::COUNT => 2, Pivot::TOTAL => ['occurrences' => 201], Pivot::ROWS => [], Pivot::COLS => [
        '2015-05-10' => [Pivot::COUNT => 1, Pivot::TOTAL => ['occurrences' => 100]],
        '2015-05-11' => [Pivot::COUNT => 1, Pivot::TOTAL => ['occurrences' => 101]]
      ]],
      $pivot->row('good')
    );
  }

  #[Test]
  public function total() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing('occurrences')
    );
    Assert::equals(220, $pivot->total()['occurrences']);
  }

  #[Test]
  public function count() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
    );
    Assert::equals(6, $pivot->count());
  }

  #[Test, Values([['good', 2], ['ok', 1], ['bad', 3]])]
  public function count_of($category, $expect) {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
    );
    Assert::equals($expect, $pivot->count($category));
  }

  #[Test]
  public function sum() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing('occurrences')
    );
    Assert::equals(220, $pivot->sum()['occurrences']);
  }

  #[Test, Values([['good', 201], ['ok', 9], ['bad', 10]])]
  public function sum_of($category, $expect) {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing('occurrences')
    );
    Assert::equals($expect, $pivot->sum($category)['occurrences']);
  }

  #[Test]
  public function average() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing('occurrences')
    );
    Assert::equals(36.667, round($pivot->average()['occurrences'], 3));
  }

  #[Test, Values([['good', 100.500], ['ok', 9.000], ['bad', 3.333]])]
  public function average_of($category, $expect) {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing('occurrences')
    );
    Assert::equals($expect, round($pivot->average($category)['occurrences'], 3));
  }

  #[Test]
  public function columns_empty_when_used_without_spreading() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing('occurrences')
    );
    Assert::equals([], $pivot->columns());
  }

  #[Test]
  public function columns() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    Assert::equals(['2015-05-10', '2015-05-11'], $pivot->columns());
  }

  #[Test]
  public function records() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    Assert::equals(5, $pivot->records('2015-05-10'));
  }

  #[Test]
  public function records_by() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    Assert::equals(1, $pivot->records('2015-05-10', 'ok'));
  }

  #[Test]
  public function column() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    Assert::equals(['occurrences' => 119], $pivot->column('2015-05-10'));
  }

  #[Test]
  public function column_by() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->spreadingOn('date')
      ->summing('occurrences')
    );
    Assert::equals(['occurrences' => 9], $pivot->column('2015-05-10', 'ok'));
  }

  #[Test, Values([[401, 1], [404, 4], [500, 5]])]
  public function grouping_by_multiple_columns($status, $occurrences) {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->groupingBy('status')
      ->summing('occurrences')
    );
    Assert::equals(10, $pivot->sum('bad')['occurrences']);
    Assert::equals([401, 404, 500], $pivot->rows('bad'));
    Assert::equals($occurrences, $pivot->sum('bad', $status)['occurrences']);
  }

  #[Test]
  public function summing_multiple_colums() {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing('occurrences')
      ->summing('bytes')
    );
    Assert::equals(['occurrences' => 10, 'bytes' => 3328], $pivot->sum('bad'));
  }

  #[Test, Values([[null, [10]], [0, [10]], ['occurrences', ['occurrences' => 10]]])]
  public function summing_with_function_and_names($key, $expect) {
    $pivot= Sequence::of($this->measurements())->collect((new InPivot())
      ->groupingBy('type')
      ->summing(function($row) { return $row['occurrences']; }, $key)
    );
    Assert::equals($expect, $pivot->sum('bad'));
  }
}