# Spaghettify

A PHP 7, PostgreSQL Platform Built on Slim Framework

CURRENTLY UNDER DEVELOPMENT

INSTALLATION
Create a PostgreSQL database for this project and import spaghettify.postgres.sql (top level)
 - ie ~$ psql -U postgres
 - postgres=# create role mydbname with login;
 - postgres=# create database mydbname with owner mydbname;
 - ~$ psql -U mydbname < /path/to/spaghettify.postgres.sql

Copy Src/config/env-sample.php to Src/config/env.php and edit database info and other fields as necessary

Create a web server (default is Apache w/ .htaccess in Src/public) and point it to Src/public

You should see the Spaghettify home page with a link to Login. When logged in, this link changes to Admin. The initial username / password = owner / ownerownerowner
 

FEATURES
PostGreSQL Database (https://postgresql.org) Integration  
MVC Structure  
<a href="#eh">Custom Error Handling</a>  
Emailing with PHPMailer (https://github.com/PHPMailer/PHPMailer)  
<a href="#se">Database Logging of system events and errors  
Logging of PHP Errors with Stack Trace  
<a href="#csrf">CSRF Checking</a>  
Twig Templates (https://twig.symfony.com/)    
HTML Form Generation using It_All/FormFormer (https://github.com/it-all/FormFormer)  
Data Validation with [Valitron] (https://github.com/vlucas/valitron) (NOTE: If you are comparing floating-point numbers with min/max validators, you should install the PHP BCMath extension (http://php.net/manual/en/book.bc.php) for greater accuracy and reliability. The extension is not required for Valitron to work, but Valitron will use it if available, and it is highly recommended.)  
<a href="#crud">CRUD for Single Database Tables</a> 
<a href="#authe">Authentication</a> (Log In/Out)  
Administrative Layout including <a href="#adminNav">Navigation</a>  
<a href="#autho">Authorization</a> (Permissions for Resource and Functionality Access)    
<a href="#xss">Preventing XSS</a>


<a name="eh">Error Handling</a>
  
Reporting Methods:

1. Database Log
    If the database and system events services have been set as properties in the ErrorHandler class, all errors are logged to the system_events table. The stack trace is not provided, instead, a reference is made to view the log file for complete details.
    
2. File Log
    All error details are logged to $config['storage']['logs']['pathPhpErrors'].

3. Echo
    Live Servers*
    Error details are never echoed, rather, a generic error message is echoed. For fatal errors, this message is set in $config['errors']['fatalMessage'].

    Dev Servers*
    Error details are echoed if $config['errors']['echoDev'] is true
    
4. Email
    For security, error details are never emailed.

    Live Servers
    All errors cause generic error notifications to be emailed to $config['errors']['emailTo'].
    
    Dev Servers*
    Generic error notifications are emailed to $config['errors']['emailTo'] if $config['errors']['emailDev'] is true.
    
    
* $config['isLive'] boolean from config/env.php determines whether site is running from live or dev server.

See ErrorHandler.php for further info.

<a name="se">System Event Database Logging</a>  
Certain events such as logging in, logging out, inserting, updating, and deleting database records are automatically logged into the system_events table. You can choose other events to insert as you write your application. For usage examples and help, search "systemEvents->insert" and see SystemEventsModel.php. Note that PHP errors are also logged to the system_events table by default (which can be turned off in config.php).

<a name="csrf">CSRF</a>  
The Slim Framework CSRF protection middleware (https://github.com/slimphp/Slim-Csrf) is used to check CSRF form fields. The CSRF key/value generators are added to the container for form field creation. They are also made available to Twig. A failure is logged to system_events as an error, the user's session is unset, and the user is redirected to the (frontend) homepage with an error message.

<a name="crud">CRUD</a>
CRUD is like a quick and dirty ORM for single database tables, which is not meant to be complete, in that many data types and many constraints are not mapped. The Testimonials section in the admin under Marketing is there for an example of a database table which uses CRUD. Your application's views and controllers can extend the AdminCrudView and CrudController to take advantage of its functionality (see AdminsView and AdminsController for examples of this).

<a name="authe">Authentication</a>
Admin pages are protected through authenticated sessions.

<a name="adminNav">Admin Nav</a>
See NavAdmin.php.

<a name="autho">Authorization</a>
Admin pages and functionality can be protected against unauthorized use based on administrative roles. Resource and functionality access is defined in config.php in the 'adminMinimumPermissions' array key based on the role and is set in routes.php on resources as necessary, in NavAdmin to determine whether or not to display navigation options, and in views and controllers as necessary to grant or limit functionality access. Authorization failures result in alerts being written to the system_events table and the user redirected to the admin homepage with a red alert message displayed.

<a name="xss">Preventing XSS</a>
The appropriate <a href="https://twig.sensiolabs.org/doc/2.x/filters/escape.html" target="_blank">Twig escape filter</a> are used for any user-input data* that is output through Twig. Note that Twig defaults to autoescape 'html' in the autoescape environment variable: https://twig.sensiolabs.org/api/2.x/Twig_Environment.html

protectXSS() or arrayProtectRecursive() should be called for any user-input data* that is output into HTML independent of Twig (currently there is none).

*Note this includes database data that has been input by any user, including through the admin