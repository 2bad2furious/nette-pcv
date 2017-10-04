<?php


class SliderControl extends PresentationControl {
    public function do_render(Page $page, int $presentationId) {
        echo "it works $presentationId.";
    }
}