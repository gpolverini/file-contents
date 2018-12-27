<?php

namespace File;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheAwareTrait;
use Psr\SimpleCache\CacheInterface;

/**
 * @author Gabriel Polverini <polverini.gabriel@gmail.com>
 */
class FileGetContents implements FileGetContentsInterface
{
    use LoggerAwareTrait, CacheAwareTrait;

    const CACHE = 'cache';
    const ENABLED = 'enabled';
    const TTL = 'ttl';
    const INSTANCE = 'instance';
    const TIMEOUT_S = 'timeout_s';
    const ENDPOINT = 'endpoint';

    const MSG_FILE_ERROR = 'Error retrieving file %s.' . PHP_EOL . 'Either the file doesn\'t exist or timed out';
    const MSG_FILE_EMPTY = 'File is empty';

    protected $config;
    protected $default_timeout = 20;
    protected $default_ttl = 5;

    public function __construct(LoggerInterface $logger)
    {
        $this->setLogger($logger);
    }

    /**
     * customFileGetContents.
     *
     * @param string $url
     *
     * @return string
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function customFileGetContents($url)
    {
        if (array_key_exists(self::CACHE, $this->config)
            && array_key_exists(self::ENABLED, $this->config[self::CACHE])
            && array_key_exists(self::INSTANCE, $this->config[self::CACHE])
            && $this->config[self::CACHE][self::INSTANCE] instanceof CacheInterface) {

            $ttl = $this->default_ttl;
            if (array_key_exists(self::TTL, $this->config[self::CACHE])) {
                $ttl = $this->config[self::CACHE][self::TTL];
            }

            $this->setCache($this->config[self::CACHE][self::INSTANCE]);
            $ret = $this->cache->get($url);
            if (is_null($ret)) {
                $ret = $this->fileGetContents($url);
                $this->cache->set($url, $ret, $ttl);
            }
            return $ret;
        }

        return $this->fileGetContents($url);
    }

    /**
     * fileGetContents.
     *
     * @param $url
     * @return bool|string
     */
    private function fileGetContents($url)
    {
        $timeout = $this->default_timeout;
        if (array_key_exists(self::TIMEOUT_S, $this->config)) {
            $timeout = $this->config[self::TIMEOUT_S];
        }
        $ctx = stream_context_create(array('http'=> array('timeout' => $timeout)));
        return file_get_contents($url, false, $ctx);
    }

    /**
     * get.
     *
     * @param string $url
     * @param array $config
     *
     * @return string
     *
     * @throws FileGetContentsException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($url, array $config = [])
    {
        if (empty($url) || !is_string($url)) {
            throw new InvalidArgumentException();
        }

        $this->config = $config;

        if (strpos($url, 'http') === false) {
            $url = (array_key_exists(self::ENDPOINT, $this->config) ? $this->config[self::ENDPOINT] : '') . $url;
        }

        $this->logger->info('ORIGINAL FILE:', [$url]);

        $ret = @$this->customFileGetContents($url);

        if ($ret === false) {
            throw new FileGetContentsException(sprintf(self::MSG_FILE_ERROR, "[$url]"));
        }

        if (empty($ret)) {
            throw new FileGetContentsException(self::MSG_FILE_EMPTY);
        }

        return $ret;
    }
}
