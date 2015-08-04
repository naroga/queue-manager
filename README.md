Queue Manager
=============

Requirements
------------

This package is only suited for UNIX or OSX distributions.

Even though it is not offically supported, you might succeed in running this package 
on a Windows machine if you have [CYGWIN](https://www.cygwin.com/) installed (as long as 
you keep commands such as 'ps' in the PATH global variable.

It requires PHP 5.6+ with the php5-curl module enabled.

It also requires writing privileges in app/cache and app/logs.

Installing
----------

To install this package, use [composer](https://getcomposer.org):

    composer require naroga/queue-manager dev-master

This will install the package with its dependencies.

Configuration
-------------

To configure this package properly, see the [Configuration Reference](/src/AppBundle/Resources/doc/Configuration.md).
You might want to skip this section, as the default configuration works just fine. Tweak the parameters
to improve your server responsiveness or to increase the number of workers.

Usage
-----

If you want to get started on using this package, you can proceed to the Usage Instructions.