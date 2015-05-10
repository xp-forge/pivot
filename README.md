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
2015-05-10 00:00:48 GOOD: 200 95 bytes (ETag: 214ceb4b-980-3a7bbd9630480)
2015-05-10 00:00:49 ERROR: 404 512 bytes (Not found)
2015-05-11 00:00:17 OK: 304 95 bytes
...
```

We will parse this using `sscanf()`, transforming the lines into arrays like the following:

```php
["2015-05-10", "00:00:48", "GOOD", 200, 95, "ETag: 214ceb4b-980-3a7bbd9630480"]
```

We can the load this into our pivot table using the array offsets (*if we had a map, we could use its string keys - for objects we'll pass references to the getters*). Putting it together, we get the following:

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
.-----------------------------------------------------------------------------.
| Category  | 2015-05-10 | 2015-05-11 | Sum      | Percentage | Count | Avg.   |
|-----------|------------|------------|----------|------------|-------|--------|
| OK        | b:100      | b:95       | b:195    | b:97.5     | b:2   | b:97.5 |
| GOOD      | b:2        | b:0        | b:2      | b:1.0      | b:1   | b:2    |
| ERROR     | b:0        | b:3        | b:3      | b:1.5      | b:3   | b:1.0  |
| ^- client | ^- b:0     | ^- b:2     | ^- b:2   | ^- b:1.0   | b:3   | b:0.67 |
|   ^- 403  |   ^- b:0   |   ^- b:1   |   ^- b:1 |   ^- b:0.5 | b:1   | b:1.0  |
|   ^- 404  |   ^- b:0   |   ^- b:1   |   ^- b:1 |   ^- b:0.5 | b:2   | b:0.5  |
| ^- server | ^- b:0     | ^- b:1     | ^- b:1   | ^- b:0.5   | b:1   | b:0.5  |
|   ^- 500  |   ^- b:0   |   ^- b:1   |   ^- b:1 |   ^- b:0.5 | b:1   | b:0.5  |
|-----------|------------|------------|----------|------------|-------|--------|
| Total     | b:102      | b:98       | b:200    |            | b:14  |        |
`-----------------------------------------------------------------------------´
```

### Accessing by category

We can iterate over the categories using the `rows()` method. Accessing a single row can be done via `row()`. The aggregates can be accessed by passing the category to the respective methods.

```php
$rows= $pivot->rows();                     // ['OK', 'GOOD', 'ERROR']
$transferred= $pivot->sum('OK')['bytes'];  // 195
$average= $pivot->average('OK')['bytes'];  // 97.5

// OK: 97.5%
// GOOD: 1.0%
// ERROR: 1.5%
foreach ($rows as $row) {
  printf("%s: %.2f%%\n", $row, $pivot->percentage($row)['bytes']);
}

// client: 1
// server: 3
foreach ($pivot->rows('ERROR') as $code) {
  printf("ERROR %s: %dx\n", $row, $pivot->count('ERROR', $code));
}
```

Accessing by date
-----------------
Pass the date to the `total()` method, e.g. `total("2015-05-10")['n']` = 102.

To iterate over the dates, use the `columns()` method.
Accessing a single column can be done via `column()`.

Grand totals
------------
Use the `total()` method without any argument to access the grand total for all
values (`['n' => 200]`).

The `count()` method will return many records we processed on (14).