<?php
// Section 06 — Support & contact
// Enter contact details directly here as strings.
// warranty_months: 1 | 3 | 6 | 12
// warranty_start:  'YYYY/MM/DD'
return [
	'warranty_months' => 6,
	'warranty_start'  => 'today',
	'contacts'        => [
//		[ 'Email (general support)', GUIDE_AUTHOR_EMAIL,  'Response within 1 business day' ],
//		[ 'WhatsApp (urgent)',        GUIDE_AUTHOR_WA,     'For site-down or critical issues' ],
//		[ 'Project manager',          GUIDE_AUTHOR_PM,     'Your dedicated point of contact' ],
//		[ 'Support hours',            GUIDE_SUPPORT_HOURS, 'Vietnam time (ICT, UTC+7)' ],
	],
	'covered'         => [
		[true, 'Bug fixes related to the original build'],
		[true, 'Guidance on using the WordPress dashboard'],
		[true, 'Coordinating safe plugin and core updates'],
		[false, 'New features, redesigns, or content creation (quoted separately)'],
		[false, 'Issues caused by client-side changes to restricted areas'],
	],
];
