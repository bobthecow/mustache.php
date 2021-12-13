Mustache.php
============

A [Mustache](https://mustache.github.io/) implementation in PHP.

[![Package version](http://img.shields.io/packagist/v/mustache/mustache.svg?style=flat-square)](https://packagist.org/packages/mustache/mustache)
[![Build status](http://img.shields.io/travis/bobthecow/mustache.php/dev.svg?style=flat-square)](http://travis-ci.org/bobthecow/mustache.php)
[![StyleCI](https://styleci.io/repos/569670/shield)](https://styleci.io/repos/569670)
[![Monthly downloads](http://img.shields.io/packagist/dm/mustache/mustache.svg?style=flat-square)](https://packagist.org/packages/mustache/mustache)


Usage
-----

A quick example:

```php
<?php
$m = new Mustache_Engine(array('entity_flags' => ENT_QUOTES));
echo $m->render('Hello {{planet}}', array('planet' => 'World!')); // "Hello World!"
```


And a more in-depth example -- this is the canonical Mustache template:

```html+jinja
Hello {{name}}
You have just won {{value}} dollars!
{{#in_ca}}
Well, {{taxed_value}} dollars, after taxes.
{{/in_ca}}
```


Create a view "context" object -- which could also be an associative array, but those don't do functions quite as well:

```php
<?php
class Chris {
    public $name  = "Chris";
    public $value = 10000;

    public function taxed_value() {
        return $this->value - ($this->value * 0.4);
    }

    public $in_ca = true;
}
```


And render it:

```php
<?php
$m = new Mustache_Engine(array('entity_flags' => ENT_QUOTES));
$chris = new Chris;
echo $m->render($template, $chris);
```

*Note:* we recommend using `ENT_QUOTES` as a default of [entity_flags](https://github.com/bobthecow/mustache.php/wiki#entity_flags) to decrease the chance of Cross-site scripting vulnerability.

And That's Not All!
-------------------

Read [the Mustache.php documentation](https://github.com/bobthecow/mustache.php/wiki/Home) for more information.


See Also
--------

 * [mustache(5)](http://mustache.github.io/mustache.5.html) man page.
 * [Readme for the Ruby Mustache implementation](http://github.com/defunkt/mustache/blob/master/README.md).
