<?php
// Section 04 — Common tasks
// Everyday actions the client can do on their own.
// 'title' and 'desc' are required. 'path' and 'label' are optional (adds a button).
// 'path' is the admin path passed to admin_url() — never hardcode the domain.
return [
	[
		'title' => 'Update text on a page',
		'desc'  => 'Go to Pages → click the page → click any text block to edit → click Update (top right) when done.',
	],
	[
		'title' => 'Replace an image',
		'desc'  => 'Upload your new image to Media first. Then go to the page, click the image block, and select Replace → choose from Media Library.',
	],
	[
		'title' => 'Publish a new blog post',
		'desc'  => 'Go to Posts → Add New. Add a title, write your content, set a Featured Image, then click Publish.',
	],
	[
		'title' => 'Update contact information',
		'desc'  => 'Your phone number, email, and address appear in multiple places. Edit the Contact page and any footer settings. Ask us if you\'re unsure.',
	],
	[
		'title' => 'Change your admin password',
		'desc'  => 'Go to Dashboard → Users → Your Profile → Account Management → Set New Password. Save the new password somewhere safe.',
	],
	[
		'title' => 'Update navigation menus',
		'desc'  => 'Add, remove, or reorder links in your site\'s navigation here.',
		'path'  => 'nav-menus.php',
		'label' => 'Open Menus',
	],
];
