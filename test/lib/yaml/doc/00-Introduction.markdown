Introduction
============

This book is about *Symfony YAML*, a PHP library part of the Symfony
Components project. Its official website is at
http://components.symfony-project.org/yaml/.

>**SIDEBAR**
>About the Symfony Components
>
>[Symfony Components](http://components.symfony-project.org/) are
>standalone PHP classes that can be easily used in any
>PHP project. Most of the time, they have been developed as part of the
>[Symfony framework](http://www.symfony-project.org/), and decoupled from the
>main framework later on. You don't need to use the Symfony MVC framework to use
>the components.

What is it?
-----------

Symfony YAML is a PHP library that parses YAML strings and converts them to
PHP arrays. It can also converts PHP arrays to YAML strings.

[YAML](http://www.yaml.org/), YAML Ain't Markup Language, is a human friendly
data serialization standard for all programming languages. YAML is a great
format for your configuration files. YAML files are as expressive as XML files
and as readable as INI files.

### Easy to use

There is only one archive to download, and you are ready to go. No
configuration, No installation. Drop the files in a directory and start using
it today in your projects.

### Open-Source

Released under the MIT license, you are free to do whatever you want, even in
a commercial environment. You are also encouraged to contribute.


### Used by popular Projects

Symfony YAML was initially released as part of the symfony framework, one of
the most popular PHP web framework. It is also embedded in other popular
projects like PHPUnit or Doctrine.

### Documented

Symfony YAML is fully documented, with a dedicated online book, and of course
a full API documentation.

### Fast

One of the goal of Symfony YAML is to find the right balance between speed and
features. It supports just the needed feature to handle configuration files.

### Unit tested

The library is fully unit-tested. With more than 400 unit tests, the library
is stable and is already used in large projects.

### Real Parser

It sports a real parser and is able to parse a large subset of the YAML
specification, for all your configuration needs. It also means that the parser
is pretty robust, easy to understand, and simple enough to extend.

### Clear error messages

Whenever you have a syntax problem with your YAML files, the library outputs a
helpful message with the filename and the line number where the problem
occurred. It eases the debugging a lot.

### Dump support

It is also able to dump PHP arrays to YAML with object support, and inline
level configuration for pretty outputs.

### Types Support

It supports most of the YAML built-in types like dates, integers, octals,
booleans, and much more...


### Full merge key support

Full support for references, aliases, and full merge key. Don't repeat
yourself by referencing common configuration bits.

### PHP Embedding

YAML files are dynamic. By embedding PHP code inside a YAML file, you have
even more power for your configuration files.

Installation
------------

Symfony YAML can be installed by downloading the source code as a
[tar](http://github.com/fabpot/yaml/tarball/master) archive or a
[zip](http://github.com/fabpot/yaml/zipball/master) one.

To stay up-to-date, you can also use the official Subversion
[repository](http://svn.symfony-project.com/components/yaml/).

If you are a Git user, there is an official
[mirror](http://github.com/fabpot/yaml), which is updated every 10 minutes.

If you prefer to install the component globally on your machine, you can use
the symfony [PEAR](http://pear.symfony-project.com/) channel server.

Support
-------

Support questions and enhancements can be discussed on the
[mailing-list](http://groups.google.com/group/symfony-components).

If you find a bug, you can create a ticket at the symfony
[trac](http://trac.symfony-project.org/newticket) under the *YAML* component.

License
-------

The Symfony YAML component is licensed under the *MIT license*:

>Copyright (c) 2008-2009 Fabien Potencier
>
>Permission is hereby granted, free of charge, to any person obtaining a copy
>of this software and associated documentation files (the "Software"), to deal
>in the Software without restriction, including without limitation the rights
>to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
>copies of the Software, and to permit persons to whom the Software is furnished
>to do so, subject to the following conditions:
>
>The above copyright notice and this permission notice shall be included in all
>copies or substantial portions of the Software.
>
>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
>IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
>FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
>AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
>LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
>OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
>THE SOFTWARE.
