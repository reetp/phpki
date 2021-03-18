<?php

$PHP_SELF = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, "utf-8");

#
# Returns TRUE if browser is Internet Explorer.
#
function isIE()
{
    global $_SERVER;
    return strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE');
}

function isKonq()
{
    global $_SERVER;
    return strstr($_SERVER['HTTP_USER_AGENT'], 'Konqueror');
}

function isMoz()
{
    global $_SERVER;
    return strstr($_SERVER['HTTP_USER_AGENT'], 'Gecko');
}


#
# Force upload of specified file to browser.
#
function upload($source, $destination, $content_type = "application/octet-stream")
{
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Expires: -1");
#   header("Cache-Control: no-store, no-cache, must-revalidate");
#   header("Cache-Control: post-check=0, pre-check=0", false);
#   header("Pragma: no-cache");
    header("Content-Type: $content_type");

    if (is_array($source)) {
        $fsize = 0;
        foreach ($source as $f) {
            $fsize += filesize($f);
        }
    } else {
        $fsize = filesize($source);
    }

    header("Content-length: " . $fsize);
#        header("Content-Disposition: attachment; filename=\"" . $destination ."\"");
        header("Content-Disposition: filename=\"" . $destination ."\"");

    if (is_array($source)) {
        foreach ($source as $f) {
            $ret = readfile($f);
        }
    } else {
        $ret=readfile($source);
    }

#        $fd=fopen($source,'r');
#        fpassthru($fd);
#        fclose($fd);
}


#
# Returns a value from the GET/POST global array referenced
# by field name.  POST fields have precedence over GET fields.
# Quoting/Slashes are stripped if magic quotes gpc is on.
#
function gpvar($v)
{
    global $_GET, $_POST;
    $x = "";
    if (isset($_GET[$v])) {
        $x = $_GET[$v];
    }
    if (isset($_POST[$v])) {
        $x = $_POST[$v];
    }
    if (get_magic_quotes_gpc()) {
        $x = stripslashes($x);
    }
    return $x;
}


#
# Sort a two multidimensional array by one of it's columns
#
function csort($array, $column, $ascdec = SORT_ASC)
{

    if (sizeof($array) == 0) {
        return $array;
    }

    // Sort by digital date rather than text date
    if ($column == 'issued') {
        $column = "issuedSort";
    }
    if ($column == 'expires') {
        $column = 'expiresSort';
    }
    
    if ($column == 'status') {
        foreach ($array as $x) {
            $sortarr[]=$x[$column];
            $sortdate[] = $x['expiresSort'];
        }
        array_multisort($sortarr, $ascdec, $sortdate, SORT_ASC, $array);
    } else {
        foreach ($array as $x) {
            $sortarr[]=$x[$column];
        }
        array_multisort($sortarr, $ascdec, $array);
    }
    return $array;
}


#
# Returns a value suitable for display in the browser.
# Strips slashes if second argument is true.
#
function htvar($v, $strip = false)
{
    if ($strip) {
        return  htmlentities(stripslashes($v), 0, "UTF-8");
    } else {
        return  htmlentities($v, 0, "UTF-8");
    }
}


#
# Returns a value suitable for use as a shell argument.
# Strips slashes if magic quotes is on, surrounds
# provided strings with single-quotes and quotes any
# other dangerous characters.
#
function escshellarg($v, $strip = false)
{
    if ($strip) {
        return escapeshellarg(stripslashes($v));
    } else {
        return escapeshellarg($v);
    }
}


#
# Similar to escshellarg(), but doesn't surround provided
# string with single-quotes.
#
function escshellcmd($v, $strip = false)
{
    if ($strip) {
        return escapeshellcmd(stripslashes($v));
    } else {
        return escapeshellarg($v);
    }
}
    
#
# Recursively strips slashes from a string or array.
#
function stripslashes_array(&$a)
{
    if (is_array($a)) {
        foreach ($a as $k => $v) {
            my_stripslashes($a[$k]);
        }
    } else {
        $a = stripslashes($a);
    }
}


