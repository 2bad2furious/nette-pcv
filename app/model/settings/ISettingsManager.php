<?php

interface ISettingsManager extends IManager {
    public function get(string $option, ?Language $language = null): ?Setting;

    public function set(string $option, string $value, ?Language $language = null): Setting;

    public function getPageSettings(Language $language): PageSettings;

    public function cleanCache();
}