<?php
// Section 01 — Dashboard overview
// Fixed across all projects — only edit if the WP admin structure changes.
// 'path' is the admin path passed to admin_url(), never hardcode the domain.
return [
	[
		'slug'  => 'dashboard',
		'title' => 'Dashboard',
		'path'  => 'index.php',
		'desc'  => 'Your home screen. Shows site health, recent activity, and plugin notices. No action needed here unless there is a red alert.',
	],
	[
		'slug'  => 'page',
		'title' => 'Pages',
		'path'  => 'edit.php?post_type=page',
		'desc'  => 'All the main pages of your site — Home, About, Services, Contact, and more. Edit content here using the block editor.',
	],
	[
		'slug'  => 'post',
		'title' => 'Posts (Blog)',
		'path'  => 'edit.php',
		'desc'  => 'Blog articles and news updates. Create and publish new posts here. Set a Featured Image and category before publishing.',
	],
	[
		'slug'  => 'attachment',
		'title' => 'Media',
		'path'  => 'upload.php',
		'desc'  => 'Your image and file library. Always upload new photos here first, then insert them into pages. Keep images under 500 KB.',
	],
	[
		'slug'  => 'users',
		'title' => 'Users',
		'path'  => 'users.php',
		'desc'  => 'Add or manage team members who need dashboard access. Use the Editor role for staff who only manage content — not Admin.',
	],
	[
		'slug'  => 'plugins',
		'title' => 'Plugins & Settings',
		'path'  => 'plugins.php',
		'desc'  => 'Behind-the-scenes features. Do not update, install, or deactivate plugins unless instructed by us.',
	],
];
