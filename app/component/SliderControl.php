<?php


class SliderControl extends PresentationControl {
    public function do_render(PageWrapper $page, int $presentationId) {
        echo "it works $presentationId.";
    }
}