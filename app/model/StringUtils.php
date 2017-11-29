<?php


class StringUtils {
    //or you could use truncate in latte or \Nette\Utils\Strings::truncate()
    public static function getExcerpt(string $string, int $length, string $endText = "", bool $stripTags = true, array $spliters = [" "]) {
        $stripped = ($stripTags) ? strip_tags($string) : $string;
        $maxLength = $length - mb_strlen($endText);

        if (mb_strlen($stripped) > $length) {
            $substr = mb_substr($stripped, 0, $maxLength);
            $spaceIndex = $maxLength;
            foreach ($spliters as $spliter) {
                $splitterIndex = mb_strripos($substr, $spliter);
                if ($splitterIndex) {
                    $spaceIndex = $splitterIndex;
                    break;
                }
            }
            $stripped = substr($substr, 0, $spaceIndex) . $endText;
        }
        return $stripped;
    }
}