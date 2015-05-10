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
2015-05-10 00:00:09 OK: 304
2015-05-10 00:00:17 OK: 304
2015-05-10 00:00:42 OK: 304
2015-05-10 00:00:48 GOOD: 200 (Cache miss, reload)
2015-05-10 00:00:49 ERROR: 404 (Not found)
...
```

...we load this into pivot table using the following:

```php
use io\streams\TextReader;
use util\data\Pivot;

$pivot= new Pivot(
  [function($row) { return $row[2]; }, function($row) { return $row[3]; }],
  function($row) { return $row[1]; },
  ['occurrences' => function($row) { return 1; }]
);

$reader= new TextReader(new FileInputStream('measures.log'));
while (null !== ($line= $reader->readLine())) {
  $pivot->add(sscanf($line, '%[0-9-] %[0-9:] %[^:]: %d (%[^)])'));
}
```

The resulting table will look something like this:

```
.-----------------------------------------------------------------------------.
| Category  | 2015-05-10 | 2015-05-11 | Sum      | Percentage | Count | Avg.   |
|-----------|------------|------------|----------|------------|-------|--------|
| OK        | n:100      | n:95       | n:195    | n:97.5     | n:2   | n:97.5 |
| GOOD      | n:2        | n:0        | n:2      | n:1.0      | n:1   | n:2    |
| ERROR     | n:0        | n:3        | n:3      | n:1.5      | n:3   | n:1.0  |
| ^- client | ^- n:0     | ^- n:2     | ^- n:2   | ^- n:1.0   | n:3   | n:0.67 |
|   ^- 403  |   ^- n:0   |   ^- n:1   |   ^- n:1 |   ^- n:0.5 | n:1   | n:1.0  |
|   ^- 404  |   ^- n:0   |   ^- n:1   |   ^- n:1 |   ^- n:0.5 | n:2   | n:0.5  |
| ^- server | ^- n:0     | ^- n:1     | ^- n:1   | ^- n:0.5   | n:1   | n:0.5  |
|   ^- 500  |   ^- n:0   |   ^- n:1   |   ^- n:1 |   ^- n:0.5 | n:1   | n:0.5  |
|-----------|------------|------------|----------|------------|-------|--------|
| Total     | n:102      | n:98       | n:200    |            | n:14  |        |
`-----------------------------------------------------------------------------´
```
Accessing by category
---------------------
Use the `sum()`, `average()` and `percentage()` methods to access the values. In the above example, `sum("OK")['n']` = 195, `average("ERROR")['n']` = 1.5 and `percentage("GOOD")['n']` = 1.0. The subtotals e.g. for client errors can be accessed by passing in varargs: `sum("ERROR", "client")['n']` = 2. The number of records included in the fact is returned by `count("OK")` = 2.

To iterate over the categories, use the `rows()` method.
Accessing a single row can be done via `row()`.

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