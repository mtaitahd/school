<?php
/**
 * Build correct URLs from role subfolders (avoids /learner/learner/ when using ../learner/)
 */
function app_script_dir(): string {
    return str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
}

function app_in_role_folder(string $role): bool {
    return (bool) preg_match('#/' . preg_quote($role, '#') . '(/|$)#', app_script_dir());
}

function app_web_path(string $path): string {
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $dir = app_script_dir();

    if (preg_match('#^(learner|teacher|parent|admin)/(.+)$#', $path, $m)) {
        $role = $m[1];
        $rest = $m[2];
        if (app_in_role_folder($role)) {
            return $rest;
        }
    }

    $base = $GLOBALS['base_path'] ?? '';
    return $base . $path;
}

function app_site_url(string $path = ''): string {
    $dir = app_script_dir();
    if (preg_match('#/(learner|teacher|parent|admin)$#', $dir)) {
        $site = preg_replace('#/(learner|teacher|parent|admin)$#', '', $dir);
    } else {
        $site = $dir;
    }
    return rtrim($site, '/') . '/' . ltrim($path, '/');
}

function app_learner_url(string $page, array $query = []): string {
    $page = ltrim($page, '/');
    if (app_in_role_folder('learner')) {
        $url = $page;
    } else {
        $url = app_site_url('learner/' . $page);
    }
    if ($query) {
        $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($query);
    }
    return $url;
}
