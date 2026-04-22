<?php
// Rewrite footer widgets 10, 11, 12 with tight, dark-friendly markup.
// Widget 13 (disclaimer) is left untouched.
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$w = get_option('widget_custom_html');
if (!is_array($w)) { echo "widget_custom_html not found\n"; exit(1); }

// Brand palette (dark footer: bg = #231f20 Raisin Black)
// #ffffff = white (headings)
// #e7e8ea = platinum (primary text)
// #bbcdd1 = silver sand (labels, muted, links)

$brand = <<<HTML
<div class="ecec-footer-brand">
<h3 style="font-size:18px;font-weight:700;letter-spacing:3px;margin:0 0 8px;color:#ffffff;">ECEC</h3>
<p style="font-size:12.5px;line-height:1.5;color:#e7e8ea;margin:0 0 12px;">A prominent engineering consultancy based in Dubai, UAE, with offices in Riyadh and Amman. Delivering innovative solutions across MEP, Structural, ICT, BIM, Acoustics, and Security engineering.</p>
<a href="https://www.linkedin.com/company/ecec-engineering/" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #bbcdd1;border-radius:50%;color:#e7e8ea;text-decoration:none;font-weight:700;font-size:13px;font-family:Georgia,serif;">in</a>
</div>
HTML;

$links = <<<HTML
<div class="ecec-footer-links">
<h6 style="font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:#bbcdd1;margin:0 0 10px;">Quick Links</h6>
<ul style="list-style:none;padding:0;margin:0;">
<li style="margin:0 0 4px;"><a href="/ecec/" style="color:#e7e8ea;text-decoration:none;font-size:13px;">Home</a></li>
<li style="margin:0 0 4px;"><a href="/ecec/about-us/" style="color:#e7e8ea;text-decoration:none;font-size:13px;">About Us</a></li>
<li style="margin:0 0 4px;"><a href="/ecec/our-services/" style="color:#e7e8ea;text-decoration:none;font-size:13px;">Our Services</a></li>
<li style="margin:0 0 4px;"><a href="/ecec/projects/" style="color:#e7e8ea;text-decoration:none;font-size:13px;">Projects</a></li>
<li style="margin:0 0 4px;"><a href="/ecec/people/" style="color:#e7e8ea;text-decoration:none;font-size:13px;">People</a></li>
<li style="margin:0;"><a href="/ecec/contact-us/" style="color:#e7e8ea;text-decoration:none;font-size:13px;">Contact Us</a></li>
</ul>
</div>
HTML;

$contact = <<<HTML
<div class="ecec-footer-contact">
<h6 style="font-size:10px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:#bbcdd1;margin:0 0 10px;">Our Offices</h6>
<div style="margin:0 0 8px;">
<p style="font-size:13px;font-weight:600;color:#ffffff;margin:0;line-height:1.3;">Dubai</p>
<p style="font-size:12px;color:#e7e8ea;margin:0;line-height:1.35;">Business Bay, Dubai, UAE</p>
</div>
<div style="margin:0 0 8px;">
<p style="font-size:13px;font-weight:600;color:#ffffff;margin:0;line-height:1.3;">Riyadh</p>
<p style="font-size:12px;color:#e7e8ea;margin:0;line-height:1.35;">Olaya, Riyadh, KSA</p>
</div>
<div style="margin:0;">
<p style="font-size:13px;font-weight:600;color:#ffffff;margin:0;line-height:1.3;">Amman</p>
<p style="font-size:12px;color:#e7e8ea;margin:0;line-height:1.35;">Mecca Street, Amman, Jordan</p>
</div>
<div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,0.15);">
<p style="font-size:12px;color:#e7e8ea;margin:0 0 2px;line-height:1.4;"><strong style="color:#bbcdd1;font-weight:600;">Email:</strong> <a href="mailto:info@ecec.co" style="color:#e7e8ea;text-decoration:none;">info@ecec.co</a></p>
<p style="font-size:12px;color:#e7e8ea;margin:0;line-height:1.4;"><strong style="color:#bbcdd1;font-weight:600;">HR:</strong> <a href="mailto:hr@ecec.co" style="color:#e7e8ea;text-decoration:none;">hr@ecec.co</a></p>
</div>
</div>
HTML;

$w[10]['content'] = $brand;
$w[11]['content'] = $links;
$w[12]['content'] = $contact;

update_option('widget_custom_html', $w);

echo "updated widgets 10, 11, 12\n";
echo "10: " . strlen($brand) . " bytes\n";
echo "11: " . strlen($links) . " bytes\n";
echo "12: " . strlen($contact) . " bytes\n";
