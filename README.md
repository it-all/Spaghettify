# Spaghettify

A PHP 7, PostgreSQL Platform Built on Slim Framework

CURRENTLY UNDER DEVELOPMENT

next step: look into form field objects for flexibility

INSTALLATION
Create a PostgreSQL database for this project.
 - ie ~$ psql -U postgres
 - postgres=# create role testdb with login;
 - postgres=# create database testdb with owner testdb;
 - ~$ psql -U testdb < /path/to/spaghettify.postgres.sql

Copy Src/config/env-sample.php to Src/config/env.php and edit database info and other fields as necessary

Create a web server (default is Apache w/ .htaccess in Src/public) and point it to Src/public

You should see the Spaghettify home page with a link to Login. When logged in, this link changes to Admin. You can login with owner / ownerownerowner
 

FEATURES
MVC Structure
Custom Error Handling
Emailing with PHPMailer
Logging Events with Monologger
Error Logging
CSRF Checking
Twig Templates
CRUD for Single Database Tables (In Development)
Data Validation with [Valitron] (https://github.com/vlucas/valitron) (NOTE: If you are comparing floating-point numbers with min/max validators, you should install the BCMath extension for greater accuracy and reliability. The extension is not required for Valitron to work, but Valitron will use it if available, and it is highly recommended.)
Authentication
Administrative Layout including Navigation
Authorization
