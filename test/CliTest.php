<?php


class CliTest extends \PHPUnit\Framework\TestCase
{


    public function testWriteConfigFile() {
        phore_exec("/opt/bin/kick write_config_file");
        $this->assertEquals(file_get_contents(__DIR__ . "/mock/test.out.txt"), file_get_contents("/tmp/test.out.txt"));
    }

}