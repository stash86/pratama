<?php

function replaceMagicSymbols($original, $withShadow = false)
{
    $stringManaCost = 'ms-cost';
    if ($withShadow) {
        $stringManaCost .= ' ms-shadow';
    }

    $pattern = [
        '/\{2\/([\w])\}/' //Example {2/B}
        , '/\{([BCEGRSUWXYZ\d]+)\}/' //Example {B} or {12}
        , '/{Q}/', '/{T}/', '/{plus}/', '/\+([\d]+)([:])/' //Example +2:
        , '/\-([\d]+)([:])/' //Example -2:
        , '/\{([\w])\/([\w])\}/' //Example {B/G}
        , '/\{h([\w])\}/' //Example {hb}
        , '/CHAOS/', '/Â½/', '/½/', '/{1/2}/', '/{âˆž}/', '/{∞}/', '/-X:/',
    ];

    $replace = [
        "<i class=\"ms ms-2$1 ms-split $stringManaCost\"></i>", "<i class=\"ms ms-$1 $stringManaCost\"></i>", "<i class=\"ms ms-untap $stringManaCost\"></i>", "<i class=\"ms ms-tap $stringManaCost\"></i>", '+', '<i class="ms ms-loyalty-up ms-loyalty-$1"></i>:', '<i class="ms ms-loyalty-down ms-loyalty-$1"></i>:', "<i class=\"ms ms-$1$2 ms-split $stringManaCost\"></i>", "<span class=\"ms-half\"><i class=\"ms ms-$1 $stringManaCost\"></i></span>", '<i class="ms ms-chaos"></i>', '<i class="ms ms-1-2"></i>', '<i class="ms ms-1-2"></i>', '<i class="ms ms-1-2"></i>', "<i class=\"ms ms-infinity $stringManaCost\"></i>", "<i class=\"ms ms-infinity $stringManaCost\"></i>", '<i class="ms ms-loyalty-down ms-loyalty-X"></i>:',
    ];

    $new = preg_replace($pattern, $replace, $original);
    $new = str_replace('0:', '<i class="ms ms-loyalty-zero ms-loyalty-0"></i>:', $new);

    return $new;
}

function replaceStartingLoyaltySymbols($original)
{
    return "<i class=\"ms ms-loyalty-start ms-loyalty-$original\"></i>";
}
