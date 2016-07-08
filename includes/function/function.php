<?php
function rs($parametr,$content,$length){
	return preg_replace($parametr,'',mb_substr($content,0,$length,'UTF-8'));
}
?>