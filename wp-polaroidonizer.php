<?php
/*
Plugin Name: WP-Polaroidonizer
Plugin URI: http://kloeschen.com/plugin-polaroidonizer/25/
Description: This Plugin implements the <a href="http://polaroidonizer.nl.eu.org/">polaroidonizer script</a>. 
Author: Markus Kloeschen
Version: 0.9debug
Author URI: http://kloeschen.com
*/ 

/*
Copyright (C) 2005 Markus Klöschen

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

http://www.gnu.org/licenses/gpl.txt

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/   


define("POLAROIDONIZER_PLUGIN_VERSION", "0.9debug");
define("POLAROIDONIZER_PATH", dirname(__FILE__));

PolaroidonizersetupOptions();

add_action('admin_menu', 'polaroidonizer_admin_menu');
add_action('wp_footer', 'polaroidonizergarbageCollector');
add_filter('the_content', 'polaroidonizer_filter_the_content');

function polaroidonizerCreateFilename($url, $text, $is_temp=false){
	$string = '';
	if($is_temp){

	 $string .= 'temp_';
	}
	$string .= md5($url.$text).'.jpg';
	if(get_option('polaroidonizer_debug')){
		echo '<!-- polaroidonizer debug '.date("H:i:s").': Using "'.$url.'" and "'.$text.'" to create filename -->'."\n";
		echo '<!-- polaroidonizer debug '.date("H:i:s").': filename set to:"'.$string.'" -->'."\n";
	}
	return $string;
}

function polaroidonizerCreatePolaroid($url, $text, $angle, $bgcolor, $x, $y, $is_temp=false){
//this function will only create the polaroid, now check here!!!
		$text_pur = $text;
		$text = trim(strip_tags(stripslashes(str_replace("_", " ", $text))));
		$photo = $url;
		if(get_option('polaroidonizer_debug')){
			echo '<!-- polaroidonizer debug '.date("H:i:s").': Polaroidonizer options: '."\n";
			echo "polaroidonizer_bgcolor = ".get_option('polaroidonizer_bgcolor')."\n";
			echo "polaroidonizer_text = ".get_option('polaroidonizer_text')."\n";
			echo "polaroidonizer_x = ".get_option('polaroidonizer_x')."\n";
			echo "polaroidonizer_y = ".get_option('polaroidonizer_y')."\n";
			echo "polaroidonizer_angle = ".get_option('polaroidonizer_angle')."\n";
			echo "polaroidonizer_cache = ".get_option('polaroidonizer_cache')."\n";
			echo "-->\n";
		}
		$polaroid = imagecreatetruecolor(246, 300);
		$bg = explode(",", $bgcolor);
		
		$bg = imagecolorallocate($polaroid, $bg[0], $bg[1], $bg[2]);
		imagefill($polaroid, 0, 0, $bg);
		$info = getimagesize($photo);
		$scale = round(($info[0] > $info[1]) ? (200 / $info[1]) : (200 / $info[0]), 4);
		if ($info[2] == 1)
		{
			$photo = imagecreatefromgif(stripslashes($photo));
			if(get_option('polaroidonizer_debug')){
				echo '<!-- polaroidonizer debug '.date("H:i:s").': Create image from gif -->'."\n";
			}
		}
		elseif ($info[2] == 2)
		{
			if(get_option('polaroidonizer_debug')){
				echo '<!-- polaroidonizer debug '.date("H:i:s").': Create image from jpeg -->'."\n";
			}
			$photo = imagecreatefromjpeg(stripslashes($photo));
		}
		elseif ($info[2] == 3)
		{
			$photo = imagecreatefrompng(stripslashes($photo));
			if(get_option('polaroidonizer_debug')){
				echo '<!-- polaroidonizer debug '.date("H:i:s").': Create image from png -->'."\n";
			}
		}

		$tmp = imagecreatetruecolor(200, 200);
		imagecopyresampled($tmp, $photo, 0, 0, $x, $y, floor($info[0] * $scale), floor($info[1] * $scale), $info[0], $info[1]);

		imagecopy($polaroid, $tmp, 20, 18, 0, 0, 200, 200);

		$frame = imagecreatefrompng(POLAROIDONIZER_PATH ."/wp-polaroidonizer/frame.png");
		if($frame){
			if(get_option('polaroidonizer_debug')){
				echo '<!-- polaroidonizer debug '.date("H:i:s").': Used Frame -->'."\n";
			}
		}else{
			if(get_option('polaroidonizer_debug')){
				echo '<!-- polaroidonizer debug '.date("H:i:s").': Error! Frame not used! -->'."\n";
			}
		}
		imagecopy($polaroid, $frame, 0, 0, 0, 0, 245, 301);

		$text = wordwrap($text, 25, "||", 1);
		$text = explode("||", $text);

		$black = imagecolorallocate($polaroid, 0, 0, 0);

		$text_pos_y = array(235, 250, 265);
		
		if(get_option('polaroidonizer_debug')){
			if(is_readable(POLAROIDONIZER_PATH . "/wp-polaroidonizer/annifont.ttf")){		
				echo '<!-- polaroidonizer debug '.date("H:i:s").': '.POLAROIDONIZER_PATH . '/wp-polaroidonizer/annifont.ttf exists, is readable and will be used -->'."\n";
			}else{
				echo '<!-- polaroidonizer debug '.date("H:i:s").': '.POLAROIDONIZER_PATH . '/wp-polaroidonizer/annifont.ttf is not readable and will be used -->'."\n";
			}
		}		

		for ($i = 0; $i < 3; $i++)
		{
			$width = imagettfbbox(10, 0, POLAROIDONIZER_PATH . "/wp-polaroidonizer/annifont.ttf", $text[$i]);
			$text_pos_x = (196 - $width[2])/2 + 20;
			imagettftext($polaroid, 10, 0, $text_pos_x, $text_pos_y[$i], $black, POLAROIDONIZER_PATH."/wp-polaroidonizer/annifont.ttf", $text[$i]);
		}

		$polaroid = imagerotate($polaroid, $angle, $bg);
		if($polaroid){
			if(get_option('polaroidonizer_debug')){
				echo '<!-- polaroidonizer debug '.date("H:i:s").': polaroid created -->'."\n";
				
			}
		}
		$filename = polaroidonizerCreateFilename($url, $text_pur, $is_temp);
		if($fp = fopen(ABSPATH.'wp-content/polaroids/'.$filename,"w") && get_option('polaroidonizer_debug')){
			echo '<!-- polaroidonizer debug '.date("H:i:s").': '.ABSPATH.'wp-content/polaroids/'.$filename.' is writeable and will be created -->'."\n";
		}
		imagejpeg($polaroid , ABSPATH.'wp-content/polaroids/'.$filename, 80);
		if(file_exists(ABSPATH.'wp-content/polaroids/'.$filename) && get_option('polaroidonizer_debug')){
					echo '<!-- polaroidonizer debug '.date("H:i:s").': '.ABSPATH.'wp-content/polaroids/'.$filename.' is created -->'."\n";
		}
		imagedestroy($polaroid);
		return $filename;
}


function polaroidonizerGarbageCollector(){
//will remove "old" cached temp images
	$cache_dir = ABSPATH . 'wp-content/polaroids';
	$handle=opendir($cache_dir);
	while ($file = readdir ($handle)) {
   		if ($file != "." && $file != ".." && substr($file,0,5) == 'temp_' && (time()-get_option('polaroidonizer_cache' )*60) > filectime($cache_dir."/".$file) ) {
       			unlink($cache_dir."/".$file);
			unlink($cache_dir."/thumbnails/".$file);
   		}
	}
	closedir($handle); 
}

function polaroidoninzerPrecheck(){
//checks all prerequesists
	$messages = array();
	if(!is_writable( ABSPATH . 'wp-content/polaroids'))
	{ 
		$messages[] = 'Cache Dir ('.ABSPATH . 'wp-content/polaroids'.') is  not writable!';
	}
	
	if(!is_readable(POLAROIDONIZER_PATH .'/wp-polaroidonizer/frame.png'))
	{
		$messages[] = 'PolaroidFrame is not readable: '.POLAROIDONIZER_PATH .'/wp-polaroidonizer/frame.png';
	}
	if(!is_readable(POLAROIDONIZER_PATH . "/wp-polaroidonizer/annifont.ttf"))
	{
		$messages[] = 'PolaroidFont is not readable: '.POLAROIDONIZER_PATH .'/wp-polaroidonizer/annifont.ttf';
	}

	$needed_functions = array();
	$needed_functions[] = 'imagecreatetruecolor';
	$needed_functions[] = 'imagecreatefromgif';
	$needed_functions[] = 'imagecreatefromjpeg';
	$needed_functions[] = 'imagecreatefrompng';
	$needed_functions[] = 'imagecopyresampled';
	$needed_functions[] = 'imagecopy';
	$needed_functions[] = 'imagettftext';
	$needed_functions[] = 'imagerotate';

	foreach($needed_functions as $function)
	{
		if(!function_exists($function))
		{
			$messages[] = 'Needed php function '.$function.' does not exist';	
		}
	}

return $messages;
}

function PolaroidonizersetupOptions()
{
	
		add_option('polaroidonizer_bgcolor', '255,255,255');
		add_option('polaroidonizer_text', 'Polaroid ;)');
		add_option('polaroidonizer_x', '0');
		add_option('polaroidonizer_y', '0');
		add_option('polaroidonizer_angle', '15');
		add_option('polaroidonizer_cache', '15');
		add_option('polaroidonizer_debug', '0');
}





function polaroidonizer_admin_menu()
{
	add_options_page(__('Polaroidonizer Options'), __('Polaroidonizer'), 5, basename(__FILE__), 'polaroidonizer_options_page');
	add_submenu_page('index.php', 'Polaroidonizer&trade;', 'Polaroidonizer&trade;', 1, __FILE__, 'polaroidonizer_admin_gallery');

}




function polaroidonizer_admin_gallery()
{
?><div class="wrap">
	<h2>Polaroidonizer&trade; Gallery</h2>
	
	<div id="polaroidonizer_gallery">
<?php 
	
	$cache_dir = ABSPATH . 'wp-content/polaroids';
	
	_e('Markus', 'wp-polaroidonizer') ;
	echo '<ul>';
	$number = 0;
	$handle=opendir($cache_dir);
	while ($file = readdir ($handle)) {
   		if ($file != "." && $file != ".." && substr($file,0,5) == 'temp_' ) {
			$number++;
       			echo '<li><a class="gallery slide'.$number.'" href="#"><span> <img src="'. get_bloginfo('wpurl')."/wp-content/polaroids/".$file.'" /></span></a></li>';
   		}
	}
	closedir($handle); 
	echo '</ul>';



?>
	<br style=" clear:both;"/></div>
</div>

<?
}






function polaroidonizer_options_page()
{
	$updated = false;
	
	if (isset($_POST['polaroidonizer_bgcolor']) && isset($_POST['polaroidonizer_bgcolor']) && isset($_POST['polaroidonizer_x']) && isset($_POST['polaroidonizer_y']) && isset($_POST['polaroidonizer_cache']) && isset($_POST['polaroidonizer_angle']))
	{
	
		$polaroidonizer_bgcolor = $_POST['polaroidonizer_bgcolor'];
		$polaroidonizer_text = $_POST['polaroidonizer_text'];
		$polaroidonizer_x = $_POST['polaroidonizer_x'];
		$polaroidonizer_y = $_POST['polaroidonizer_y'];
		$polaroidonizer_angle = $_POST['polaroidonizer_angle'];
		$polaroidonizer_cache = $_POST['polaroidonizer_cache'];
		$polaroidonizer_debug = $_POST['polaroidonizer_debug'];

		
		update_option('polaroidonizer_bgcolor', $polaroidonizer_bgcolor);
		update_option('polaroidonizer_text', $polaroidonizer_text);
		update_option('polaroidonizer_x', $polaroidonizer_x);
		update_option('polaroidonizer_y', $polaroidonizer_y);
		update_option('polaroidonizer_angle', $polaroidonizer_angle);
		update_option('polaroidonizer_cache', $polaroidonizer_cache);
		update_option('polaroidonizer_debug', $polaroidonizer_debug);
		
		$updated = true;
	}
	$polaroidonizer_bgcolor = get_option('polaroidonizer_bgcolor');
	$polaroidonizer_text = get_option('polaroidonizer_text');
	$polaroidonizer_x = get_option('polaroidonizer_x' );
	$polaroidonizer_y = get_option('polaroidonizer_y' );
	$polaroidonizer_angle = get_option('polaroidonizer_angle' );
	$polaroidonizer_cache = get_option('polaroidonizer_cache' );
	$polaroidonizer_debug = get_option('polaroidonizer_debug' );

	if ($updated)
	{
		?>
<div class="updated"><p><strong>Options saved.</strong></p></div>
		<?php
	}

//do the prechecks:
$check = polaroidoninzerPrecheck();
if(!empty($check)){
?>
	<div class="wrap"><h2>Check failed!</h2><ul>
<?php
	foreach($check as $error_message)
	{
		echo '<li>'.$error_message.'</li>';
	}
?>	</ul></div>
<?php
}

?>	


<div class="wrap">
		<h2>Polaroidonizer&trade; Options</h2>
		<form name="form1" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">

			<fieldset class="options">
				<legend>Configure the default values for the Polaroidonizer&trade;</legend>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
				<tr valign="top">
					<th width="33%" scope="row">Background-Color:</th>
					<td><input name="polaroidonizer_bgcolor" type="text" id="polaroidonizer_bgcolor" width="20" value="<?php echo $polaroidonizer_bgcolor; ?>"/></td>
				</tr>
				<tr valign="top">
					<th width="33%" scope="row">X and Y Coordinates</th>
					<td><input name="polaroidonizer_x" type="text" id="polaroidonizer_x" width="10" value="<?php echo $polaroidonizer_x; ?>"/><input name="polaroidonizer_y" type="text" id="polaroidonizer_y" width="10" value="<?php echo $polaroidonizer_y; ?>"/></td>
				</tr>
				<tr valign="top">
					<th width="33%" scope="row">Rotation Angle (0-360 degrees):</th>
					<td><input name="polaroidonizer_angle" type="text" id="polaroidonizer_angle" width="20" value="<?php echo $polaroidonizer_angle; ?>"/><br/>
						15 as default looks good!
					</td>
				</tr>
				<tr valign="top">
					<th width="33%" scope="row">Default (funny) text:</th>
					<td><input name="polaroidonizer_text" type="text" id="polaroidonizer_text" width="20" value="<?php echo $polaroidonizer_text; ?>"/><br/>
						Will be handwritten on the polaroid
					</td>
				</tr>
				<tr valign="top">
					<th width="33%" scope="row">Cache time for generated Polaroids:</th>
					<td><input name="polaroidonizer_cache" type="text" id="polaroidonizer_cache" width="20" value="<?php echo $polaroidonizer_cache; ?>"/><br/>
						In minutes. Only used for Polaroidonizer&trade; Page
					</td>
				</tr>
				<tr valign="top">
					<th width="33%" scope="row">Write debug Data as HTML-Comments?:</th>
					<td><input name="polaroidonizer_debug" type="checkbox" id="polaroidonizer_debug" value="1" <?php if($polaroidonizer_debug) echo 'checked="checked"';?>/><br/>
						inserts HTML-Comment with debug data.
					</td>
				</tr>
				</table>
			</fieldset>
		
			
			<p class="submit">
			  <input type="submit" name="Submit" value="Update Options &raquo;" />
			</p>
		</form>
	
	</div>
<div class="wrap"><h2>Polaroidonizer&trade; Demo</h2>
<p><img src="<?php echo bloginfo('wpurl'); ?>/wp-content/plugins/wp-polaroidonizer/images/demo_image.jpg" alt="original Image from http://flickr.com/photos/nvbphoto/" />

<?php
//first delete the cached version:
$photo = get_bloginfo('wpurl').'/wp-content/plugins/wp-polaroidonizer/images/demo_image.jpg';
$text = 'This is a demonstration of what can be done';
$cache_dir = ABSPATH . 'wp-content/polaroids/';
$demo_name = polaroidonizerCreateFilename($photo, $text, true);
if(file_exists( $cache_dir.$demo_name)) unlink( $cache_dir.$demo_name);
polaroidonizerCreatePolaroid($photo, $text, get_option('polaroidonizer_angle'), get_option('polaroidonizer_bgcolor'), get_option('polaroidonizer_x'), get_option('polaroidonizer_y'),  true);

echo sprintf('<img src="%s" />',get_bloginfo('wpurl').'/wp-content/polaroids/'.$demo_name);
?>
</p><p>
The original image is from <a href="http://www.flickr.com">flickr</a>. Posted there by <a href="http://flickr.com/photos/nvbphoto/">nvbphoto</a><br />
Link to the image: <a href="http://flickr.com/photos/nvbphoto/20290577/">http://flickr.com/photos/nvbphoto/20290577/</a>. Please leave a comment, if you like it.

</p>
</div>
<?php	
}


function polaroidonizer_filter_the_content($content)
{
	if (strstr($content, "[POLAROIDONIZER]") != false)
	{
		$output = '';

define("VERSION", "0.5.7");
define("CREATIONDATE", "June 8th, 2005");
error_reporting(0);
$polaroid_created = false;
if ($_SERVER['REQUEST_METHOD'] == "POST" )
{
	$data = array_merge($_GET, $_POST);
	

	if (empty($data['bg']))
	{
		$errmsg['bg'] = "Missing background color value:";
	}
	else
	{
		$bg = explode(",", $data['bg']);
		for ($i = 0; $i < 3; $i++)
		{
			if ($bg[$i] < 0 || $bg[$i] > 255 || !is_numeric($bg[$i]))
			{
				$errmsg['bg'] = "Incorrect background color value:";
				break;
			}
		}
	}

	$data['x'] = !empty($data['x']) ? $data['x'] : 0;
	$data['y'] = !empty($data['y']) ? $data['y'] : 0;

	if ((isset($data['x']) && !is_numeric($data['x'])) || (isset($data['y']) && !is_numeric($data['y'])))
	{
		$errmsg['xy'] = "Incorrect x and/or y coordinates:";
	}

	$data['angle'] = !empty($data['angle']) || $data['angle'] == "0" ? $data['angle'] : 15;

	if ($data['angle'] < 0 || $data['angle'] > 360 || !is_numeric($data['angle']))
	{
		$errmsg['angle'] = "Incorrect rotation angle:";
	}

	if(!isset($data['photo']) || $data['photo'] == "http://")
	{
		$errmsg['photo'] = "Missing image URL:";
	}
	else
	{
		$info = getimagesize($data['photo']);
		if(!$info)
		{
			$errmsg['photo'] = "Incorrect URL or linked file does not exist:";
		}
		elseif(!in_array($info[2], array(1, 2, 3)))
		{
			$errmsg['photo'] = "Unknown filetype. Use GIF, JPEG or PNG only:";
		}
		elseif (($info[0] - $data['x']) < 200 || ($info[1] - $data['y']) < 200)
		{
			$errmsg['photo'] = "Imageresolution is too low:";
		}
		elseif ($info[0] >= 2000 || $info[1] >= 2000)
		{
			$errmsg['photo'] = "Imageresolution is too high";
		}
	}

	$text = strip_tags(stripslashes(str_replace(" ", "_", trim($data['text']))));
	if(empty($text))
	{
		$errmsg['text'] = "Missing (funny) remark/text:";
	}
	elseif(strlen($text) > 100)
	{
		$errmsg['text'] = "Text/remark is too long:";
	}


	if(!isset($errmsg))
	{

		$image_name = polaroidonizerCreatePolaroid($data['photo'], $_POST['text'], $_POST['angle'], $data['bg'], $_POST['x'], $_POST['y'], true);
		$polaroid_created = true;

	}
}
/*
TODO muss ich noch automagisch im header einbauen....
<style type="text/css">
<!--
@import url("style.css");
-->
</style>

*/

