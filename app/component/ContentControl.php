<?php


class ContentControl extends BaseControl {

    private $page;

    public function __construct(PageWrapper $pageWrapper, BasePresenter $presenter, $name) {
        $this->page = $pageWrapper;
        parent::__construct($presenter, $name);
    }

    /**
     */
    public function render() {
        $m = $this->getRegistrar();
        $page = $this->page;
        $template = $this->createTemplate();
        $pm = $this->getPageManager();
        try {
            $m->register(
                new \Maiorano\Shortcodes\Library\SimpleShortcode(
                    "posts", [
                    "limit" => 5,
                    "excerpt" => 200,
                    "excerpt_end" => "...",
                    "excerpt_more" => "Read more.",
                ], function (string $content, array $atts) use ($page, $pm, $template) {

                    $limit = (int)$atts['limit'] ?: 15;
                    $order = (int)$atts['order'] ?: IPageManager::ORDER_BY_ID;

                    $arr = $pm->getFiltered(IPageManager::TYPE_POST, IPageManager::STATUS_PUBLIC, $page->getLanguage(), null, 1, $limit, $var, null, $order);

                    $template->setFile(__DIR__ . "/templates/posts_excerpt.latte");
                    $template->posts = array_map(function (array $posts) {
                        return end($posts);
                    }, $arr);
                    $template->excerptReadMore = $atts['excerpt_more'];
                    $template->excerpt = (int)$atts['excerpt'] ?: 200;
                    $template->excerptEnd = $atts['excerpt_end'];
                    $template->render();
                }));
        } catch (\Maiorano\Shortcodes\Exceptions\RegisterException $ex) {
            \Tracy\Debugger::log($ex);
        }
        $content = $this->page->getContent();
        echo $this->getShortCodeManager()->runShortcode($content);
    }

    private function getShortCodeManager(): IShortcodeManager {
        /** @var IShortcodeManager $sm */
        return $this->getPresenter()->context->getByType(\IShortcodeManager::class);
    }

    private function getRegistrar(): \Maiorano\Shortcodes\Manager\ShortcodeManager {
        return $this->getShortCodeManager()->getRegistrar();
    }
}