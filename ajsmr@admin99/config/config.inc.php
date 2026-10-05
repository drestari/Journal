<?php
/*
 * AJSMR legacy configuration compatibility file.
 * Replace the password placeholder with the current password for
 * the cPanel MySQL user "ajsmrjournal".
 */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

$Config = array();
$Config['dbServer']   = getenv('MAIN_DB_HOST') ?: '127.0.0.1';
$Config['dbUser']     = getenv('MAIN_DB_USER') ?: 'root';
$Config['dbPassword'] = getenv('MAIN_DB_PASS') ?: '';
$Config['dbName']     = getenv('MAIN_DB_NAME') ?: 'ajsmrjournal';
$Config['siteName']   = 'Ajsmrjournal';
$Config['httppath']   = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'ajsmrjournal.com') . '/';
$Config['imagespath'] = '../images/';

mysqli_report(MYSQLI_REPORT_OFF);
$Config['link'] = @mysqli_connect(
    $Config['dbServer'], $Config['dbUser'],
    $Config['dbPassword'], $Config['dbName']
);
if (!$Config['link']) {
    $Config['link'] = @mysqli_connect('127.0.0.1', 'root', getenv('MAIN_DB_PASS') ?: '', 'ajsmrjournal', 3306);
}
if (!$Config['link']) {
    $Config['link'] = @mysqli_connect('localhost', 'root', getenv('MAIN_DB_PASS') ?: '', 'ajsmrjournal');
}
$Config['db'] = (bool)$Config['link'];
if ($Config['link']) { mysqli_set_charset($Config['link'], 'utf8mb4'); }

if (!function_exists('mysql_query')) {
    function mysql_query($query, $link_identifier = null) {
        global $Config; $link = $link_identifier ?: ($Config['link'] ?? null);
        return $link ? mysqli_query($link, $query) : false;
    }
}
if (!function_exists('mysql_fetch_array')) {
    function mysql_fetch_array($result, $result_type = MYSQLI_BOTH) { return mysqli_fetch_array($result, $result_type); }
}
if (!function_exists('mysql_fetch_assoc')) {
    function mysql_fetch_assoc($result) { return mysqli_fetch_assoc($result); }
}
if (!function_exists('mysql_fetch_object')) {
    function mysql_fetch_object($result) { return mysqli_fetch_object($result); }
}
if (!function_exists('mysql_num_rows')) {
    function mysql_num_rows($result) { return mysqli_num_rows($result); }
}
if (!function_exists('mysql_real_escape_string')) {
    function mysql_real_escape_string($string, $link_identifier = null) {
        global $Config; $link = $link_identifier ?: ($Config['link'] ?? null);
        return $link ? mysqli_real_escape_string($link, $string) : addslashes($string);
    }
}
if (!function_exists('mysql_error')) {
    function mysql_error($link_identifier = null) {
        global $Config; $link = $link_identifier ?: ($Config['link'] ?? null);
        return $link ? mysqli_error($link) : '';
    }
}
if (!function_exists('mysql_insert_id')) {
    function mysql_insert_id($link_identifier = null) {
        global $Config; $link = $link_identifier ?: ($Config['link'] ?? null);
        return $link ? mysqli_insert_id($link) : 0;
    }
}
if (!function_exists('mysql_select_db')) {
    function mysql_select_db($database_name, $link_identifier = null) {
        global $Config; $link = $link_identifier ?: ($Config['link'] ?? null);
        return $link ? mysqli_select_db($link, $database_name) : false;
    }
}
if (!function_exists('mysql_connect')) {
    function mysql_connect($server = null, $username = null, $password = null, $new_link = false, $client_flags = 0) {
        global $Config;
        return @mysqli_connect(
            $server ?: $Config['dbServer'],
            $username ?: $Config['dbUser'],
            $password ?: $Config['dbPassword']
        );
    }
}
?>