<?php
// =============================================================================
// ✏️  CONFIG — edit this block per client
// =============================================================================

define('GUIDE_BRAND_COLOR', '#1D6FA8');
define('GUIDE_AUTHOR_NAME', 'Layơ Lab');
define('GUIDE_CLIENT_NAME', 'DMD');
// Set to a parent menu slug (e.g. 'theme-options') to nest as submenu, or '' for top-level.
define('GUIDE_PARENT_SLUG', '');
// =============================================================================

add_action('admin_menu', function(){
	add_menu_page(
		'Website Guide',
		'Website Guide',
		'read',
		'website-guide',
		'wg_render_page',
		'dashicons-book-alt',
		3
	);

	if(GUIDE_PARENT_SLUG){
		// Hide from top-level; appear under parent instead (URL stays admin.php?page=website-guide).
		remove_menu_page('website-guide');
		add_submenu_page(
			GUIDE_PARENT_SLUG,
			'Website Guide',
			'Website Guide',
			'read',
			'website-guide',
			'wg_render_page'
		);
	}
});

// -----------------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------------

function wg_img(string $slug): string{
	foreach(['gif', 'png', 'jpg', 'jpeg', 'webp'] as $ext){
		$path = __DIR__ . '/public/' . $slug . '.' . $ext;
		if(file_exists($path)){
			$url = plugin_dir_url(__FILE__) . 'public/' . $slug . '.' . $ext;

			return '<div class="pg-screenshot"><img src="' . esc_url($url) . '" alt="' . esc_attr($slug) . '" /></div>';
		}
	}

	return '';
}

function wg_section_item(string $num, string $title, string $desc, string $link = '', string $link_label = '', string $img_slug = ''): void{
	echo '<div class="pg-item">';
	echo '<div class="pg-item-num">' . esc_html($num) . '</div>';
	echo '<div class="pg-item-body">';
	echo '<div class="pg-item-title">' . esc_html($title) . '</div>';
	echo '<div class="pg-item-desc">' . esc_html($desc) . '</div>';
	if($link){
		echo '<div class="pg-item-links">';
		echo '<a href="' . esc_url($link) . '" class="pg-link">' . esc_html($link_label ?: 'Go to ' . $title) . '</a>';
		echo '</div>';
	}
	if($img_slug){
		echo wg_img($img_slug);
	}
	echo '</div>';
	echo '</div>';
}