$output .= '<a href="http://www.polaroidonizer.nl.eu.org/"><img src="'. get_bloginfo('wpurl') .'/wp-content/plugins/wp-polaroidonizer/images/polaroidonizer_logo.png" width="167" height="46" border="0"></a><br />';

if(!$polaroid_created) 
{
	$output .=  '<img src="'. get_bloginfo('wpurl').'/wp-content/plugins/wp-polaroidonizer/images/photo.jpg" />';
}else{
		$output .=  '<img src="'. get_bloginfo('wpurl')."/wp-content/polaroids/".$image_name.'">';
}


$output .= '
<div>
			<form name="polaroidonizer" method="post" action="" onsubmit="polaroidonizer.submit.disabled=true;">
				<table width="100%" border="0" cellpadding="4" cellspacing="0">
					<tr>
						<td><h1>Polaroid-o-nize&trade; an image</h1></td>
					</tr>';

if (isset($errmsg['bg']))
{
$output .= '
					<tr>
						<td bgcolor="#ffffff"><span class="errmsg">'. $errmsg['bg'] .'</span></td>
					</tr>';

}
$output .=' 
					<tr>
						<td>
							Background color (RGB value):<br>
							<input name="bg" type="text" value="';
 if (isset($data['bg'])) { $output .=  trim(htmlspecialchars(stripslashes($data['bg']), ENT_QUOTES)); } 
	else { $output .= "255,255,255"; } 
