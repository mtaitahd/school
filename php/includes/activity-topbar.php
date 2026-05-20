<?php
/** Activity page top bar: Home, Back, Audio */
$back_url = $back_url ?? '../learner/categories.php';
$home_url = $home_url ?? '../index.php';
$lang = $current_lang ?? 'en';
?>
<div class="activity-topbar" role="toolbar" aria-label="Activity navigation">
    <a href="<?php echo $home_url; ?>?lang=<?php echo $lang; ?>" class="topbar-btn topbar-home" aria-label="<?php echo htmlspecialchars($t['activity_home'] ?? 'Home'); ?>">
        <i class="fas fa-home" aria-hidden="true"></i>
        <span><?php echo htmlspecialchars($t['activity_home'] ?? 'Home'); ?></span>
    </a>
    <a href="<?php echo htmlspecialchars($back_url); ?>" class="topbar-btn topbar-back" aria-label="<?php echo htmlspecialchars($t['activity_back'] ?? 'Back'); ?>">
        <i class="fas fa-arrow-left" aria-hidden="true"></i>
        <span><?php echo htmlspecialchars($t['activity_back'] ?? 'Back'); ?></span>
    </a>
    <button type="button" class="topbar-btn topbar-audio" id="topbarAudioBtn" onclick="typeof playInstruction === 'function' ? playInstruction() : (typeof playAudio === 'function' && playAudio(activityInstruction || ''))" aria-label="<?php echo htmlspecialchars($t['activity_audio'] ?? 'Listen'); ?>">
        <i class="fas fa-volume-up" aria-hidden="true"></i>
        <span><?php echo htmlspecialchars($t['activity_audio'] ?? 'Listen'); ?></span>
    </button>
</div>
