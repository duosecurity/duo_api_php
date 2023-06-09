# Overview

[![Build Status](https://github.com/duosecurity/duo_api_php/workflows/PHP%20CI/badge.svg?branch=master)](https://github.com/duosecurity/duo_api_php/actions)

**Auth** - https://www.duosecurity.com/docs/authapi

**Admin** - https://www.duosecurity.com/docs/adminapi

**Accounts** - https://www.duosecurity.com/docs/accountsapi

## Tested Against PHP Versions:
* 8.0
* 8.1
* 8.2

## TLS 1.2 and 1.3 Support

Duo_api_php uses PHP's cURL extension and OpenSSL for TLS operations.  TLS support will depend on the versions of multiple libraries:

TLS 1.2 support requires PHP 5.5 or higher, curl 7.34.0 or higher, and OpenSSL 1.0.1 or higher.

TLS 1.3 support requires PHP 7.3 or higher, curl 7.61.0 or higher, and OpenSSL 1.1.1 or higher.

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
```

Note that the tests in `tests/SSL/SSLTest.php` require `stunnel3`.

# Linting

```
$ ./vendor/bin/phpcs --standard=PSR2 -n src/* tests/*
```
