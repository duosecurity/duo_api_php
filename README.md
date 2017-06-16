# Overview

[![Build Status](https://travis-ci.org/duosecurity/duo_api_php.svg?branch=master)](https://travis-ci.org/duosecurity/duo_api_php)

**Auth** - https://www.duosecurity.com/docs/authapi

**Verify** - https://www.duosecurity.com/docs/duoverify

**Admin** - https://www.duosecurity.com/docs/adminapi

**Accounts** - https://www.duosecurity.com/docs/accountsapi

# Installing

Development:

```
$ git clone https://github.com/duosecurity/duo_api_php.git
$ cd duo_api_php
$ composer install
```

System:

```
$ composer global require duosecurity/duo_api_php:dev-master
```

Or add the following to your project:

```
{
    "require": {
        "duosecurity/duo_api_php": "dev-master"
    }
}
```

# Using

```
$ php -a -d auto_prepend_file=vendor/autoload.php
Interactive mode enabled

php > $D = new DuoAPI\Auth($ikey, $skey, $host);
php > var_dump($D->preauth($username));
array(2) {
  'response' =>
  array(2) {
    'response' =>
    array(3) {
      'enroll_portal_url' =>
      string(23) "https://api-example.com"
      'result' =>
      string(6) "enroll"
      'status_msg' =>
      string(42) "Enroll an authentication device to proceed"
    }
    'stat' =>
    string(2) "OK"
  }
  'success' =>
  bool(true)
}
```

# Testing

```
$ ./vendor/bin/phpunit -c phpunit.xml
PHPUnit 5.3.2 by Sebastian Bergmann and contributors.

..............................                                    30 / 30 (100%)

Time: 1.18 seconds, Memory: 6.00Mb

OK (30 tests, 48 assertions)
```

# Linting

```
$ ./vendor/bin/phpcs --standard=PSR2 -n src/* tests/*
```