$output .= '" size="11" maxlength="11">
						</td>
					</tr>';
if (isset($errmsg['photo']))
{
					$output .= '<tr>
						<td bgcolor="#ffffff"><span class="errmsg">'. $errmsg['photo'].'</span></td>
					</tr>';
}
$output .= ' <tr>
						<td>
							URL to image (GIF, JPEG or PNG, 200x200 pixels minimum):<br>';
							$output .= '<input name="photo" type="text" value="';
 if (isset($data['photo'])) { $output .= trim(htmlspecialchars(stripslashes($data['photo']), ENT_QUOTES)); } 
	else { $output .= ' "http://"'; } 
$output .= '" style="width:255px">
						</td>
					</tr>';

if (isset($errmsg['xy']))
{
$output .= ' <tr>
						<td bgcolor="#ffffff"><span class="errmsg">'. $errmsg['xy'].'</span></td>
					</tr>';

}
$output .= ' <tr> <td> x and y coordinates (optional):<br> <input name="x" type="text" value="';
 if (isset($data['x'])) { $output .= trim(htmlspecialchars(stripslashes($data['x']), ENT_QUOTES)); } 
	else { $output .= "0"; } 
$output .= '" size="3" maxlength="3"> <input name="y" type="text" value="';
if (isset($data['y'])) { $output .=  trim(htmlspecialchars(stripslashes($data['y']), ENT_QUOTES)); } 
else { $output .=  "0"; } 
$output .= '" size="3" maxlength="3"> </td> </tr>';


