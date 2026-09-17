<?php
  // DB Params
  // Docker/env override support (added for docker-compose wiring): each
  // constant falls back to the original hardcoded default when no
  // environment variable is set, so behavior outside Docker is unchanged.
  define("DB_HOST", getenv('DB_HOST') ?: "localhost");
  define("DB_USER", getenv('DB_USER') ?: "root");
  define("DB_PASS", getenv('DB_PASS') ?: "");
  define("DB_NAME", getenv('DB_NAME') ?: "rest-api-mvc-php");
  define('CHARSET', getenv('DB_CHARSET') ?: 'utf8');
  define('SESSION_TIME', (int) (getenv('SESSION_TIME') ?: 1800));

  // App Root
  define('APPROOT', dirname(dirname(__FILE__)));
  // URL Root
  define('URLROOT', getenv('URL_ROOT') ?: 'http://rest-api-mvc-php/api');
  // Site Name
  define('SITENAME', getenv('SITE_NAME') ?: 'rest-api-mvc-php');
  define('JWT_SECCRET_KEY', getenv('JWT_SECRET_KEY') ?: 'rest-api-mvc-php123!@#!$!@546asda');

