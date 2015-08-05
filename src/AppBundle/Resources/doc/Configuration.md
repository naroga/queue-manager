Configuration reference
=======================

The application configuration is located in `app/config/services.yml`, under the `parameters` section.
If you want to use the default values, you can proceed to the [Usage Instructions](/src/AppBundle/Resources/doc/Usage.md).
 
If not, you can tweak the values below:

queue.interval
--------------

Describes the interval, in seconds, to check the process result, to start new processes and to process internal signals. 
The higher the value, the less CPU it uses. The lower the value, the more responsive the Queue Manager will be, but the
higher the CPU usage.

If set to **0**, the queue manager will respond in realtime, but will eat up your CPU resources. This is not advisable.

**Type: float|int**

**Default: 0.5**

queue.process.discardOnFailure
------------------------------

Determines if the process should be discarded if it doesn't timeout and returns a failure message.

**Type: boolean**

**Default: true**


queue.process.timeout
---------------------

Describes the maximum amount of time, in seconds, the manager will wait for the callback return before killing the 
process. 

**Type: int**

**Default: 30**

queue.process.tries
-------------------

Determines the number of times a process will be dispatched again before being discarded by the manager.

If `queue.process.discardOnFailure` is set to `true`, the manager will only retry the process
if it times out. If it returns a failure message, it will not be retried.

**Type: int**

**Default: 3**

queue.workers
-------------

Amount of workers. Increase this value to dispatch more requests asynchronously.

**Type: int**

**Default: 5**