if (isset($errmsg['angle']))
{
$output .= ' <tr> <td bgcolor="#ffffff"><span class="errmsg">' . $errmsg['angle'] .'</span></td> </tr>';


}
$output .= ' <tr> <td> Rotation angle (between 0 and 360 degrees):<br> <input name="angle" type="text" value="';
 if (isset($data['angle'])) { $output .=  trim(htmlspecialchars(stripslashes($data['angle']), ENT_QUOTES)); } 
else { $output .= '15'; } 
$output .= '" size="3" maxlength="3"> </td> </tr>';

if (isset($errmsg['text']))
{
					$output .= '<tr> <td bgcolor="#ffffff"><span class="errmsg">'. $errmsg['text'].'</span></td> </tr>';

}
$output .= ' <tr> <td> (Funny) remark/text:<br> <input name="text" type="text" style="width:255px" value="';
if (isset($data['text'])) { $data['text'] = str_replace("_", " ", $data['text']); 
$output .=  trim(htmlspecialchars(stripslashes($data['text']), ENT_QUOTES)); } 
$output .= '"> </td> </tr> <tr> <td><input type="submit" name="submit" value="Polaroid-o-nize&trade; now!"></td> </tr> </table> </form> <table width="100%" border="0" cellspacing="0" cellpadding="4"> <tr> <td colspan="2" ><h1>Other Polaroid-o-nizer&trade; sites</h1></td> </tr> <tr> <td valign="middle"><img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-polaroidonizer/images/bullet.gif"></td> <td><a href="http://www.polaroidonizer.nl.eu.org/">The official Polaroid-o-nizer&trade; site</a></td> </tr>';
$output .= '<tr> <td valign="middle"><img src="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-polaroidonizer/images/bullet.gif"></td> <td><a href="http://www.phpfreakz.nl/library.php?sid=18260">The original Polaroid-o-nizer&trade; script</a></td> </tr> <tr> <td valign="middle"><img src="' . get_bloginfo('wpurl').'/wp-content/plugins/wp-polaroidonizer/images/bullet.gif"></td> <td><a href="http://mathibus.com/archive/2005/06/polaroidonizer-favelet">Polaroid-o-nizer&trade; favelet</a></td> </tr> <tr> <td valign="middle">';
$output .= '<img src="'. get_bloginfo('wpurl').'/wp-content/plugins/wp-polaroidonizer/images/bullet.gif"></td> <td><a href="http://kloeschen.com/plugin-polaroidonizer/25/">Polaroid-o-nizer&trade; WordPress plugin</a></td> </tr> </table> </td> </tr></table> </div> <table>	<tr> <td class="dc"> The Polaroid-o-nizer&trade; team cannot be held responsible for anything created with the Polaroid-o-nizer&trade;. Use at own risk! Contact us at polaroidonizer[at]gmail.com </td> <td align="right" class="dc"> Copyright 2004-2005 Polaroid-o-nizer&trade;<br> (v.'. VERSION .' / '.  CREATIONDATE .') Plugin-Version: '.POLAROIDONIZER_PLUGIN_VERSION.'</td> </tr> </table>';









		
		$output = '</p>' . $output . '<p>'; // This hack is for wordpress replacing lines with paragraphs in the editor
		
		return str_replace("[POLAROIDONIZER]", $output, $content);

	}
	else
	{
		return $content;
	}
}









