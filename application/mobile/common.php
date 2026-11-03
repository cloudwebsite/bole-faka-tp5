<?php


function download($order)
{
	alert($order);
	$filename = kami_order_txt_path($order); //文件路径
	if ($filename === '' || !is_file($filename)) {
		echo '文件已删除';
		return;
	}
header("Content-Type: application/force-download");
header("Content-Disposition: attachment; filename=".basename($filename));
readfile($filename);
	
}




