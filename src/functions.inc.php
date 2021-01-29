<?php


/**
 * Safely return the contents of a key of array structure
 *
 * @param       $input
 * @param array $keys
 * @param null  $default
 *
 * @return null
 */
function access ($input, array $keys, $default=null)
{
    $cur =& $input;
    foreach ($keys as $key) {
        if ( ! isset ($cur[$key]))
            return $default;
        $cur =& $cur[$key];
    }
    return $cur;
}

/**
 * @param string $filename
 * @throws InvalidArgumentException
 * @return mixed
 */
function kicker_yaml_parse_file(string $filename)
{
    ini_set("yaml.decode_php", "0");
    $ret = \Symfony\Component\Yaml\Yaml::parseFile($filename);
    #$ret = yaml_parse_file($filename);
    if ($ret === false) {
        $err = error_get_last();
        throw new InvalidArgumentException(
            "Cannot parse yaml-file: '{$filename}' Error: {$err["message"]}",
            0
        );
    }
    return $ret;
}


function absolute_to_relative_uri(string $path, string $rootPath = "/")
{
    if (substr($path, 0, strlen($rootPath)) !== $rootPath)
        throw new InvalidArgumentException("Path '$path' is not a child of root-path '$rootPath'");
    $path = substr($path, strlen($rootPath));
    while(true) {
        if (substr($path, 0, 1) !== "/")
            return $path;
        $path = substr($path, 1);
    }
}