#
# Don't use this.
#
function undo_magic_quotes(&$a)
{
    if (get_magic_quotes_gpc()) {
        global $HTTP_POST_VARS, $HTTP_GET_VARS;

        foreach ($HTTP_POST_VARS as $k => $v) {
            stripslashes_array($HTTP_POST_VARS[$k]);
            global $$k;
            stripslashes_array($$k);
        }
        foreach ($HTTP_GET_VARS as $k => $v) {
            stripslashes_array($HTTP_GET_VARS[$k]);
            global $$k;
            stripslashes_array($$k);
        }
    }
}

#
# Returns TRUE if argument contains only alphabetic characters.
#
function is_alpha($v)
{
    #return (eregi('[^A-Z]',$v) ? false : true) ;
    #return (preg_match('/[^A-Z]'.'/i',$v,PCRE_CASELESS) ? false : true) ; # Replaced eregi() with preg_match()
    return (preg_match('/[^A-Z]/i', $v) ? false : true) ;
}

#
# Returns TRUE if argument contains only numeric characters.
#
function is_num($v)
{
    #return (eregi('[^0-9]',$v) ? false : true) ;
    return (preg_match('/[^0-9]/', $v) ? false : true) ; # Replaced eregi() with preg_match()
}

#
# Returns TRUE if argument contains only alphanumeric characters.
#
function is_alnum($v)
{
    #return (eregi('[^A-Z0-9]',$v) ? false : true) ;
    return (preg_match('/[^A-Z0-9]/i', $v) ? false : true) ; # Replaced eregi() with preg_match()
}

#
# Returns TRUE if argument is in proper e-mail address format.
#
function is_email($v)
{
    #return (eregi('^[^@ ]+\@[^@ ]+\.[A-Z]{2,4}$',$v) ? true : false);
    return (preg_match('/^[^@ ]+\@[^@ ]+\.[A-Z]{2,4}$'.'/i', $v) ? true : false); # Replaced eregi() with preg_match()
}

#
# Returns True if the given string is a IP address
#
function is_ip($ip = null)
{
    if (!$ip or strlen(trim($ip)) == 0) {
        return false;
    }
    $ip=trim($ip);
    if (preg_match("/^[0-9]{1,3}(.[0-9]{1,3}){3}$/", $ip)) {
        foreach (explode(".", $ip) as $block) {
            if ($block<0 || $block>255) {
                return false;
            }
        }
        return true;
    }
    return false;
}

#
# Returns True if the given string is a valid FQDN
#
function is_fqdn($FQDN)
{
    // remove leading wildcard characters if exist
    $FQDN = preg_replace('/^\*\./', '', $FQDN, 1);
    return (!empty($FQDN) && preg_match('/^(?=.{1,254}$)((?=[a-z0-9-]{1,63}\.)(xn--+)?[a-z0-9]+(-[a-z0-9]+)*\.)+(xn--+)?[a-z0-9]{2,63}$/i', $FQDN) > 0);
}

#
# Checks regexp in every element of an array, returns TRUE as soon
# as a match is found.
#

function preg_match_array($regexp, $arr)
{

    foreach ($arr as $elem) {
        #if (eregi($regexp,$elem))
        if (! preg_match('/^\/.*\/$/', $regexp)) { # if it doesn't begin and end with '/'
            $regexp = '/'.$regexp.'/'; # pad the $regexp with '/' to prepare for preg_match()
        }
        if (preg_match($regexp.'i', $elem)) { # Replaced eregi() with preg_match()
            return true;
        }
    }
    return false;
}
#
# Reads entire file into a string
# Same as file_get_contents in php >= 4.3.0
#
function my_file_get_contents($f)
{
    return implode('', file($f));
}

function getOSInformation()
{
    if (false == function_exists("shell_exec")) {
        return null;
    }
    $os = shell_exec('cat /etc/redhat-release');
    if (preg_match('/^SME Server/', $os)) {
        return true;
    } else {
        return null;
    }
}

# Used in setup
function flush_exec($command, $line_length = 200)
{
        $handle = popen("$command 2>&1", 'r');

        $line = '';
    while (! feof($handle)) {
            $chr = fread($handle, 1);
            $line .= $chr;
        if ($chr == "\n") {
                print str_replace("\n", "<br>\n", $line);
                $line = '';
                flush();
        } elseif (strlen($line) > $line_length) {
                print $line."<br>\n";
                $line = '';
                flush();
        }
    }
        print $line."<br>\n";
    flush();
    return;
}
