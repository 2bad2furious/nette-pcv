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
                        "page_id" => null,
                        "lang_id" => null
                    ],
                    function (string $content, array $atts) use ($pm) {
                        $pageId = (int)@$atts["page_id"];
                        $langId = (int)@$atts["lang_id"];


                        if (!$pageId || !$langId) return "";

                        $page = $pm->getByGlobalId($langId, $pageId, false);

                        if ($page instanceof PageWrapper) return $page->getCompleteUrl(true);
                        return "";
                    }
                )
            ]
        );
    }

    public function runShortcode(string $content, array $tags = [], bool $deep = false): string {
        $unwebalized = $this->unwebalizeLinks($content);
        return $this->getRegistrar()->doShortcode($unwebalized, $tags, $deep);
    }

    private function unwebalizeLinks(string $content): string {
        $links = $this->findShortcodeLinks($content);
        foreach ($links as $link) {

            $content = str_replace($link, urldecode($link), $content);
        }
        return $content;
    }

    private function findShortcodeLinks(string $content): array {
        preg_match_all("#\[(link)[^\[\]]*\]#", $content, $matches);
        return $matches[0];
    }


}