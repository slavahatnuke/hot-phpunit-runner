hot/phpunit-runner
==================

`Requirements: php, phpunit, git, find`

### Watcher for php classes and tests

`bin/phpunit-hot-runner --config=app/phpunit.xml --watch`

* When you change entity `XXXClass` this watcher looking for and run tests for this entity.
* When you change test for entity `XXXClassTest` this watcher run this test too.

```
PHPUnit HotRunner has been started


> phpunit -c app/phpunit.xml ./src/.../....../XXXClassTest.php

PHPUnit 3.7.21 by Sebastian Bergmann.

Configuration read from /Users/slava/project/xxx/app/phpunit.xml

....

Time: 1 second, Memory: 10.00Mb

OK (4 tests, 9 assertions)


[OK]

```


**[notice]** If you run `bin/phpunit-hot-runner` without any options then you
run all tests (or test for entity) based on `git status` (one time).
FYI, runner will not run tests next time. ( result is cached for watcher )


#### Default running looks like:

* `bin/phpunit-hot-runner --watch`
* `bin/phpunit-hot-runner --config=app/phpunit.xml --watch`

#### Options
```
 --config=app/phpunit.xml # custom phpunit config file
 --options="-c app/ --testdox" # custom phpunit options (alternative for `--config`)
 --watch # watcher mode
 --period=2 # sec. watcher period
 --clean # this option remove cached result and re-test full
 --phpunit-bin=bin/phpunit # custom path to phpunit
 --test-similarity=100 # percentage of similarity 0..100 (entity vs test)
 --coverage=project.coverage.xml # collect coverage and write Clover xml
```

#### Install

* composer: `"hot/phpunit-runner": "dev-master"`
* http://packagist.org/packages/hot/phpunit-runner
* https://github.com/slavahatnuke/hot-phpunit-runner

#### Workflows

1. `bin/phpunit-hot-runner --watch` # default watcher
2. `bin/phpunit-hot-runner --config=app/phpunit.xml --watch` # with phpunit config file
3. `bin/phpunit-hot-runner --config=app/phpunit.xml --phpunit-bin=bin/phpunit --watch` # with custom `phpunit`
4. `bin/phpunit-hot-runner --clean` # clear cache and re-test changeset (when watcher working)
5. `bin/phpunit-hot-runner --config=app/phpunit.xml --watch --period=1` # with phpunit config hot watcher with `--period=1`
6. `bin/phpunit-hot-runner --config=app/phpunit.xml --watch &` # with phpunit config file and with `&` (background process)
7. `bin/phpunit-hot-runner --watch --test-similarity=50` # default watcher with `--test-similarity=50` # watcher can not find my tests. I need to reduce the similarity.
8. `bin/phpunit-hot-runner --options="-c app/ --testdox" --watch` # watcher with `--options="-c app/ --testdox"` # with custom phpunit options
9. `bin/phpunit-hot-runner --config=app/ --coverage=project.coverage.xml --watch` # with `--coverage=project.coverage.xml`. shortcut to collect coverage

#### PhpStorm

Highlight coverage in IDE

plugin: [download](plugin/phpunit_codecoverage_display.jar)

https://github.com/oker1/phpunit_codecoverage_display/wiki


