<?php

interface ISettingsManager extends IManager {
    public function get(string $option, ?int $langId, bool $throw = false): ?SettingWrapper;

    public function set(string $option, string $value, ?int $langId): SettingWrapper;

    public function cleanCache();
}