<?php
function nocache_headers($disable_back=false)
{
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Connection: close");
	
	if($disable_back)
	{
		ini_set('session.cache_limiter','private');
		session_cache_limiter(1);
	}
}
	
function generate_codes()
{
	$result = [];

	try
	{
		$file_path = __DIR__ . '/temp_generated/user_'.rand(1,111111). "_".rand(1,111111). "_".rand(1,111111). "_".rand(1,111111). "_";
		$print = true; //otherwise to file
		$command_1 = "vipaccess provision ". ($print ? '-p' : "-o $file_path").' -t SYMC';
		$exec_output_1 = shell_exec($command_1); 
			
		// parse
		preg_match('/otpauth\:\/\/(.*?)\s/', $exec_output_1, $vars_1);
		$oath_full_string = 'otpauth://'.$vars_1[1];
		parse_str($oath_full_string, $out);  
		preg_match('/Access\:(.*?)\?/', $oath_full_string, $vars_2);
		
		$result['name']	= $vars_2[1];
		$result['secret']= $out[array_keys($out)[0]];
		
		// generate QR_CODE
		$command_2 = "qrencode -o - '$oath_full_string'";
		$exec_output_2 = shell_exec($command_2); 
		$qr_code_png_data = $exec_output_2;
		$qr_code_png_encoded = base64_encode($exec_output_2);
		
		$result['qr_code_data']	= $qr_code_png_encoded;
		$result['full_string']	= $oath_full_string;
		
		return $result;
	}
	catch(Exception $e)
	{
		//mail to admin
	}
}


//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

if (!isset($_GET['action'])) 
{
	exit('action should be : load | show | generate');
}
 
if ($_GET['action']=="load")
{
	?>
	<html><head></head><body style="margin:0;">
	<div>
		<div style="background: url(https://i.imgur.com/GE9kHrL.jpg) no-repeat; background-position: center; width: 100%; height: 100%; opacity: 0.15; z-index: -111; position: absolute; background-size: contain;"></div>
		<div style="text-align:center; height:100%;">
			<button style="top: 40%; position: relative; border: 5px solid green; border-radius: 15px; box-shadow: 0px 0px 15px black;  font-size: 1.6em; padding: 6px; cursor: pointer;" onclick="js_trigger()">Generate New Symantec VIP & QR-Code</button>
			<script>function js_trigger(){ document.body.innerHTML='<img src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.6.0/ajax-loader.gif" style="width:150px; margin: 0 auto; display: flex;" />';  location.href = location.href.replace("=load","=show"); }</script>
		</div>
	</div>
	</body>
	</html>
	<?php
	exit;
}
elseif ($_GET['action']=="show")
{
	$result = generate_codes(true);
	?>
	<table>
	<?php 
	foreach ($result as $name=>$value)
	{
		$final_value= $name != 'qr_code_data' ? $value : '<img src="data:image/png;base64,'.$value.'" alt="qr-code" />';
		echo "<tr><td class='title'>$name</td><td class='value' style='font-weight:bold;'>$final_value</td></tr>";
	}
	?>
	</table>
	<?php
	
}
elseif ($_GET['action']=="generate")
{ 
	echo json_encode(generate_codes(true));
}