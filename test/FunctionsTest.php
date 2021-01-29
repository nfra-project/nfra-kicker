<?php



class FunctionsTest extends \PHPUnit\Framework\TestCase
{


    public function testAbsoluteToRelativeFileName()
    {
        $this->assertEquals("some/path", absolute_to_relative_uri("/tmp/some/path", "/tmp"));
        $this->assertEquals("some/path", absolute_to_relative_uri("/tmp//some/path", "/tmp"));
        $this->assertEquals("some/path", absolute_to_relative_uri("/tmp/some/path", "/tmp/"));
    }
}