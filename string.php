<?php
    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return substr($haystack, 0, $length) === $needle;
    }
    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);

        return $length == 0 ? true : substr($haystack, -$length) === $needle;
    }
    function removeFromEnd($haystack, $needle)
    {
        return endsWith($haystack, $needle) ? substr($haystack, 0, -strlen($needle)) : $haystack;
    }
    function contains($haystack, $needle)
    {
        return strpos((string) $haystack, (string) $needle) >= 0;
    }

    function replaceSpecialChars($original)
    {
        $pattern = ['/é/', '/è/', '/ë/', '/ê/', '/É/', '/È/', '/Ë/', '/Ê/', '/á/', '/à/', '/ä/', '/â/', '/å/', '/Á/', '/À/', '/Ä/', '/Â/', '/Å/', '/ó/', '/ò/', '/ö/', '/ô/', '/Ó/', '/Ò/', '/Ö/', '/Ô/', '/í/', '/ì/', '/ï/', '/î/', '/Í/', '/Ì/', '/Ï/', '/Î/', '/ú/', '/ù/', '/ü/', '/û/', '/Ú/', '/Ù/', '/Ü/', '/Û/', '/ý/', '/ÿ/', '/Ý/', '/ø/', '/Ø/', '/œ/', '/Œ/', '/Æ/', '/ç/', '/Ç/', '/—/', '/−/', '/â€”/', '/’/', '/½/'];
        $replace = ['e', 'e', 'e', 'e', 'E', 'E', 'E', 'E', 'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A', 'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'i', 'i', 'i', 'I', 'I', 'I', 'I', 'I', 'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U', 'y', 'y', 'Y', 'o', 'O', 'ae', 'ae', 'Ae', 'c', 'C', '-', '-', '-', "'", '{1/2}'];

        return preg_replace($pattern, $replace, $original);
    }
