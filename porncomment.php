<?php header('Content-Type: text/html; charset=utf-8'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-gb" xml:lang="en-us">
	<head profile="http://www.w3.org/2005/10/profile">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Random Porn Video Comment</title>
		<meta name="keywords" content="nsfw porn pr0n comment text humor" />
		<meta name="description" lang="en-us" content="Refresh for new comment. Idea from /r/Python on Reddit." />
		<meta name="author" content="Ewoud Dronkert" />
		<link rel="shortcut icon" href="../favicon.ico" />
		<style type="text/css">
			body { color: #666; background-color: white; width: 800px; margin: 0 auto; padding: 0; line-height: 1.5em; }
			div#comment { font: 36px 'Palatino', 'Georgia', serif; border: 0; border-radius: 30px; margin: 120px 0 0; padding: 60px; background-color: #fff4f4; text-align: center; }
			div#source { font: 8px 'Arial', 'Helvetica', sans-serif; margin: 10px 0 0; text-align: right; }
			a { text-decoration: none; color: #ccc; }
			a:hover { text-decoration: underline; }
		</style>
	</head>
	<body onclick="location.reload(true)">
		<div id="comment"><?php

$source = array(
	'youporn' => array(
		'url' => 'http://www.youporn.com/random/video/',
		'lnk' => '#<link rel="canonical" href="(.+?)" />#s',
		'msg' => '#<div class="commentContent">\s*<p>(.+?)</p>#s'),
	'pornhub' => array(
		'url' => 'http://www.pornhub.com/random',
		'lnk' => '#<link rel="canonical" href="(.+?)" />#s',
		'msg' => '#<div class="commentMessage">\s*([^\s[].+?)<div#s'),
);

$context = stream_context_create(array(
	'http' => array(
		'method' => 'GET',
		'header' => 'Cookie: age_verified=1' . "\r\n" .
					'Accept-language: ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'] . "\r\n" .
					'User-agent: ' . $_SERVER['HTTP_USER_AGENT'] . "\r\n" .
					'Accept: ' . $_SERVER['HTTP_ACCEPT'] . "\r\n")));

$comment = '';
do {

	$key = array_rand($source);
	$src = $source[$key];

	$html = file_get_contents($src['url'], FALSE, $context);

	$link = '';
	if (preg_match($src['lnk'], $html, $match) !== FALSE) {
		if (isset($match[1]) && strlen($match[1])) {
			$link = trim($match[1]);
		}
	}

	if (preg_match_all($src['msg'], $html, $match) !== FALSE) {
		if (isset($match[1]) && is_array($match[1]) && count($match[1])) {
			$comment = trim($match[1][array_rand($match[1])]);
		}
	}

} while (!strlen($comment));

echo $comment;

?></div>
		<div id="source"><a href="<?php echo htmlspecialchars($link); ?>" title="<?php echo ucfirst($key); ?>" target="_blank">source</a></div>
	</body>
</html>
