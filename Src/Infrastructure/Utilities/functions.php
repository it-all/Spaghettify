<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Utilities;

function getRouteName(bool $isAdmin = true, string $routePrefix = null, string $routeType = null, string $resourceType = null)
{
    $routeName = '';

    if ($isAdmin) {
        $routeName .= ROUTEPREFIX_ADMIN;
    }

    if ($routePrefix !== null) {
        $routeName .= '.' . $routePrefix;
    }

    if ($resourceType != null) {
        $validActionTypes = ['put', 'post'];
        if (!in_array($resourceType, $validActionTypes)) {
            throw new \Exception("Invalid resource type $resourceType");
        }

        $routeName .= '.' . $resourceType;
    }

    if ($routeType !== null) {
        $validRouteTypes = ['index', 'insert', 'update', 'delete'];
        if (!in_array($routeType, $validRouteTypes)) {
            throw new \Exception("Invalid route type $routeType");
        }

        $routeName .= '.' . $routeType;
    }

    return $routeName;
}


/**
 * protects array from xss by changing actual array values to escaped characters
 * @param array $arr
 */
function arrayProtectRecursive(array &$arr)
{
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            arrayProtectRecursive($arr[$k]);
        } else {
            $arr[$k] = protectXSS($v);
        }
    }
}

/**
 * use for inputs that are to be displayed in HTML
 * including values stored in the database
 * @param string $input
 * @return string
 */
function protectXSS(string $input)
{
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * determines if a $sessionId id is valid.
 * @param $session_id
 * @param bool optional $isEmptyIdValid
 * @return bool
 */
function sessionValidId($sessionId, $isEmptyIdValid = true): bool
{
    if ($isEmptyIdValid && strlen($sessionId) == 0) { // if blank, there is no session id
        return true;
    }
    return preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $sessionId) > 0;
}

/**
 * determines if current page is https
 * @return bool
 */
function isHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || intval($_SERVER['SERVER_PORT']) === 443;
}

/**
 * determines if url host name begins with 'www'
 * @return bool
 */
function isWww(): bool
{
    return (strtolower(substr($_SERVER['SERVER_NAME'], 0, 3)) == 'www');
}

/**
 * Returns true if the current script is running from the command line (ie, CLI).
 */
function isRunningFromCommandLine(): bool
{
    return php_sapi_name() == 'cli';
}

function getBaseUrl()
{
    global $config;
    $baseUrl = "https://";
    if ($config['domainUseWww']) {
        $baseUrl .= "www.";
    }
    $baseUrl .= $_SERVER['SERVER_NAME'];
    return $baseUrl;
}

function getCurrentUri(bool $includeQueryString = true): string
{
    $uri = $_SERVER['REQUEST_URI'];
    if ($includeQueryString && isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
        $uri .= "?" . $_SERVER['QUERY_STRING'];
    }

    return $uri;
}

// if called with no args, redirects to current URI with proper protocol, www or not based on config, and query string
function redirect(string $toURI = null)
{
    if (is_null($toURI)) {
        $toURI = getCurrentUri(true, false);
    }

    // add / if nec
    if (substr($toURI, 0, 1) != "/") {
        $toURI = "/" . $toURI;
    }

    $to = getBaseUrl() . $toURI;
    header("Location: $to");
    exit();
}

/**
 * gets extension of a fileName
 */
function getFileExt(string $fileName)
{
    if (!strstr($fileName, '.')) {
        return false;
    }
    $fileNameParts = explode('.', $fileName);
    return $fileNameParts[count($fileNameParts) - 1];
}

/**
 * converts array to string
 * @param array $arr
 * @param int $level
 * @return string
 */
function arrayWalkToStringRecursive(array $arr, int $level = 0, int $maxLevel = 1000): string
{
    $out = "";
    $tabs = " ";
    for ($i = 0; $i < $level; $i++) {
        $tabs .= " ^"; // use ^ to denote another level
    }
    foreach ($arr as $k => $v) {
        $out .= "$tabs$k: ";
        if (is_object($v)) {
            $out .= 'object type: '.get_class($v);
        } elseif (is_array($v)) {
            $newLevel = $level + 1;
            if ($newLevel > $maxLevel) {
                $out .= ' array, too deep, quitting';
            } else {
                $out .= arrayWalkToStringRecursive($v, $newLevel);
            }
        } else {
            $out .= (string)$v;
        }
    }
    return $out;
}

function printPreArray(array $in, bool $die = false)
{
    echo "<pre>";
    print_r($in);
    echo "</pre>";
    if ($die) {
        die('Array print and die.');
    }
}

/**
 * @param $var_array
 * @return mixed
 * can be used to set multiple vars in one cookie
 */
function buildCookie($var_array)
{
    if (is_array($var_array)) {
        foreach ($var_array as $index => $data) {
            $out .= ($data != "") ? $index . "=" . $data . "|" : "";
        }
    }
    return rtrim($out, "|");
}

function breakCookie($cookie_string)
{
    $array = explode("|", $cookie_string);
    foreach ($array as $i => $stuff) {
        $stuff = explode("=", $stuff);
        $array[$stuff[0]] = $stuff[1];
        unset($array[$i]);
    }
    return $array;
}

/**
 * @param string $cookieName
 * https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet
 */
function deleteCookie(string $cookieName)
{
    setcookie($cookieName, "", 1);
    setcookie($cookieName, false);
    unset($_COOKIE[$cookieName]);
}

/**
 * @param $d1
 * @param $d2 if null compare to today
 * d1, d2 already verified to be isDbDate()
 * @return int
 */
function dbDateCompare($d1, $d2 = null): int
{
    // inputs 2 dates (Y-m-d) and returns d1 - d2 in seconds
    if ($d2 === null) {
        $d2 = date('Y-m-d');
    }
    return convertDateMktime($d1) - convertDateMktime($d2);
}

/**
 * @param $dbDate already been verified to be isDbDate()
 * @return int
 */
function convertDateMktime($dbDate): int
{
    return mktime(0, 0, 0, substr($dbDate, 5, 2), substr($dbDate, 8, 2), substr($dbDate, 0, 4));
}

function convertDbDateDbTimestamp($dbDate, $time = 'end')
{
    if ($time == 'end') {
        return "$dbDate 23:59:59.999999";
    } else {
        return "$dbDate 0:0:0";
    }
}
