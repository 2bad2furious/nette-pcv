<?php


interface IManager {
    public static function on(string $trigger, callable $callback);
}