hot-phpunit-runner
==================

`Requirements: php, phpunit, git, find`

Watcher for php classes and tests

`bin/phpunit-hot-runner --watch --period=2 --config=app/phpunit.xml`

When you change entity `XXXClass` this watcher will looking for and run test for this entity.

```
PHPUnit HotRunner has been started


> phpunit -c app/phpunit.xml ./src/.../.../XXXClassTest.php

PHPUnit 3.7.21 by Sebastian Bergmann.

Configuration read from /Users/slava/project/xxx/app/phpunit.xml

....

Time: 1 second, Memory: 10.00Mb

OK (4 tests, 9 assertions)


[OK]

```

You can run test with phpunit configuration in this case:

`bin/phpunit-hot-runner --config=app/phpunit.xml`

Runner will run tests for changed entities one time and will not observe changes

FYI, runner will not run tests next time. ( result is cached for watcher )

if you need remove cache and retest all changes run `bin/phpunit-hot-runner --clean`

You can setup `bin/phpunit-hot-runner --phpunit-bin=bin/phpunit`

Also you can execute this bin without config for phpunit

`bin/phpunit-hot-runner`

composer: `"hot/phpunit-runner": "dev-master"` http://packagist.org/packages/hot/phpunit-runner

you can setup similarity of test `bin/phpunit-hot-runner --test-similarity=80`
this option used in search algorithm

