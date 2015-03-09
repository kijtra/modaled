<?php
sleep(2);
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>ajax</title>
</head>
<body>
	GET:
	<pre><?php var_dump($_GET); ?></pre>
	<hr>
	POST:
	<pre><?php var_dump($_POST); ?></pre>
	<hr>
	<a href="#" class="modaler">close</a>
</body>
</html>