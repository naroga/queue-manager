Usage Instructions
==================

Composition
-----------

This package has two separate but integrated services. 

The first one, the Queue Manager, should be started from the command line. It will be running in the background
firing up the runners and checking for new processes continously.

The second one, the HTTP restful api, works like a normal HTTP service. You'll need Apache or nginx,
with a virtual host set to the `/web/` directory.

The Queue Manager
-----------------

The Queue Manager is responsible for reading the process queue, starting the runners, and dispatching
all processes. It should be started from the command line, as below:

    $ php app/console naroga:queue:start
    
There are two distinct modes you can use to run the Queue Manager:

**1. Daemon Mode**

If you want the Queue Manager to run in the background as a Daemon service, you can append the `-d` or `--daemon` option
to the command above:

    $ php app/console naroga:queue:start -d
    
**2. Verbose Mode**

If you want additional information from the Queue Manager, such as warnings, you can append the `-v` or `--verbose`
option to the command above:

    $ php app/console naroga:queue:start -v

---
    
Obviously, you can combine both modes:

    $ php app/console naroga:queue:start -d -v
    
You can check the service status with the following command:

    $ php app/console naroga:queue:status
    
And you can stop the Queue Manager with `naroga:queue:stop`.

    $ php app/console naroga:queue:stop
    
You can check a full list of commands by running `php app/console` with no further arguments.