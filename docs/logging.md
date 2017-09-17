Logging

Stored in $config['storage']['pathLogs']

apacheErrors.log

phpErrors.log 
written to in ErrorHandler::handleError()

System Events
inserted to system_events table (search systemEvents->insert for usage example and see SystemEventsModel.php)

Note that PHP Errors are both logged to phpErrors.log and inserted to system_events.