<?php


use Maiorano\Shortcodes\Manager\ShortcodeManager;

class ContentControl extends BaseControl {

    private $page;

    public function __construct(PageWrapper $pageWrapper, BasePresenter $presenter, $name) {
        $this->page = $pageWrapper;
        parent::__construct($presenter, $name);
    }

    public function render() {
        $m = $this->getShortCodeManager();
        $page = $this->page;
        $template = $this->createTemplate();
        $pm = $this->getPageManager();
        $m->register(
            new \Maiorano\Shortcodes\Library\SimpleShortcode("posts", [
                "limit"       => 5,
                "excerpt"     => 200,
                "excerptEnd"  => "...",
                "excerptMore" => "Read more.",
            ], function (string $content, array $atts) use ($page, $pm, $template) {
                dump($atts, $content);
                $limit = (int)$atts['limit'] ?: 15;
                $order = (int)$atts['order'] ?: IPageManager::ORDER_BY_ID;

                $arr = $pm->getFiltered(IPageManager::TYPE_POST, IPageManager::STATUS_PUBLIC, $page->getLanguage(), null, 1, $limit, $var, null, $order);

                dump($arr);

                $template->setFile(__DIR__ . "/templates/posts_excerpt.latte");
                $template->posts = array_map(function (array $posts) {
                    return end($posts);
                }, $arr);
                $template->excerptReadMore = $atts['excerptMore'];
                $template->excerpt = (int)$atts['excerpt'] ?: 200;
                $template->excerptEnd = $atts['excerptEnd'];
                $template->render();
            }));
        echo $m->doShortcode($this->page->getContent());
    }

    private function getShortCodeManager(): ShortcodeManager {
        return new ShortcodeManager();
    }
}