function polaroidonizerCreator($matches){
	$vars = explode('|',$matches);
	$url = $vars[0];
	$text = $vars[1];

		$text = trim(strip_tags($text));

		$info = getimagesize($url);
		if(!$info)
		{
			return "<!-- Polaroidonizer Error: Incorrect URL or linked file does not exist-->";
		}
		elseif(!in_array($info[2], array(1, 2, 3)))
		{
			return "<!-- Polaroidonizer Error: Unknown filetype. Use GIF, JPEG or PNG only-->";
		}
		elseif (($info[0] - get_option('polaroidonizer_x')) < 200 || ($info[1] - get_option('polaroidonizer_y')) < 200)
		{
			return "<!-- Polaroidonizer Error: Imageresolution is too low-->";
		}
		elseif ($info[0] >= 2000 || $info[1] >= 2000)
		{
			return "<!-- Polaroidonizer Error: Imageresolution is too high-->";
		}

		$filename = polaroidonizerCreatePolaroid($url, $text, get_option('polaroidonizer_angle'), get_option('polaroidonizer_bgcolor'), get_option('polaroidonizer_x'), get_option('polaroidonizer_y'), $is_temp=false);
		return '<img src="'. get_bloginfo('wpurl')."/wp-content/polaroids/".$filename.'" />';
}






