<?php
$comments = new Bricks\Element_Post_Comments(
	[
		'settings' => [
			'title'  => true,
			'avatar' => true,
		],
	]
);

$comments->load();

$comments->init();
