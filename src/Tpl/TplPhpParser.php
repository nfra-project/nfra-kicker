<?php


namespace Kick\Tpl;


class TplPhpParser
{

    public $inputFile;
    public function __construct(string $inputFile)
    {
        $this->inputFile = $inputFile;
    }

    public function parseFile($environment) : string
    {
        $output = "";

        // Use the current interpreter for evaling
        exec("/kickstart/bin/_kick_php -f '" . escapeshellarg($this->inputFile) . "' 2>&1", $ret, $retVar);
        if ($retVar !== 0)
            throw new \Exception( implode("", $ret));

        return implode("\n", $ret);
    }
}