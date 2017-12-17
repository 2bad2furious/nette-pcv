<?php


use Nette\Caching\Storages\FileStorage;

class TransactioningFileStorage extends FileStorage {

    private $hash;
    private $data = [];
    public function read($key) {
        dump("start of read - $key", ["hash" => $this->hash, "parent" => parent::read($key), "our_data" => @$this->data[$key]], "end of read - $key");
        if ($this->hash && isset($this->data[$key])) return $this->data[$key][0];

        return parent::read($key);
    }

    public function write($key, $data, array $dp) {
        dump("start of write - $key", ["hash" => $this->hash, "data" => $data, "our_data" => @$this->data[$key]], "end of write - $key");
        if ($this->hash) $this->data[$key] = [$data, $dp];
        else parent::write($key, $data, $dp);
    }


    public function beginTransaction() {
        $this->hash = true;//dechex(time()); //TODO should i write to file and on rollBack use a backup?
    }

    public function commit() {
        if (!$this->hash) throw new \Nette\InvalidStateException("Transaction not begun");
        dump("commit", $this->data);
        foreach ($this->data as $key => $value) {
            parent::write($key, $value[0], $value[1]);
        }
    }

    public function rollBack() {
        if (!$this->hash) throw new \Nette\InvalidStateException("Transaction not begun");
    }
}