function polaroidonizerFilter($text) {
	
	return preg_replace_callback("/<!--polaroid:(.*)-->/", "polaroidonizerMatcher", $text);
}

function polaroidonizerMatcher($matches) {
return polaroidonizerCreator($matches[1]);
}

if(function_exists('add_filter')){
	add_filter('the_content', 'polaroidonizerFilter');
}




function wp_polaroidonizer_css() {
	?>
	<style type="text/css">
	/* BASIC STYLES
	------------------------------------------------------------------------------*/
<?php 


	$cache_dir = ABSPATH . 'wp-content/polaroids';
	

	$number = 0;
	$handle=opendir($cache_dir);
	while ($file = readdir ($handle)) {
   		if ($file != "." && $file != ".." && substr($file,0,5) == 'temp_' ) {
			$number++;
			//thumbnail erzeugen
			if(!file_exists(ABSPATH . 'wp-content/polaroids/thumbnails/'.$file)){
			

////////////////////////////////////

$percent = 0.5; // if you want to scale down first
$imagethumbsize = 93; // thumbnail size (area cropped in middle of image)
list($width, $height) = getimagesize(ABSPATH . 'wp-content/polaroids/'.$file);
$new_width = $width * $percent;
$new_height = $height * $percent;

$image_p = imagecreatetruecolor($imagethumbsize , $imagethumbsize);  // true color for best quality
$image = imagecreatefromjpeg(ABSPATH.'wp-content/polaroids/'.$file);

// basically take this line and put in your versin the -($new_width/2) + ($imagethumbsize/2) & -($new_height/2) + ($imagethumbsize/2) for
// the 2/3 position in the 3 and 4 place for imagecopyresampled
// -($new_width/2) + ($imagethumbsize/2)
// AND
// -($new_height/2) + ($imagethumbsize/2)
// are the trick
imagecopyresampled($image_p, $image, -($new_width/2) + ($imagethumbsize/2), -($new_height/2) + ($imagethumbsize/2), 0, 0, $new_width , $new_width , $width, $height);

// Output

imagejpeg($image_p, ABSPATH . 'wp-content/polaroids/thumbnails/'.$file, 100);

///////////////////////////////////
			}
       			echo '#polaroidonizer_gallery a.slide'.$number.' {
    				background:url('.get_bloginfo('wpurl')."/wp-content/polaroids/thumbnails/".$file.'); 
    				height:93px; 
    				width:60px;
    				}';

   		}
	}
	closedir($handle); 
	


