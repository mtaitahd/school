<?php
/**
 * Absolute web paths for learner navigation (avoids learner/learner/ bugs)
 */
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
if (preg_match('#/learner$#', $scriptDir)) {
    $learner_web = $scriptDir . '/';
    $site_web = preg_replace('#/learner$#', '', $scriptDir) . '/';
} else {
    $learner_web = rtrim($scriptDir, '/') . '/learner/';
    $site_web = rtrim($scriptDir, '/') . '/';
}
?>
<script>
window.KONA_PATHS = {
    base: <?php echo json_encode($site_web); ?>,
    learner: <?php echo json_encode($learner_web); ?>,
    inLearnerFolder: <?php echo json_encode((bool) preg_match('#/learner(/|$)#', str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')))); ?>
};
</script>
