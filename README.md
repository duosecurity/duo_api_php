# Overview

**Auth** - https://www.duosecurity.com/docs/authapi

**Verify** - https://www.duosecurity.com/docs/duoverify

**Admin** - https://www.duosecurity.com/docs/adminapi

**Accounts** - https://www.duosecurity.com/docs/accountsapi

# Installing

```
$ git clone https://github.com/duosecurity/duo_api_php.git
$ composer install
```

To add this to your project simply include the following in your `composer.json`:

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
$ phpunit -c phpunit.xml
PHPUnit 4.7.2 by Sebastian Bergmann and contributors.

.........

Time: 47 ms, Memory: 4.50Mb

OK (9 tests, 9 assertions)
```
