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
Authentication
Authorization
Custom Error Handling
Emailing with PHPMailer
Logging Events with Monologger
Error Logging
CSRF Checking
MVC Structure
Twig Templates
CRUD for Single Database Tables (In Development)

