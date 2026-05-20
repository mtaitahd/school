<?php
/**
 * SEO meta tags for public pages (Google Search Console friendly)
 * Expects: $page_title, $page_description (optional), $current_lang, $canonical_path (optional)
 */
$seo_title = $page_title ?? 'Kona Ya Hisabati - Math Learning Corner';
$seo_desc = $page_description ?? 'Kona Ya Hisabati is an interactive mathematics learning platform for Pre-Primary learners in Tanzania. Child-friendly numeracy activities for teachers, parents, and pupils.';
$seo_lang = ($current_lang ?? 'en') === 'sw' ? 'sw' : 'en';
$site_name = 'Kona Ya Hisabati';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . '://' . $host;
$script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$script = preg_replace('/\.php$/', '', $script);
$canonical = $canonical_path ?? $base_url . $script;
if (!empty($_GET['lang'])) {
    $canonical .= (strpos($canonical, '?') !== false ? '&' : '?') . 'lang=' . urlencode($_GET['lang']);
}
?>
<meta name="description" content="<?php echo htmlspecialchars($seo_desc); ?>">
<meta name="keywords" content="Kona Ya Hisabati, mathematics, Pre-Primary, Tanzania, numeracy, early childhood education, hisabati">
<meta name="author" content="Kona Ya Hisabati">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?php echo htmlspecialchars($canonical); ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($seo_title); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($seo_desc); ?>">
<meta property="og:url" content="<?php echo htmlspecialchars($canonical); ?>">
<meta property="og:locale" content="<?php echo $seo_lang === 'sw' ? 'sw_TZ' : 'en_US'; ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($seo_title); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($seo_desc); ?>">
<meta name="google-site-verification" content="">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "name": "Kona Ya Hisabati",
  "description": <?php echo json_encode($seo_desc); ?>,
  "url": <?php echo json_encode($base_url); ?>,
  "inLanguage": ["en", "sw"],
  "audience": {
    "@type": "EducationalAudience",
    "educationalRole": "student",
    "audienceType": "Pre-Primary learners"
  }
}
</script>
