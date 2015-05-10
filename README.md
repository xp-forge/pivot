Pivot table
===========

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-forge/pivot.svg)](http://travis-ci.org/xp-forge/pivot)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_4plus.png)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/pivot/version.png)](https://packagist.org/packages/xp-forge/pivot)

Working with [pivot tables](https://en.wikipedia.org/wiki/Pivot_table)

Example
-------
Given the following input, e.g. from a logfile:

```
2015-05-10 00:00:09 OK: 304 100 bytes
2015-05-10 00:00:48 GOOD: 200 102 bytes (ETag: 214ceb4b-980-3a7bbd9630480)
2015-05-10 03:00:49 ERROR: 404 512 bytes (Not found)
2015-05-11 00:00:17 OK: 304 102 bytes
2015-05-11 02:01:01 ERROR: 500 0 bytes (Internal Server Error)
2015-05-11 02:01:02 ERROR: 500 256 bytes (Internal Server Error)
...
```

We will parse this using `sscanf()`, transforming the lines into arrays like the following:

```php
["2015-05-10", "00:00:48", "GOOD", 200, 95, "ETag: 214ceb4b-980-3a7bbd9630480"]
```

We can the load this into our pivot table using the array offsets (*if we had a map, we could use its string keys; for objects we'll pass references to the getters and for more complex situations we can pass closures*). Putting it together, we get the following:

```php
use io\streams\TextReader;
use io\streams\FileInputStream;
use util\data\PivotCreation;

$pivot= (new PivotCreation())
  ->groupingBy(2)        // category
  ->groupingBy(3)        // code
  ->spreadingBy(0)       // date
  ->summing(4, 'bytes')  // bytes
  ->create()
);

$reader= new TextReader(new FileInputStream('measures.log'));
while (null !== ($line= $reader->readLine())) {
  $pivot->add(sscanf($line, '%[0-9-] %[0-9:] %[^:]: %d %d bytes (%[^)])'));
}
```

The resulting table will look something like this (using "b:" as an abbreviation for *bytes*):

```
.------------------------------------------------------------------------.
| Category  | Count  | 2015-05-10 | 2015-05-11 | Sum        | Avg.       |
|-----------|--------|------------|------------|------------|------------|
| OK        | 2      | b:100      | b:102      | b:202      | b:101      |
| GOOD      | 1      | b:102      | b:0        | b:102      | b:102      |
| ERROR     | 3      | b:512      | b:256      | b:768      | b:256      |
| ^- client | ^- 1   | ^- b:512   | ^- b:0     | ^- b:512   | ^- b:512   |
|   ^- 404  |   ^- 1 |   ^- b:512 |   ^- b:0   |   ^- b:512 |   ^- b:512 |
| ^- server | ^- 2   | ^- b:0     | ^- b:256   | ^- b:256   | ^- b:128   |
|   ^- 500  |   ^- 2 |   ^- b:0   |   ^- b:256 |   ^- b:256 |  ^- b:128  |
|-----------|--------|------------|------------|------------|------------|
| Total     | 6      | b:714      | b:358      | b:1072     | b:178.67   |
`------------------------------------------------------------------------´
```

### Accessing values in a pivot

The number of records grouped by the grouping columns can be retrieved via `count()`. The aggregates can be accessed by passing the category to the respective methods. 

```php
$count= $pivot->count('OK');                   // 2
$transferred= $pivot->sum('OK')['bytes'];      // 202
$average= $pivot->average('OK')['bytes'];      // 101.0
```

### Drill down

We can dril down by the categories we grouped on by using the `rows()` method. To calculate the distribution of categories in percent of the total, we'll use the `count()` method.

```php
$rows= $pivot->rows();                         // ['OK', 'GOOD', 'ERROR']

// OK: 2 / 6 = 33.3%
// GOOD: 1 / 6 = 16.7%
// ERROR: 3 / 6 = 50.0%
$total= $pivot->count();
foreach ($pivot->rows() as $row) {
  $count= $pivot->count($row);
  printf("%s: %d / %d = %.1f%%\n", $row, $count, $total, $count / $total * 100);
}

// client: 1
// server: 2
foreach ($pivot->rows('ERROR') as $code) {
  printf("ERROR %s: %dx\n", $row, $pivot->count('ERROR', $code));
}
```

It can also interesting to see a development over time, so we'll drill down based on the columsn instead.

```php
$columns= $pivot->columns();                   // ['2015-05-10', '2015-05-11']

// 2015-05-10: 714 / 1072 bytes = 66.6%
// 2015-05-11: 358 / 1072 bytes = 33.4%
$total= $pivot->total()['bytes'];
foreach ($pivot->columns() as $column) {
  $bytes= $pivot->total($column)['bytes'];
  printf("%s: %d / %d bytes = %.1f%%\n", $column, $bytes, $total, $bytes / $total * 100);
}
```
