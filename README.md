# Spaghettify

Spaghettify is a PHP 7, PostgreSQL RESTful web platform with built-in administration, based on <a target="_blank" href="https://www.slimframework.com/">Slim Framework</a>.  

INSTALLATION  

Clone the repo  
git clone https://github.com/it-all/Spaghettify  

Install Requirements  
$ cd Spaghettify  
$ composer install  

Create a PostgreSQL database for this project and import spaghettify.postgres.sql (top level) ie:  
 - $ psql -U postgres
 - postgres=# create role mydbname with login; (creating the role with the same name as the database name allows easy psql access)
 - postgres=# alter role mydbname with encrypted password 'mypassword';
 - postgres=# create database mydbname with owner mydbname;
 - $ psql -U mydbname < /path/to/spaghettify.postgres.sql  
  
Create your environmental variables by copying src/config/env-sample.php to src/config/env.php . Be sure to set the database connection. Note that a .gitignore file with env.php is included in the config directory to help prevent uploading your sensitive information to the web.  
$ cp src/config/env-sample.php src/config/env.php 
  
Create a local site with src/public as the root directory (default web server is Apache w/ .htaccess in src/public). Set your error log to /path/to/project/storage/logs/apacheErrors.log if you so desire. Remember to restart apache if necessary.  You will probably have to:  
$ chmod 777 storage/sessions  
$ chmod 777 storage/cache/twig  
  
Browse to your local site. You should see the Spaghettify home page with a link to Login. When logged in, this link changes to Admin. The initial username / password = owner / ownerownerowner.  
  
If you want to use Gulp for CSS and/or JS preprocessing  
~$ cd /path/to/project  
~$ npm init  
~$ cd cssJsBuildTool  
~$ npm install gulp gulp-beepbeep babel-core babel-loader --save-dev  
~$ npm install gulp-plumber gulp-sourcemaps gulp-sass gulp-autoprefixer gulp-cssnano gulp-concat gulp-uglify gulp-babel --save-dev  
~$ gulp watch  
See: https://travismaynard.com/writing/getting-started-with-gulp for further information.  

FEATURES  
<a target="_blank" href="https://postgresql.org">PostGreSQL Database</a> Integration  
MVC Structure  
<a href="#eh">Custom Error Handling</a>  
Emailing with <a target="_blank" href="https://github.com/PHPMailer/PHPMailer">PHPMailer</a>    
<a href="#se">Database Logging of system events and errors</a>  
<a href="#errLog">Logging of PHP Errors with Stack Trace</a>  
<a href="#csrf">CSRF Checking</a>  
<a href="https://twig.symfony.com/">Twig</a> Templates     
HTML Form Generation using <a target="_blank" href="https://github.com/it-all/FormFormer">FormFormer</a>   
Data Validation with <a target="_blank" href="https://github.com/vlucas/valitron">Valitron</a> (NOTE: If you are comparing floating-point numbers with min/max validators, you should install the PHP <a target="_blank" href="http://php.net/manual/en/book.bc.php">BCMath extension</a> for greater accuracy and reliability. The extension is not required for Valitron to work, but Valitron will use it if available, and it is highly recommended.)  
<a href="#crud">CRUD for Single Database Tables</a>  
<a href="#authe">Authentication</a> (Log In/Out)  
Administrative Layout including <a target="_blank" href="#adminNav">Navigation</a>  
<a href="#autho">Authorization</a> (Permissions for Resource and Functionality Access)    
<a href="#xss">XSS Prevention</a>  

CODING NEW FUNCTIONALITY  
Create a new directory under Domain/Admin or Domain/Frontend and create a Model/View/Controller there as necessary. Model these files after existing functionality such as Domain/Admin/Marketing/Testimonials (single database table functionality so uses SingleTable files) or Domain/Admin/Administrators (joined database tables so mostly custom code).  
Define a new global constant for the route name in config/config.php  
Add the route(s) in config/slim3/routes.php.  
If authorization is required at a resource or functionality level, add them to the 'administratorMinimumPermissions' key in config/config.php, then add AuthorizationMiddleware to the route for resource authorization in config/slim3/routes.php.  
If this is new admin functionality, you can add a link to it in the admin nav by editing Domain/Admin/NavAdmin.php. 

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
Certain events such as logging in, logging out, inserting, updating, and deleting database records are automatically logged into the system_events table. You can choose other events to insert as you write your application. For usage examples and help, search "systemEvents->insert" and see SystemEventsModel.php. Note that PHP errors are also logged to the system_events table by default (this can be turned off in config.php).

<a name="errLog">PHP Error Log</a>  
PHP Errors with stack trace are logged to the file set in config['storage']['logs']['pathPhpErrors']  
  
<a name="csrf">CSRF</a>   
The <a href="https://github.com/slimphp/Slim-Csrf" target="_blank">Slim Framework CSRF</a> protection middleware is used to check CSRF form fields. The CSRF key/value generators are added to the container for form field creation. They are also made available to Twig. A failure is logged to system_events as an error, the user's session is unset, and the user is redirected to the (frontend) homepage with an error message.

<a name="crud">Single Table CRUD</a>  
Single Table CRUD is like a quick and dirty ORM for single database tables, which is not meant to be complete, in that many data types and many constraints are not mapped. The Testimonials section in the admin under Marketing is there for an example of a database table which uses CRUD. Your application's views and controllers can extend the AdminCrudView and CrudController to take advantage of its functionality (see AdministratorsView and AdministratorsController for examples of this).

<a name="authe">Authentication</a>  
Admin pages are protected through authenticated sessions.

<a name="adminNav">Admin Nav</a>  
See NavAdmin.php.

<a name="autho">Authorization</a>  
Admin pages and functionality can be protected against unauthorized use based on administrative roles. Resource and functionality access is defined in config.php in the 'administratorMinimumPermissions' array key based on the role and is set in routes.php on resources as necessary, in NavAdmin to determine whether or not to display navigation options, and in views and controllers as necessary to grant or limit functionality access. Authorization failures result in alerts being written to the system_events table and the user redirected to the admin homepage with a red alert message displayed.

<a name="xss">XSS Prevention</a>  
The appropriate <a target="_blank" href="https://twig.sensiolabs.org/doc/2.x/filters/escape.html" target="_blank">Twig escape filter</a> are used for any user-input data* that is output through Twig. Note that Twig defaults to autoescape 'html' in the autoescape environment variable: https://twig.sensiolabs.org/api/2.x/Twig_Environment.html

protectXSS() or arrayProtectRecursive() should be called for any user-input data* that is output into HTML independent of Twig (currently there is none).

*Note this includes database data that has been input by any user, including through the admin

Miscellaneous Instructions  

To add a form to a Twig template:  
Be sure to include the csrf fields:  
&lt;input type="hidden" name="{{ csrf['tokenNameKey'] }}" value="{{ csrf['tokenName'] }}"&gt;  
&lt;input type="hidden" name="{{ csrf['tokenValueKey'] }}" value="{{ csrf['tokenValue'] }}"&gt;  

To print debugging info in admin pages:  
Send a 'debug' variable to twig ie:   
return $this->view->render($response, 'admin/lists/administratorsList.twig',['debug' => arrayWalkToStringRecursive($_SESSION)]);  
This is because the html main page content is set to 100% height, and simply doing a var_dump or echo can cause an unreadable display of the content.  

===========================================================Thank you.