function wg_page(string $eyebrow, string $title, string $body, string $page_num, callable $content, string $id = ''): void{
	$id_attr = $id ? ' id="' . esc_attr($id) . '"' : '';
	echo '<div class="pg-page"' . $id_attr . '>';
	echo '<div class="pg-section-label">' . esc_html($eyebrow) . '</div>';
	echo '<div class="pg-section-title">' . esc_html($title) . '</div>';
	if($body){
		echo '<p class="pg-body">' . esc_html($body) . '</p>';
	}
	$content();
	echo '<div class="pg-page-footer">';
	echo '<span>' . esc_html(GUIDE_AUTHOR_NAME) . '</span>';
	echo '<span>' . esc_html((new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh')))->format('M j, Y · H:i')) . '</span>';
	echo '</div>';
	echo '</div>';
}

// -----------------------------------------------------------------------------
// Render
// -----------------------------------------------------------------------------

function wg_render_page(): void{
	$brand    = GUIDE_BRAND_COLOR;
	$img_base = plugin_dir_url(__FILE__) . 'public/';

	// Load section content from files
	$content_dir   = __DIR__ . '/content/';
	$builtin       = require $content_dir . '01-dashboard-overview.php';
	$special_pages = require $content_dir . '03-key-areas.php';
	$tasks         = require $content_dir . '04-common-tasks.php';
	$warnings      = require $content_dir . '05-do-not-touch.php';
	$support       = require $content_dir . '06-support-contact.php';

	// Collect custom post types: only those with a screenshot in public/.
	// Adding a gif/png for a CPT slug is the opt-in — no config needed.
	$builtin_slugs = array_column($builtin, 'slug');
	$public_files  = glob(__DIR__ . '/public/*.{gif,png,jpg,jpeg,webp}', GLOB_BRACE) ?: [];
	$file_slugs    = array_diff(
		array_map(fn($f) => pathinfo($f, PATHINFO_FILENAME), $public_files),
		$builtin_slugs
	);
	$all_pts    = get_post_types([], 'objects');
	$custom_pts = array_filter($all_pts, fn($pt) => in_array($pt->name, $file_slugs, true));

	// TOC — label + anchor id for each section
	$toc_sections = [
		['label' => '01 — Dashboard overview', 'id' => 'dashboard-overview'],
		['label' => '02 — Your website content', 'id' => 'your-website-content'],
		['label' => '03 — Key areas', 'id' => 'special-pages'],
		['label' => '04 — Common tasks', 'id' => 'common-tasks'],
		['label' => '05 — Do not touch', 'id' => 'do-not-touch'],
		['label' => '06 — Support & contact', 'id' => 'support-contact'],
	];

	?>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&subset=vietnamese&display=swap"
          rel="stylesheet"/>

    <style>
        /* Reset WP admin bleed inside our root */
        .pg-root, .pg-root * { box-sizing:border-box; }
        .pg-root h1, .pg-root h2, .pg-root h3,
        .pg-root h4, .pg-root h5, .pg-root h6 {
            font-size:unset; font-weight:unset; line-height:unset;
            margin:0; padding:0; color:unset; border:none;
        }
        .pg-root p { margin:0; padding:0; }
        .pg-root a { text-decoration:none; color:inherit; }
        .pg-root ul, .pg-root li { margin:0; padding:0; }
        .pg-root button { font-family:inherit; }

        /* Layout */
        #wpcontent, #wpbody { padding:0 !important; }
        #wpfooter { display:none; }

        .pg-wrap {
            display:flex;
            flex-direction:column;
            height:calc(100vh - 32px);
            overflow:hidden;
            background:#f4f1ec;
        }

        /* Admin bar */
        .pg-bar {
            display:flex;
            align-items:center;
            gap:1rem;
            padding:0 1.5rem;
            height:48px;
            background:#fff;
            border-bottom:1px solid #e0ddd6;
            flex-shrink:0;
        }

        .pg-bar-logo {
            font-family:'Lora', serif;
            font-size:17px;
            letter-spacing:-0.5px;
            color:#1a1a1a;
            font-weight:400;
        }

        .pg-bar-logo span { color:<?php echo esc_attr($brand); ?>; }

        .pg-bar-client { font-size:12px; color:#999; flex:1; }

        .pg-bar-print {
            font-size:11px;
            font-weight:500;
            letter-spacing:0.06em;
            text-transform:uppercase;
            color:#1a1a1a;
            background:#fff;
            border:0.5px solid<?php echo esc_attr($brand); ?>;
            border-radius:4px;
            padding:0.45rem 1rem;
            cursor:pointer;
            display:flex;
            align-items:center;
            gap:6px;
            transition:background 0.15s, color 0.15s, border-color 0.15s;
        }

        .pg-bar-print:hover { background:#1a1a1a; color:#fff; border-color:#1a1a1a; }

        /* Scrollable content area */
        .pg-scroll {
            flex:1;
            overflow-y:auto;
            padding:2rem 1rem;
            scroll-behavior:smooth;
        }

        /* Pages */
        .pg-root { max-width:720px; margin:0 auto; }

        .pg-page {
            background:#fff;
            margin-bottom:2rem;
            padding:3rem 3.5rem;
            border-radius:4px;
            position:relative;
            overflow:hidden;
        }

        .pg-page::before {
            content:'';
            position:absolute;
            top:0; left:0; right:0;
            height:5px;
            background:<?php echo esc_attr($brand); ?>;
        }

        /* Cover */
        .pg-cover {
            min-height:400px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
        }

        .pg-cover-top {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
        }

        .pg-logo-mark {
            font-family:'Lora', serif;
            font-size:22px;
            letter-spacing:-0.5px;
            color:#1a1a1a;
        }

        .pg-logo-mark span { color:<?php echo esc_attr($brand); ?>; }

        .pg-doc-tag {
            font-size:11px;
            letter-spacing:0.12em;
            text-transform:uppercase;
            color:#888;
            font-weight:500;
        }

        .pg-cover-body {
            flex:1;
            display:flex;
            flex-direction:column;
            justify-content:center;
            padding:3rem 0 2rem;
        }

        .pg-cover-eyebrow {
            font-size:11px;
            letter-spacing:0.14em;
            text-transform:uppercase;
            color:<?php echo esc_attr($brand); ?>;
            font-weight:500;
            margin-bottom:1rem;
        }

        .pg-cover-title {
            font-family:'Lora', serif;
            font-size:42px;
            line-height:1.1;
            color:#1a1a1a;
            margin-bottom:1.5rem;
            letter-spacing:-1px;
        }

        .pg-cover-title em { font-style:italic; color:<?php echo esc_attr($brand); ?>; }

        .pg-cover-desc {
            font-size:14px;
            color:#666;
            line-height:1.7;
            max-width:380px;
        }

        .pg-cover-footer {
            display:flex;
            justify-content:space-between;
            align-items:flex-end;
            padding-top:2rem;
            border-top:0.5px solid #e0ddd6;
        }

        .pg-client-field { font-size:12px; color:#999; }

        .pg-client-field strong {
            display:block;
            font-size:14px;
            font-weight:500;
            color:#1a1a1a;
            margin-top:4px;
        }

        /* Typography */
        .pg-section-label {
            font-size:10px;
            letter-spacing:0.16em;
            text-transform:uppercase;
            color:<?php echo esc_attr($brand); ?>;
            font-weight:500;
            margin-bottom:0.75rem;
        }

        .pg-section-title {
            font-family:'Lora', serif;
            font-size:24px;
            color:#1a1a1a;
            letter-spacing:-0.5px;
            margin-bottom:1.25rem;
        }

        .pg-body {
            font-family:'DM Sans', sans-serif;
            font-size:13.5px;
            line-height:1.75;
            color:#444;
            margin-bottom:1.25rem;
        }

        .pg-divider { height:0.5px; background:#e0ddd6; margin:2rem 0; }

        /* TOC */
        .pg-toc-item {
            display:flex;
            justify-content:space-between;
            align-items:baseline;
            padding:0.6rem 0;
            border-bottom:0.5px dotted #d8d4cc;
            font-family:'DM Sans', sans-serif;
            color:inherit;
            transition:color 0.12s;
        }

        .pg-toc-item:last-child { border-bottom:none; }
        .pg-toc-item:hover .pg-toc-name { color:<?php echo esc_attr($brand); ?>; }
        .pg-toc-name { font-size:13.5px; color:#333; transition:color 0.12s; }
        .pg-toc-pg { font-size:12px; color:#bbb; }

        /* Items (dashboard + tasks) */
        .pg-item {
            display:flex;
            gap:1rem;
            padding:1rem 0;
            border-bottom:0.5px solid #eee;
            align-items:flex-start;
        }

        .pg-item:last-child { border-bottom:none; }

        .pg-item-num {
            font-family:'Lora', serif;
            font-size:18px;
            color:#d8c9a8;
            line-height:1.2;
            min-width:28px;
            flex-shrink:0;
        }

        .pg-item-num.check { font-size:14px; color:<?php echo esc_attr($brand); ?>; font-family:'DM Sans', sans-serif; }
        .pg-item-num.dash { font-size:14px; color:#bbb; font-family:'DM Sans', sans-serif; }

        .pg-item-title {
            font-family:'DM Sans', sans-serif;
            font-size:13.5px;
            font-weight:500;
            color:#1a1a1a;
            margin-bottom:4px;
        }

        .pg-item-desc {
            font-family:'DM Sans', sans-serif;
            font-size:12.5px;
            color:#666;
            line-height:1.65;
        }

        .pg-item-desc.muted { color:#bbb; }

        .pg-item-links { margin-top:8px; display:flex; gap:8px; flex-wrap:wrap; }

        .pg-link {
            display:inline-block;
            font-family:'DM Sans', sans-serif;
            font-size:11px;
            font-weight:500;
            letter-spacing:0.04em;
            color:<?php echo esc_attr($brand); ?>;
            border:0.5px solid<?php echo esc_attr($brand); ?>;
            border-radius:3px;
            padding:3px 10px;
            text-decoration:none;
            transition:background 0.12s, color 0.12s;
        }

        .pg-link:hover { background:<?php echo esc_attr($brand); ?>; color:#fff; }

        /* Screenshots */
        .pg-screenshot {
            margin-top:0.75rem;
            border-radius:4px;
            overflow:hidden;
            border:0.5px solid #e0ddd6;
        }

        .pg-screenshot img { display:block; width:100%; height:auto; }

        /* CPT divider label */
        .pg-cpt-divider {
            font-size:10px;
            letter-spacing:0.14em;
            text-transform:uppercase;
            color:#bbb;
            font-weight:500;
            padding:1rem 0 0.5rem;
            border-top:0.5px solid #eee;
            margin-top:0.5rem;
        }

        /* Do-not-touch cards */
        .pg-dnt-card {
            border:1px solid #f0ece4;
            border-left:3px solid #c8a96e;
            border-radius:0 4px 4px 0;
            padding:0.9rem 1.1rem;
            margin-bottom:0.6rem;
        }

        .pg-dnt-card-title {
            font-family:'DM Sans', sans-serif;
            font-size:13px;
            font-weight:600;
            color:#1a1a1a;
            margin-bottom:4px;
        }

        .pg-dnt-card-desc {
            font-family:'DM Sans', sans-serif;
            font-size:12.5px;
            color:#666;
            line-height:1.6;
        }

        /* Warranty block */
        .pg-warranty {
            border:1px solid<?php echo esc_attr($brand); ?>;
            border-radius:4px;
            padding:1.25rem 1.5rem;
            margin-bottom:1.5rem;
        }

        .pg-warranty-title {
            font-family:'DM Sans', sans-serif;
            font-size:11px;
            font-weight:500;
            letter-spacing:0.1em;
            text-transform:uppercase;
            color:<?php echo esc_attr($brand); ?>;
            margin-bottom:0.5rem;
        }

        .pg-warranty-dates {
            font-family:'Lora', serif;
            font-size:18px;
            color:#1a1a1a;
            letter-spacing:-0.3px;
            margin-bottom:0.4rem;
        }

        .pg-warranty-sub {
            font-family:'DM Sans', sans-serif;
            font-size:12px;
            color:#999;
        }

        .pg-warranty-expired {
            font-family:'DM Sans', sans-serif;
            font-size:13px;
            color:#c0392b;
        }

        /* Warning box */
        .pg-warning {
            background:#fdf7ee;
            border:1px solid #e8c97a;
            border-radius:4px;
            padding:1.25rem 1.5rem;
            margin-bottom:1rem;
        }

        .pg-warning-header {
            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:0.75rem;
        }

        .pg-warning-icon {
            width:20px; height:20px;
            background:#c8a96e;
            border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:11px; color:#fff; font-weight:500; flex-shrink:0;
        }

        .pg-warning-title { font-family:'DM Sans', sans-serif; font-size:13px; font-weight:500; color:#7a5c1e; }

        .pg-warning-list { list-style:none; padding:0; }

        .pg-warning-list li {
            font-family:'DM Sans', sans-serif;
            font-size:12.5px;
            color:#7a5c1e;
            padding:4px 0 4px 1rem;
            position:relative;
            line-height:1.6;
        }

        .pg-warning-list li::before { content:'—'; position:absolute; left:0; color:#c8a96e; }

        /* Contact grid */
        .pg-contact-grid {
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:1rem;
            margin-top:1rem;
        }

        .pg-contact-card {
            border:0.5px solid #d8d4cc;
            border-radius:4px;
            padding:1rem 1.25rem;
        }

        .pg-contact-method {
            font-family:'DM Sans', sans-serif;
            font-size:10px;
            letter-spacing:0.1em;
            text-transform:uppercase;
            color:#999;
            margin-bottom:6px;
        }

        .pg-contact-value { font-family:'DM Sans', sans-serif; font-size:13px; font-weight:500; color:#1a1a1a; }
        .pg-contact-note { font-family:'DM Sans', sans-serif; font-size:11px; color:#999; margin-top:3px; }

        /* Page footer */
        .pg-page-footer {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:2.5rem;
            padding-top:1.25rem;
            border-top:0.5px solid #e0ddd6;
            font-family:'DM Sans', sans-serif;
            font-size:11px;
            color:#bbb;
        }

        .pg-badge {
            display:inline-block;
            background:#f4f1ec;
            border:0.5px solid #d8d4cc;
            border-radius:2px;
            font-size:10px;
            letter-spacing:0.08em;
            text-transform:uppercase;
            color:#888;
            padding:3px 8px;
            font-weight:500;
        }

        /* Back cover */
        .pg-back-cover {
            text-align:center;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            min-height:260px;
        }

        .pg-back-cover .pg-logo-mark { font-size:28px; margin-bottom:1rem; }

        .pg-back-cover p {
            font-family:'DM Sans', sans-serif;
            font-size:13px;
            color:#999;
            line-height:1.6;
        }

        @media print {
            #adminmenuwrap, #adminmenuback, #wpadminbar,
            #screen-meta, #screen-meta-links,
            .pg-bar, .update-nag, .notice, .wp-header-end { display:none !important; }

            html, body { overflow:visible !important; height:auto !important; background:#fff !important; }
            #wpcontent { margin-left:0 !important; }
            #wpcontent, #wpbody, #wpbody-content { overflow:visible !important; height:auto !important; padding:0 !important; }

            .pg-wrap { display:block; height:auto; overflow:visible; background:#fff; }
            .pg-scroll { display:block; height:auto; overflow:visible; padding:0; }
            .pg-root { max-width:100%; }
            .pg-page { page-break-after:always; border-radius:0; overflow:visible; margin-bottom:0; }
            .pg-page::before { print-color-adjust:exact; -webkit-print-color-adjust:exact; }
        }
    </style>

    <div class="pg-wrap">

        <!-- Admin bar -->
        <div class="pg-bar">
            <div class="pg-bar-logo"><?php echo esc_html(GUIDE_AUTHOR_NAME); ?><span>.</span></div>
            <div class="pg-bar-client"><?php echo esc_html(GUIDE_CLIENT_NAME); ?></div>
            <button class="pg-bar-print" onclick="window.print()">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"
                     stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="1" width="10" height="6" rx="1"/>
                    <path d="M3 11H1.5A.5.5 0 0 1 1 10.5v-4A.5.5 0 0 1 1.5 6h13a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5H13"/>
                    <rect x="3" y="9" width="10" height="6" rx="1"/>
                </svg>
                Print
            </button>
        </div>

        <div class="pg-scroll">
            <div class="pg-root">

                <!-- COVER + TOC -->
                <div class="pg-page pg-cover">
                    <div class="pg-cover-top">
                        <div class="pg-logo-mark"><?php echo esc_html(GUIDE_AUTHOR_NAME); ?><span>.</span></div>
                        <div class="pg-doc-tag">Confidential · Client Copy</div>
                    </div>
                    <div class="pg-cover-body">
                        <div class="pg-cover-eyebrow">Website Handover Document</div>
                        <div class="pg-cover-title">Your website,<br><em>your guide.</em></div>
                        <div class="pg-cover-desc">Everything you need to manage, update, and get the most from your new
                            website — without calling us for every small thing.
                        </div>
                    </div>
                    <div class="pg-section-label" style="margin-top:1.5rem">Contents</div>
					<?php
					foreach($toc_sections as $i => $section):
						echo '<a href="#' . esc_attr($section['id']) . '" class="pg-toc-item">';
						echo '<span class="pg-toc-name">' . esc_html($section['label']) . '</span>';
						echo '<span class="pg-toc-pg">' . ($i + 2) . '</span>';
						echo '</a>';
					endforeach;
					?>
                    <div class="pg-cover-footer">
                        <div class="pg-client-field">Prepared for<strong><?php echo esc_html(GUIDE_CLIENT_NAME); ?></strong></div>
                        <div class="pg-client-field" style="text-align:right">Delivered by<strong><?php echo esc_html(GUIDE_AUTHOR_NAME); ?></strong></div>
                    </div>
                </div>

                <!-- 01 DASHBOARD OVERVIEW -->
				<?php wg_page(
					'01 · Dashboard overview',
					'Finding your way around',
					'When you log into WordPress, you\'ll see the admin sidebar on the left. Here\'s what each section does and when you\'ll use it.',
					'3',
					function() use ($builtin){
						$letters = ['A', 'B', 'C', 'D', 'E', 'F'];
						foreach($builtin as $i => $item){
							wg_section_item(
								$letters[$i] ?? '',
								$item['title'],
								$item['desc'],
								admin_url($item['path']),
								'Open ' . $item['title'],
								$item['slug']
							);
						}
					},
					'dashboard-overview'
				); ?>

                <!-- 02 YOUR WEBSITE CONTENT -->
				<?php wg_page(
					'02 · Your website content',
					'Managing your content types',
					'Your website has custom content sections built specifically for ' . GUIDE_CLIENT_NAME . '. Each one works like a simple list — add, edit, or remove entries from the dashboard.',
					'4',
					function() use ($custom_pts){
						if(!empty($custom_pts)){
							$i = 1;
							foreach($custom_pts as $pt){
								$list_url    = admin_url('edit.php?post_type=' . $pt->name);
								$add_new_url = admin_url('post-new.php?post_type=' . $pt->name);
								echo '<div class="pg-item">';
								echo '<div class="pg-item-num">' . sprintf('%02d', $i) . '</div>';
								echo '<div class="pg-item-body">';
								echo '<div class="pg-item-title">' . esc_html($pt->labels->name) . '</div>';
								echo '<div class="pg-item-desc">Click <strong>Add ' . esc_html($pt->labels->singular_name) . '</strong> to create a new entry. Fill in the fields and click <strong>Publish</strong>. To edit or delete an existing entry, click its title in the list.</div>';
								echo '<div class="pg-item-links">';
								echo '<a href="' . esc_url($list_url) . '" class="pg-link">View all ' . esc_html($pt->labels->name) . '</a>';
								echo '<a href="' . esc_url($add_new_url) . '" class="pg-link">+ Add ' . esc_html($pt->labels->singular_name) . '</a>';
								echo '</div>';
								echo wg_img($pt->name);
								echo '</div>';
								echo '</div>';
								$i++;
							}
						}else{
							echo '<p class="pg-body" style="color:#999;">No custom content types registered yet.</p>';
						}
					},
					'your-website-content'
				); ?>

                <!-- 03 KEY AREAS -->
				<?php wg_page(
					'03 · Key areas',
					'Templates, settings & tools',
					'These areas aren\'t found under regular Pages — they include site templates, global layout parts, settings panels, and tools like forms.',
					'5',
					function() use ($special_pages){
						if(empty($special_pages)){
							echo '<p class="pg-body" style="color:#999;">No special pages configured yet.</p>';

							return;
						}
						foreach($special_pages as $i => $sp){
							wg_section_item(
								sprintf('%02d', $i + 1),
								$sp['title'],
								$sp['desc'],
								admin_url($sp['path']),
								$sp['label'] ?? 'Edit in Site Editor'
							);
						}
					},
					'special-pages'
				); ?>

                <!-- 04 COMMON TASKS -->
				<?php wg_page(
					'04 · Common tasks',
					'Things you can do yourself',
					'These are the everyday updates you\'re fully set up to handle on your own. Each task takes under 5 minutes once you\'re familiar with it.',
					'6',
					function() use ($tasks){
						foreach($tasks as $i => $task){
							wg_section_item(
								sprintf('%02d', $i + 1),
								$task['title'],
								$task['desc'],
								isset($task['path']) ? admin_url($task['path']) : '',
								$task['label'] ?? 'Open'
							);
						}
					},
					'common-tasks'
				); ?>

                <!-- 05 DO NOT TOUCH -->
				<?php wg_page(
					'05 · Do not touch',
					'Leave these to us',
					'Some parts of your site are finely configured and should only be modified by us. Making changes here — even accidentally — can break your website or cause data loss.',
					'7',
					function() use ($warnings){
						foreach($warnings as $w){
							echo '<div class="pg-dnt-card">';
							echo '<div class="pg-dnt-card-title">' . esc_html($w['title']) . '</div>';
							echo '<div class="pg-dnt-card-desc">' . esc_html($w['desc']) . '</div>';
							echo '</div>';
						}
						echo '<p class="pg-body" style="margin-top:1rem">If something looks broken or you\'re unsure whether an action is safe, <strong style="color:#1a1a1a">stop and contact us first</strong> — it\'s always faster to ask than to fix.</p>';
					},
					'do-not-touch'
				); ?>

                <!-- 06 SUPPORT & CONTACT -->
				<?php wg_page(
					'06 · Support & contact',
					'We\'re here when you need us',
					'',
					'8',
					function() use ($support){
						// Warranty block
						$months    = (int) ($support['warranty_months'] ?? 0);
						$start_raw = $support['warranty_start'] ?? '';
						$unset     = ($start_raw === 'today' || empty($start_raw));
						$start     = $unset ? null : new DateTime($start_raw);
						echo '<div class="pg-warranty">';
						echo '<div class="pg-warranty-title">Warranty period</div>';
						if($unset){
							echo '<div class="pg-warranty-sub" style="font-style:italic">Not defined yet — will be confirmed upon launch.</div>';
						}else{
							$expiry  = (clone $start)->modify("+{$months} months");
							$now     = new DateTime();
							$expired = $now > $expiry;
							$fmt     = 'F j, Y';
							if($expired){
								echo '<div class="pg-warranty-expired">Your ' . $months . '-month warranty expired on ' . esc_html($expiry->format($fmt)) . '.</div>';
							}else{
								echo '<div class="pg-warranty-dates">' . esc_html($start->format($fmt)) . ' — ' . esc_html($expiry->format($fmt)) . '</div>';
								echo '<div class="pg-warranty-sub">' . $months . '-month warranty · expires in ' . (int) $now->diff($expiry)->days . ' days</div>';
							}
						}
						echo '</div>';

						// What's covered
						echo '<div class="pg-section-label">What\'s covered during this period</div>';
						foreach($support['covered'] as $item){
							echo '<div class="pg-item" ' . (!$item[0] && $item === end($support['covered']) ? 'style="border-bottom:none"' : '') . '>';
							echo '<div class="pg-item-num ' . ($item[0] ? 'check' : 'dash') . '">' . ($item[0] ? '✓' : '—') . '</div>';
							echo '<div class="pg-item-body"><div class="pg-item-desc' . (!$item[0] ? ' muted' : '') . '">' . esc_html($item[1]) . '</div></div>';
							echo '</div>';
						}

						// Contacts
						echo '<div class="pg-divider"></div>';
						if(!empty($support['contacts'])){
							echo '<div class="pg-section-label">How to reach us</div>';
							echo '<div class="pg-contact-grid">';
							foreach($support['contacts'] as $c){
								echo '<div class="pg-contact-card">';
								echo '<div class="pg-contact-method">' . esc_html($c[0]) . '</div>';
								echo '<div class="pg-contact-value">' . esc_html($c[1]) . '</div>';
								echo '<div class="pg-contact-note">' . esc_html($c[2]) . '</div>';
								echo '</div>';
							}
							echo '</div>';
						}else{
							echo '<p class="pg-body">Reach out via the contact details shared with you during project handover.</p>';
						}
					},
					'support-contact'
				); ?>

                <!-- BACK COVER -->
                <div class="pg-page pg-back-cover">
                    <div class="pg-logo-mark" style="font-size:28px;margin-bottom:1rem"><?php echo esc_html(GUIDE_AUTHOR_NAME); ?><span>.</span></div>
                </div>

            </div><!-- .pg-root -->
        </div><!-- .pg-scroll -->
    </div><!-- .pg-wrap -->
    <style>
        #wpbody-content {padding-bottom:0;}
        #wpfooter + svg {display:none;}
    </style>
	<?php
}
