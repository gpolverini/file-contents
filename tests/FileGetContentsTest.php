<?php

namespace File;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use \APCu\Cache\APCUserCache;

/**
 * @author Gabriel Polverini <polverini.gabriel@gmail.com>
 *
 * @group FileGetContents
 */
class FileGetContentsTest extends TestCase
{
    const URL_TEST = 'https://mxusphls2.clarovideo.com/multimediav81/plataforma_vod/MP4/201806/MAH002699_full/MAH002699_full_SS_HLSFPS.ism/MAH002699_full_SS_HLSFPS.m3u8';
    protected $logger;

    public function setUp()
    {
        $this->logger = $this->prophesize('Psr\Log\LoggerInterface');
        $this->logger->info(Argument::any(), Argument::any())->willReturn('');
    }

    /**
     * @test
     */
    public function testGetInvalidArgument()
    {
        $file_get_contents = new FileGetContents($this->logger->reveal());

        $url = new \StdClass();
        try {
            $file_get_contents->get($url);
        } catch (\Exception $ex) {
            $this->assertTrue($ex instanceof InvalidArgumentException);
        }

        $url = 1;
        try {
            $file_get_contents->get($url);
        } catch (\Exception $ex) {
            $this->assertTrue($ex instanceof InvalidArgumentException);
        }

        $url = '';
        try {
            $file_get_contents->get($url);
        } catch (\Exception $ex) {
            $this->assertTrue($ex instanceof InvalidArgumentException);
        }
    }

    /**
     * @test
     */
    public function testGetWithTimeOutDefault()
    {
        $file_get_contents = new FileGetContents($this->logger->reveal());
        $ret = $file_get_contents->get(self::URL_TEST);

        $this->assertNotNull($ret);
    }

    /**
     * @test
     */
    public function testGetWithTimeOut()
    {
        $file_get_contents = new FileGetContents($this->logger->reveal());
        $ret = $file_get_contents->get(self::URL_TEST, [$file_get_contents::TIMEOUT_S => 3.5]);

        $this->assertNotNull($ret);
    }

    /**
     * @test
     */
    public function testGetTimeOutException()
    {
        $file_get_contents = new FileGetContents($this->logger->reveal());
        try {
            $file_get_contents->get(self::URL_TEST, [$file_get_contents::TIMEOUT_S => 0]);
        } catch (\Exception $ex) {
            $this->assertTrue(
                $ex instanceof FileGetContentsException
                && $ex->getMessage() == sprintf($file_get_contents::MSG_FILE_ERROR, "[".self::URL_TEST."]")
            );
        }
    }

    /**
     * @test
     */
    public function testGetFileEmptyException()
    {
        $file_get_contents = new FileGetContents($this->logger->reveal());

        try {
            $file_get_contents->get(__DIR__ . '/empty.m3u8');
        } catch (\Exception $ex) {
            $this->assertTrue(
                $ex instanceof FileGetContentsException
                && $ex->getMessage() == $file_get_contents::MSG_FILE_EMPTY
            );
        }
    }

    /**
     * @test
     */
    public function testGetWithCache()
    {
        $file_get_contents = new FileGetContents($this->logger->reveal());
        $ret = $file_get_contents->get(self::URL_TEST, [
            $file_get_contents::CACHE => [
                $file_get_contents::ENABLED => true,
                $file_get_contents::TTL => 5,
                $file_get_contents::INSTANCE => new APCUserCache()
            ]
        ]);

        $this->assertNotNull($ret);
    }
}
