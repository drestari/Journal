<?php
/*
 * AJSMR legacy configuration compatibility file.
 * Replace the password placeholder with the current password for
 * the cPanel MySQL user "ajsmrjournal".
 */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

$Config = array();
$Config['dbServer']   = 'shareddb-g.hosting.stackcp.net';
$Config['dbUser']     = 'ajsmrjournal-3731a6db';
$Config['dbPassword'] = 'DV4z3wDax|=Q';
$Config['dbName']     = 'ajsmrjournal-3731a6db';
$Config['siteName']   = 'Ajsmrjournal';
$Config['httppath']   = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') . '/';
$Config['imagespath'] = '../images/';

mysqli_report(MYSQLI_REPORT_OFF);
$Config['link'] = @mysqli_connect(
    $Config['dbServer'], $Config['dbUser'],
    $Config['dbPassword'], $Config['dbName']
);
if (!$Config['link']) {
    $Config['link'] = @mysqli_connect('shareddb-g.hosting.stackcp.net', 'ajsmrjournal-3731a6db', '{7QSSrLm5_Fm', 'ajsmrjournal-3731a6db');
}
if (!$Config['link']) {
    $Config['link'] = @mysqli_connect('127.0.0.1', 'root', 'Srija@2005', 'ajsmrjournal', 3306);
}
if (!$Config['link']) {
    $Config['link'] = @mysqli_connect('localhost', 'root', 'Srija@2005', 'ajsmrjournal');
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