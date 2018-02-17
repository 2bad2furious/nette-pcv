<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 9.2.18
 * Time: 15:04
 */

class OneInstanceFileStorage extends \Nette\Caching\Storages\FileStorage {
    private $storage = [];

    public function write($key, $data, array $dp) {
        parent::write($key, $data, $dp); // TODO: Change the autogenerated stub

        return $this->storage[$key] = $data;
    }

    public function read($key) {
        if (isset($this->storage[$key])) return $this->storage[$key];
        return parent::read($key); // TODO: Change the autogenerated stub
    }


}