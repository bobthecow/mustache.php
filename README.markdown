Mustache.php
============

A [Mustache](http://defunkt.github.com/mustache/) implementation in PHP.


Usage
-----

A quick example:

    <?php
    include('Mustache.php');
    $m = new Mustache;
    echo $m->render('Hello {{planet}}', array('planet' => 'World!'));
    // "Hello World!"
    ?>


And a more in-depth example--this is the canonical Mustache template:

    Hello {{name}}
    You have just won ${{value}}!
    {{#in_ca}}
    Well, ${{taxed_value}}, after taxes.
    {{/in_ca}}


Along with the associated Mustache class:

    <?php
    class Chris extends Mustache {
        public $name = "Chris";
        public $value = 10000;
    
        public function taxed_value() {
            return $this->value - ($this->value * 0.4);
        }
    
        public $in_ca = true;
    }


Render it like so:

    <?php
    $c = new Chris;
    echo $chris->render($template);
    ?>


Here's the same thing, a different way:

Create a view object--which could also be an associative array, but those don't do functions quite as well:

    <?php
    class Chris {
        public $name = "Chris";
        public $value = 10000;
    
        public function taxed_value() {
            return $this->value - ($this->value * 0.4);
        }
    
        public $in_ca = true;
    }
    ?>


And render it:

    <?php
    $chris = new Chris;
    $m = new Mustache;
    echo $m->render($template, $chris);
    ?>

Finally, if you have the [PHP YAML module](http://php.net/YAML) (yaml_parse) installed or specify an [alternative YAML parser](http://symfony.com/doc/2.0/reference/YAML.html), then [YAML frontmatter](http://mustache.github.com/mustache.1.html) at the beginning of your Mustache templates is also supported:

    ---
    names: [ {name: chris}, {name: mark}, {name: scott} ]
    ---
    {{#names}}
      Hi {{name}}!
    {{/names}}

If you don't want to use yaml_parse(), specify your alternative as an option before rendering:

    <?php
    $m = new Mustache($template, null, null, array(
        'yaml_parser' => array('Symfony\Component\Yaml\Yaml', 'parse')
    ));
    echo $m->render();
    ?>

For [Jekyll](http://github.com/mojombo/jekyll/wiki/YAML-Front-Matter)-like frontmatter, you can also define a namespace for your YAML data:

    <?php
    $template = <<<MUSTACHE_IN
    ---
    title: Hello World
    ---
    <h1>{{ page.title }}</h1>
    MUSTACHE_IN;

    $m = new Mustache($template, null, null, array(
        'yaml_namespace' => 'page'
    ));
    echo $m->render();
    ?>


Known Issues
------------

 * As of Mustache spec v1.1.2, there are a couple of whitespace bugs around section tags... Despite these failing tests, this
   version is actually *closer* to correct than previous releases.


See Also
--------

 * [Readme for the Ruby Mustache implementation](http://github.com/defunkt/mustache/blob/master/README.md).
 * [mustache(1)](http://mustache.github.com/mustache.1.html) and [mustache(5)](http://mustache.github.com/mustache.5.html) man pages.
