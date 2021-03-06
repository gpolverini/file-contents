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

    protected $config = [];
    protected $default_timeout = 20;
    protected $default_ttl = 5;

    /**
     * FileGetContents constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->setLogger($logger);
    }

    /**
     * isEnabledCache.
     * @return bool
     */
    private function isEnabledCache()
    {
        return array_key_exists(self::CACHE, $this->config)
            && array_key_exists(self::ENABLED, $this->config[self::CACHE])
            && filter_var($this->config[self::CACHE][self::ENABLED],FILTER_VALIDATE_BOOLEAN)
            && array_key_exists(self::INSTANCE, $this->config[self::CACHE])
            && $this->config[self::CACHE][self::INSTANCE] instanceof CacheInterface;
    }

    /**
     * customFileGetContents.
     * @param $url
     * @return bool|mixed|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function customFileGetContents($url)
    {
        if ($this->isEnabledCache()) {
            $ttl = $this->default_ttl;
            if (array_key_exists(self::TTL, $this->config[self::CACHE])
                && is_numeric($this->config[self::CACHE][self::TTL])) {
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
     * @param $url
     * @return bool|string
     */
    private function fileGetContents($url)
    {
        $timeout = $this->default_timeout;
        if (array_key_exists(self::TIMEOUT_S, $this->config)
            && is_numeric($this->config[self::TIMEOUT_S])) {
            $timeout = $this->config[self::TIMEOUT_S];
        }
        $ctx = stream_context_create(array('http'=> array('timeout' => $timeout)));
        return file_get_contents($url, false, $ctx);
    }


    /**
     * get.
     * @param $url
     * @param array $config
     * @return bool|mixed|string
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

        $this->logger->info("ORIGINAL FILE: $url");

        $ret = @$this->customFileGetContents($url);

        if ($ret === false) {
            throw new FileGetContentsException(
                "Error retrieving file [$url].\nEither the file doesn\'t exist or timed out"
            );
        }

        if (empty($ret)) {
            throw new FileGetContentsException('File is empty');
        }

        return $ret;
    }
}
