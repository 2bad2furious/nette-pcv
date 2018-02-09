<?php
/**
 * Created by PhpStorm.
 * User: martin
 * Date: 9.2.18
 * Time: 13:23
 */

class ShortcodeManager extends Manager implements IShortcodeManager {

    /** @var \Maiorano\Shortcodes\Manager\ShortcodeManager[] */
    private $manager;

    public function getRegistrar(): \Maiorano\Shortcodes\Manager\ShortcodeManager {
        return $this->manager instanceof \Maiorano\Shortcodes\Manager\ShortcodeManager ?
            $this->manager :
            $this->manager = $this->createInstance();
    }

    private function createInstance(): \Maiorano\Shortcodes\Manager\ShortcodeManager {
        $pm = $this->getPageManager();
        return new \Maiorano\Shortcodes\Manager\ShortcodeManager(
            [
                new \Maiorano\Shortcodes\Library\SimpleShortcode(
                    "link",
                    [
                        "pageId" => null,
                        "landId" => null
                    ],
                    function (string $content, array $atts) use ($pm) {
                        $pageId = (int)@$atts["pageId"];
                        $langId = (int)@$atts["langId"];

                        if (!$pageId || !$langId) return "";

                        $page = $pm->getByGlobalId($langId, $pageId, false);
                        if ($page instanceof PageWrapper) return $page->getCompleteUrl(true);
                        return "";
                    }
                )
            ]
        );
    }
}