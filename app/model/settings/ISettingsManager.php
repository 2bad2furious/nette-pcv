<?php

interface ISettingsManager extends IManager {
    public function get(string $option, bool $throw = false): ?SettingWrapper;

    public function set(string $option, string $value): SettingWrapper;

    public function cleanCache();
}