Configuration reference
=======================

The application configuration is located in `app/config/services.yml`, under the `parameters` section.
If you want to use the default values, you can proceed to the Usage Instructions.
 
If not, you can tweak the values below:

queue.process.timeout
---------------------

Describes the maximum amount of time, in seconds, the manager will wait for the callback return before killing the 
process. 

**Default: 30**

queue.interval
--------------

Describes the interval, in seconds, to check the process result, to start new processes and to process internal signals. 
The higher the value, the less CPU it uses. The lower the value, the more responsive the Queue Manager will be, but the
higher the CPU usage.

If set to **0**, the queue manager will respond in realtime, but will eat up your CPU resources. This is not advisable.

**Default: 0.5**