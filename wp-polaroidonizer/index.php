<?php
define("VERSION", "0.5.7");
define("CREATIONDATE", "June 8th, 2005");
header("PoN-Version: " . VERSION);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] == "POST" || !empty($_GET))
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

	if(!isset($errmsg) && empty($_GET))
	{
		$url = "?bg=" . $data['bg'] . "&photo=" . $photo . "&x=" . $data['x'] . "&y=" . $data['y'] . "&angle=" . $data['angle'] . "&text=" . $text;
		header("Location: " . $url);
		exit;
	}
	elseif(!isset($errmsg))
	{
		$text = trim(strip_tags(stripslashes(str_replace("_", " ", $_GET['text']))));

		$polaroid = imagecreatetruecolor(246, 300);

		$bg = imagecolorallocate($polaroid, $bg[0], $bg[1], $bg[2]);
		imagefill($polaroid, 0, 0, $bg);

		$scale = round(($info[0] > $info[1]) ? (200 / $info[1]) : (200 / $info[0]), 4);

		if ($info[2] == 1)
		{
			$photo = imagecreatefromgif(stripslashes($photo));
		}
		elseif ($info[2] == 2)
		{
			$photo = imagecreatefromjpeg(stripslashes($photo));
		}
		elseif ($info[2] == 3)
		{
			$photo = imagecreatefrompng(stripslashes($photo));
		}

		$tmp = imagecreatetruecolor(200, 200);
		imagecopyresampled($tmp, $photo, 0, 0, $_GET['x'], $_GET['y'], floor($info[0] * $scale), floor($info[1] * $scale), $info[0], $info[1]);

		imagecopy($polaroid, $tmp, 20, 18, 0, 0, 200, 200);

		$frame = imagecreatefrompng("frame.png");
		imagecopy($polaroid, $frame, 0, 0, 0, 0, 245, 301);

		$text = wordwrap($text, 25, "||", 1);
		$text = explode("||", $text);

		$black = imagecolorallocate($polaroid, 0, 0, 0);

		$text_pos_y = array(235, 250, 265);
		for ($i = 0; $i < 3; $i++)
		{
			$width = imagettfbbox(10, 0, "annifont.ttf", $text[$i]);
			$text_pos_x = (196 - $width[2])/2 + 20;
			imagettftext($polaroid, 10, 0, $text_pos_x, $text_pos_y[$i], $black, "annifont.ttf", $text[$i]);
		}

		$polaroid = imagerotate($polaroid, $_GET['angle'], $bg);

		header("Content-type: image/jpeg");
		imagejpeg($polaroid , "", 80);
		imagedestroy($polaroid);
		exit;
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Polaroid-o-nizer™</title>
<meta name="description" content="Onize your favorite moments with the Polaroid-o-nizer.">
<meta name="keywords" content="polaroidonizer, polaroid-o-nizer, polaroid, onizer, onize, o-nizer, o-nize, polaroidonized">
<meta name="robots" content="index,follow,noarchive">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<style type="text/css">
<!--
@import url("style.css");
-->
</style>
</head>

<body>
<table width="770" border="0" align="center" cellpadding="0" cellspacing="0">
	<tr>
		<td colspan="2">
			<a href="<?php echo $_SERVER['PHP_SELF']; ?>"><img src="images/polaroidonizer_logo.png" width="167" height="46" border="0"></a>
		</td>
	</tr>
	<tr>
		<td width="501" height="400" background="images/photo.jpg">&nbsp;</td>
		<td width="269" align="left" valign="top" bgcolor="#C1DEEA">
			<form name="polaroidonizer" method="post" action="" onsubmit="polaroidonizer.submit.disabled=true;">
				<table width="100%" border="0" cellpadding="4" cellspacing="0">
					<tr>
						<td bgcolor="#2a86ad"><h1>Polaroid-o-nize™ an image</h1></td>
					</tr>
<?php
if (isset($errmsg['bg']))
{
?>
					<tr>
						<td bgcolor="#ffffff"><span class="errmsg"><?php echo $errmsg['bg']; ?></span></td>
					</tr>
<?php
}
?>
					<tr>
						<td>
							Background color (RGB value):<br>
							<input name="bg" type="text" value="<?php if (isset($data['bg'])) { echo trim(htmlspecialchars(stripslashes($data['bg']), ENT_QUOTES)); } else { echo "255,255,255"; } ?>" size="11" maxlength="11">
						</td>
					</tr>
<?php
if (isset($errmsg['photo']))
{
?>
					<tr>
						<td bgcolor="#ffffff"><span class="errmsg"><?php echo $errmsg['photo']; ?></span></td>
					</tr>
<?php
}
?>
					<tr>
						<td>
							URL to image (GIF, JPEG or PNG, 200x200 pixels minimum):<br>
							<input name="photo" type="text" value="<?php if (isset($data['photo'])) { echo trim(htmlspecialchars(stripslashes($data['photo']), ENT_QUOTES)); } else { echo "http://"; } ?>" style="width:255px">
						</td>
					</tr>
<?php
if (isset($errmsg['xy']))
{
?>
					<tr>
						<td bgcolor="#ffffff"><span class="errmsg"><?php echo $errmsg['xy']; ?></span></td>
					</tr>
<?php
}
?>
					<tr>
						<td>
							x and y coordinates (optional):<br>
							<input name="x" type="text" value="<?php if (isset($data['x'])) { echo trim(htmlspecialchars(stripslashes($data['x']), ENT_QUOTES)); } else { echo "0"; } ?>" size="3" maxlength="3">
							<input name="y" type="text" value="<?php if (isset($data['y'])) { echo trim(htmlspecialchars(stripslashes($data['y']), ENT_QUOTES)); } else { echo "0"; } ?>" size="3" maxlength="3">
						</td>
					</tr>
<?php
if (isset($errmsg['angle']))
{
?>
					<tr>
						<td bgcolor="#ffffff"><span class="errmsg"><?php echo $errmsg['angle']; ?></span></td>
					</tr>
<?php
}
?>
					<tr>
						<td>
							Rotation angle (between 0 and 360 degrees):<br>
							<input name="angle" type="text" value="<?php if (isset($data['angle'])) { echo trim(htmlspecialchars(stripslashes($data['angle']), ENT_QUOTES)); } else { echo "15"; } ?>" size="3" maxlength="3">
						</td>
					</tr>
<?php
if (isset($errmsg['text']))
{
?>
					<tr>
						<td bgcolor="#ffffff"><span class="errmsg"><?php echo $errmsg['text']; ?></span></td>
					</tr>
<?php
}
?>
					<tr>
						<td>
							(Funny) remark/text:<br>
							<input name="text" type="text" style="width:255px" value="<?php if (isset($data['text'])) { $data['text'] = str_replace("_", " ", $data['text']); echo trim(htmlspecialchars(stripslashes($data['text']), ENT_QUOTES)); } ?>">
						</td>
					</tr>
					<tr>
						<td><input type="submit" name="submit" value="Polaroid-o-nize™ now!"></td>
					</tr>
				</table>
			</form>
<?php
if (!isset($errmsg))
{
?>
			<table width="100%" border="0" cellspacing="0" cellpadding="4">
				<tr>
					<td colspan="2" bgcolor="#58abcf"><h1>Other Polaroid-o-nizer™ sites</h1></td>
				</tr>
				<tr>
					<td valign="middle"><img src="images/bullet.gif"></td>
					<td><a href="http://www.polaroidonizer.nl.eu.org/">The official Polaroid-o-nizer™ site</a></td>
				</tr>
				<tr>
					<td valign="middle"><img src="images/bullet.gif"></td>
					<td><a href="http://www.phpfreakz.nl/library.php?sid=18260">The original Polaroid-o-nizer™ script</a></td>
				</tr>
				<tr>
					<td valign="middle"><img src="images/bullet.gif"></td>
					<td><a href="http://mathibus.com/archive/2005/06/polaroidonizer-favelet">Polaroid-o-nizer™ favelet</a></td>
				</tr>
		  </table>
<?php
}
?>
		</td>
	</tr>
	<tr>
		<td class="dc">
			The Polaroid-o-nizer™ team cannot be held responsible for anything created with the
			Polaroid-o-nizer™. Use at own risk! Contact us at polaroidonizer[at]gmail.com
		</td>
		<td align="right" class="dc">
			Copyright 2004-2005 Polaroid-o-nizer™<br>
			(v.<?php echo VERSION; ?> / <?php echo CREATIONDATE; ?>)
		</td>
	</tr>
</table>
</body>
</html>