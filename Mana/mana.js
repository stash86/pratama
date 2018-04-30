function replaceMagicSymbol($original, $withShadow)
{
	$new=$original;
	if($new !== null && $new !=='')
	{
		$stringManaCost = "ms-cost";
		if($withShadow === true)
		{
			$stringManaCost+=" ms-shadow";
		}

		$new = $new.replace(/{2\/([\w])}/g, function(v){return v.toLowerCase();}); //Example {2/B}
		$new = $new.replace(/{2\/([\w])}/ig, "<i class=\"ms ms-2$1 ms-split "+$stringManaCost+"\"></i>"); //Example {2/B}
		$new = $new.replace(/{([BCEGRSUWXYZ\d])}/g, function(v){return v.toLowerCase();}); //Example {B}
		$new = $new.replace(/{([BCEGRSUWXYZ\d])}/ig, "<i class=\"ms ms-$1 "+$stringManaCost+"\"></i>"); //Example {B}
		$new = $new.replace(/{Q}/g, "<i class=\"ms ms-untap "+$stringManaCost+"\"></i>");
		$new = $new.replace(/{T}/g, "<i class=\"ms ms-tap "+$stringManaCost+"\"></i>");
		$new = $new.replace(/{plus}/g, "+");
		$new = $new.replace(/\+([\d]+)([:])/g, "<i class=\"ms ms-loyalty-up ms-loyalty-$1\"></i>"); //Example +2:
		$new = $new.replace(/-([\d]+)([:])/g, "<i class=\"ms ms-loyalty-down ms-loyalty-$1\"></i>"); //Example -2:
		$new = $new.replace(/{([\w])\/([\w])}/g, function(v){return v.toLowerCase();}); //Example {B/G}
		$new = $new.replace(/{([\w])\/([\w])}/ig, "<i class=\"ms ms-$1$2 ms-split "+$stringManaCost+"\"></i>"); //Example {B/G}
		$new = $new.replace(/{h([\w])}/g, function(v){return v.toLowerCase();}); //Example {hb}
		$new = $new.replace(/{h([\w])}/ig, "<span class=\"ms-half\"><i class=\"ms ms-$1 "+$stringManaCost+"\"></i></span>"); //Example {hb}
		$new = $new.replace(/CHAOS/g, "<i class=\"ms ms-chaos\"></i>");
		$new = $new.replace(/Â½/g, "<i class=\"ms ms-1-2\"></i>");
		$new = $new.replace(/½/g, "<i class=\"ms ms-1-2\"></i>");
		$new = $new.replace(/{1\/2}/g, "<i class=\"ms ms-1-2\"></i>");
		$new = $new.replace(/{âˆž}/g, "<i class=\"ms ms-infinity "+$stringManaCost+"\"></i>");
		$new = $new.replace(/{∞}/g, "<i class=\"ms ms-infinity "+$stringManaCost+"\"></i>");
		$new = $new.replace(/-X:/g, "<i class=\"ms ms-loyalty-down ms-loyalty-x\"></i>:");
		$new = $new.replace(/0:/g, "<i class=\"ms ms-loyalty-zero ms-loyalty-0\"></i>:");
	}
	
	return $new;
}
