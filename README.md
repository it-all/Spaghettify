# Spaghettify

A PHP 7, PostgreSQL Platform Built on Slim Framework

CURRENTLY UNDER DEVELOPMENT

INSTALLATION
Create a PostgreSQL database for this project.
 - ie ~$ psql -U postgres
 - postgres=# create role testdb with login;
 - postgres=# create database testdb with owner testdb;
 - ~$ psql -U testdb < /path/to/spaghettify.postgres.sql

Copy Src/config/env-sample.php to Src/config/env.php and edit database info and other fields as necessary

Create a web server (default is Apache w/ .htaccess in Src/public) and point it to Src/public

You should see the Spaghettify home page with a link to Login. When logged in, this link changes to Admin. The initial username / password = owner / ownerownerowner
 

FEATURES
PostGreSQL Database
MVC Structure
Custom Error Handling
Emailing with PHPMailer
Logging Events and Errors into the Database
Error Logging into a log file
CSRF Checking
Twig Templates
HTML Form Generation using It_All/FormFormer
CRUD for Single Database Tables (In Development) (NOTE: CRUD is like a quick and dirty ORM for single tables which is not meant to be complete, in that many data types and many constraints will not be mapped). The Testimonials section in the admin under Marketing is there for an example of a database table which uses CRUD.
Data Validation with [Valitron] (https://github.com/vlucas/valitron) (NOTE: If you are comparing floating-point numbers with min/max validators, you should install the BCMath extension for greater accuracy and reliability. The extension is not required for Valitron to work, but Valitron will use it if available, and it is highly recommended.)
Authentication
Administrative Layout including Navigation
Authorization

Details
CSRF: 2 pieces of middleware are installed to check CSRF form fields and display and log errors when necessary. The CSRF key/value generators are added to the container for form field creation. They are also made available to Twig. 