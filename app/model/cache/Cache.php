<?php


use Nette\Caching\Cache as NetteCache;

class Cache extends NetteCache {
    //TODO make better
    public function clean(array $conditions = null) {
        if (!$conditions || !empty($conditions[self::ALL]))
            $conditions[self::NAMESPACES] = [$this->getNamespace()];
        parent::clean($conditions);
    }
}