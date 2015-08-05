Queue Manager
=============

Requirements
------------

This package is only suited for UNIX or OSX distributions.

Even though it is not offically supported, you might succeed in running this package 
on a Windows machine if you have [CYGWIN](https://www.cygwin.com/) installed (as long as 
you keep commands such as 'ps' in the PATH global variable.

It requires PHP 5.6+ with the following modules enabled: `php5-curl`, `php5-memcache` 
(not to mistake with `php5-memcached`). Writing privileges in app/cache and app/logs are also required.

Even though it fires up process asynchronously, it does **not** need any additional modules (like pthreads).

Installing
----------

To install this package, use [composer](https://getcomposer.org):

    composer create-project naroga/queue-manager -s dev

This will install the package with its dependencies.

You should also create a VirtualHost with the root directory set to `/web/`.

Configuration
-------------

To configure this package properly, see the [Configuration Reference](/src/AppBundle/Resources/doc/Configuration.md).
You might want to skip this section, as the default configuration should work just fine. Tweak the parameters
to improve your server responsiveness, save CPU/memory or to increase the number of workers.

Usage
-----

If you want to get started on using this package, 
you can proceed to the [Usage Instructions](/src/AppBundle/Resources/doc/Usage.md).

License
-------

Naroga/QueueManager is released under the MIT License. See the bundled LICENSE file for details.