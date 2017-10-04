<?php


class ContentControl extends BaseControl {
    const SLIDER_CLASS = SliderControl::class,
        SLIDER_NAME = "slider";

    const BY_NICK = [
        self::SLIDER_NAME => self::SLIDER_CLASS,
    ];

    const SYNTAX_BEGINNING = "<presentation ",
        SYNTAX_NAME = "[A-Za-z0-9_-]+",
        SYNTAX_ID = "\d+",
        SYNTAX_END = ">";

    /** #<presentation [A-Za-z0-9_-]{1,} \d{1,}> => <presentation xd-1 2># */
    const SEARCH_SYNTAX = "#" . self::SYNTAX_BEGINNING . self::SYNTAX_NAME . " " . self::SYNTAX_ID . self::SYNTAX_END . "#";
    /**  #(?<=<presenter )[A-Za-z0-9_-]+#*/
    const SEARCH_NAME = "#" . "(?<=" . self::SYNTAX_BEGINNING . ")" . self::SYNTAX_NAME . "#";
    /** #(?<=<presnter $controlName )\d+# */
    const SEARCH_ID = "#" . "(?<=" . self::SYNTAX_BEGINNING . "%s " . ")" . self::SYNTAX_ID . "#";


    /**
     * @param Page $page
     * @throws Exception for testing
     */
    public function render(Page $page): void {
        $content = $page->getContent();

        /* sets $matches to array of String[$n length] of matches => [[$match1,$match2]] */
        preg_match_all(self::SEARCH_SYNTAX, $content, $matches);
        $matches = $matches[0];

        /** @var String[$n+1 length] $split */
        $split = preg_split(self::SEARCH_SYNTAX, $content);

        for ($i = 0; $i < count($matches); $i++) {
            echo $split[$i];
            try {
                preg_match(self::SEARCH_NAME, $matches[$i], $names);
                $presentationName = (string)@$names[0];

                preg_match(sprintf(self::SEARCH_ID, $presentationName), $matches[$i], $ids);
                $id = (string)@$ids[0];

                $presentationClassName = @self::BY_NICK[$presentationName];
                if ($presentationClassName) {
                    $presentation = new $presentationClassName($this->getPresenter(), $presentationName);
                    if (!$presentation instanceof PresentationControl) throw new Exception("Control $presentationClassName not instance of PresentationControl");
                    $presentation->redrawControl();
                    $presentation->do_render($page, $id);
                }
            } catch (Exception $ex) {
                //\Tracy\Debugger::log($ex);
                throw $ex;
            }
        }
        echo $split[$i];
    }
}