<?php

namespace File;

/**
 * @author Gabriel Polverini <polverini.gabriel@gmail.com>
 */
interface FileGetContentsInterface
{
    public function get($url, array $config = []);
}
