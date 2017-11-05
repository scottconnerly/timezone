<?php

use ScottConnerly\TimeZone\TimeZoneSelect;

//probably doesn't in this non-bootstrapped example file.
if(!class_exists('TimeZoneSelect')) {
    include('./src/TimeZoneSelect.php');
}

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Time Zone Select by Scott Connerly</title>
	<!--[if IE]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
</head>
<body id="home">

	<h1>Time Zone Select</h1>
	
    <form>
        <?php echo TimeZoneSelect::get_select_html([
            'country'=>'US',
            'selected'=>'Pacific/Midway'
        ]); ?>
    </form>	

</body>
</html>
