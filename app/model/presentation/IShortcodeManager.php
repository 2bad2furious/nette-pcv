<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 9.2.18
 * Time: 13:24
 */

interface IShortcodeManager extends IManager {
    public function getRegistrar():\Maiorano\Shortcodes\Manager\ShortcodeManager;
}