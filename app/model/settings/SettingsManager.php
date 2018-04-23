<?php


use Nette\Database\Table\IRow;

class SettingsManager extends Manager implements ISettingsManager {
    const
        TABLE = "settings",
        COLUMN_ID = "settings_id",
        COLUMN_OPTION = "option", COLUMN_OPTION_LENGTH = 60,
        COLUMN_VALUE = "value";

    /**
     * @param string $option
     * @param bool $throw
     * @return null|SettingWrapper
     * @throws SettingNotFound
     */
    public function get(string $option, bool $throw = true): ?SettingWrapper {
        $cached = $this->getCache()->load($option,
            function () use ($option) {
                return $this->getFromDb($option);
            });

        if ($cached instanceof Setting) {
            return new SettingWrapper($cached, $this->getLanguageManager());
        } else if ($throw) throw new SettingNotFound($option);

        return null;
    }

    /**
     * @param string $option
     * @param string $value
     * @return SettingWrapper
     * @throws SettingNotFound
     * @throws Throwable
     */
    public function set(string $option, string $value): SettingWrapper {
        $existing = $this->get($option, false);

        $this->uncache($option);

        $this->runInTransaction(function () use ( $option, $value, $existing) {


            $whereData = [
                self::COLUMN_OPTION => $option,
            ];

            $updateData = [
                self::COLUMN_VALUE => $value,
            ];

            if ($existing instanceof SettingWrapper) {
                $this->getDatabase()->table(self::TABLE)
                    ->where($whereData)
                    ->update($updateData);

            } else {
                $insertData = array_merge($updateData, $whereData);
                $this->getDatabase()->table(self::TABLE)
                    ->insert($insertData);
            }

        });

        return $this->get($option);
    }

    public function cleanCache() {
        $this->getCache()->clean();
    }

    /**
     * @param string $option
     * @return false|Setting
     */
    private function getFromDb(string $option) {
        $data = $this->getDatabase()
            ->table(self::TABLE)
            ->where([
                self::COLUMN_OPTION => $option,
            ])->fetch();

        return $data instanceof IRow ? $this->getFromRow($data) : false;
    }

    private function getFromRow(IRow $row): Setting {
        return new Setting($row[self::COLUMN_ID], $row[self::COLUMN_OPTION], $row[self::COLUMN_VALUE]);
    }

    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "settings");
    }

    private function uncache(string $key) {
        $this->getCache()->remove($key);
    }
}