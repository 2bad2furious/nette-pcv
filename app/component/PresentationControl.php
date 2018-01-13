<?php


use Nette\Application\UI\IRenderable;

abstract class PresentationControl extends BaseControl {
    public abstract function do_render(PageWrapper $page, int $id);
    public function render(){}
}