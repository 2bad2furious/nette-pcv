<?php


class Cache extends \Nette\Caching\Cache {
    //TODO make better
    public function clean(array $conditions = null) {
        if (!$conditions || !empty($conditions[self::ALL]))
            $conditions[self::NAMESPACES] = [$this->getNamespace()];
        parent::clean($conditions);
    }
}