?>


#polaroidonizer_gallery {position:relative; width:770px; height:396px; margin:20px auto 0 auto; border:1px solid #aaa; background:#fff url(../images/back.jpg) 75px 10px no-repeat;}

/* Removing the list bullets and indentation - add size - and position */
#polaroidonizer_gallery ul {width:198px; height:386px; padding:0;  margin:5px; list-style-type:none; float:right;}

#polaroidonizer_gallery li {float:left;}

/* Remove the images and text from sight */
#polaroidonizer_gallery a.gallery span {position:absolute; width:1px; height:1px; top:5px; left:5px; overflow:hidden; background:#fff;}

#polaroidonizer_gallery a.gallery, #polaroidonizer_gallery a.gallery:visited {display:block; color:#000; text-decoration:none; border:1px solid #000; margin:1px 2px 1px 2px; text-align:left; cursor:default;}
#polaroidonizer_gallery a.gallery:hover {border:1px solid #fff;}
#polaroidonizer_gallery a.gallery:hover span {position:absolute; width:372px; height:372px; top:10px; left:75px; color:#000; background:#fff;}
#polaroidonizer_gallery a.gallery:hover img {border:1px solid #fff; float:left; margin-right:5px;}
#polaroidonizer_gallery a.slideb:hover img, #container a.slidei:hover img {float:right;}





	</style>
	<?php
}


add_action('admin_head', 'wp_polaroidonizer_css');



?>
