/**
 * KONA YA HISABATI - Main JavaScript
 * Audio prompts, interactions, accessibility
 */

const audioPlayer = document.getElementById('audioPlayer');

function playAudio(text) {
    if (!text) return;
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 0.85;
        utterance.pitch = 1.15;
        utterance.volume = 1;
        const voices = window.speechSynthesis.getVoices();
        const preferred = voices.find(v =>
            v.name.includes('Female') ||
            v.name.includes('Samantha') ||
            v.name.includes('Google US English')
        );
        if (preferred) utterance.voice = preferred;
        window.speechSynthesis.speak(utterance);
    }
}

function getPageLang() {
    return new URLSearchParams(window.location.search).get('lang') || 'en';
}

/** Always resolve to /school/learner/ — never learner/learner/ */
function getLearnerBase() {
    if (window.KONA_PATHS && window.KONA_PATHS.inLearnerFolder) {
        return '';
    }
    if (window.KONA_PATHS && window.KONA_PATHS.learner) {
        let base = window.KONA_PATHS.learner.replace(/\/?$/, '/');
        base = base.replace(/\/learner\/learner\//g, '/learner/');
        return base;
    }
    const path = window.location.pathname.replace(/\\/g, '/');
    const marker = '/learner/';
    const idx = path.indexOf(marker);
    if (idx >= 0) {
        return path.substring(0, idx + marker.length);
    }
    const parts = path.split('/').filter(Boolean);
    const last = parts[parts.length - 1] || '';
    if (last.includes('.')) {
        parts.pop();
    }
    return '/' + parts.join('/') + '/learner/';
}

function selectModule(moduleId) {
    const lang = getPageLang();
    window.location.href = getLearnerBase() + 'activities.php?module_id=' + encodeURIComponent(moduleId) + '&lang=' + encodeURIComponent(lang);
}

function selectActivity(activityId) {
    const lang = getPageLang();
    window.location.href = getLearnerBase() + 'activity.php?activity_id=' + encodeURIComponent(activityId) + '&lang=' + encodeURIComponent(lang);
}

function showStarAnimation() {
    const star = document.createElement('div');
    star.className = 'star-animation';
    star.innerHTML = '<i class="fas fa-star" aria-hidden="true"></i>';
    star.setAttribute('role', 'img');
    star.setAttribute('aria-label', 'Correct answer');
    document.body.appendChild(star);
    playAudio('Good job! You did it!');
    setTimeout(() => star.remove(), 1000);
}

function showErrorFeedback() {
    playAudio('Try again!');
}

function validateAnswer(selectedAnswer, correctAnswer, button) {
    if (selectedAnswer === correctAnswer) {
        button.classList.add('correct');
        showStarAnimation();
        return true;
    }
    button.classList.add('incorrect');
    showErrorFeedback();
    setTimeout(() => button.classList.remove('incorrect'), 500);
    return false;
}

function updateProgress(percentage) {
    const progressBar = document.querySelector('.progress-fill');
    if (progressBar) {
        progressBar.style.width = percentage + '%';
        progressBar.textContent = Math.round(percentage) + '%';
    }
}

function nextActivity(activityId) {
    selectActivity(activityId);
}

function returnToModules() {
    window.location.href = 'index.php';
}

function initNavbar() {
    const hamburger = document.getElementById('hamburgerBtn');
    const menu = document.getElementById('navbarMenu');
    if (!hamburger || !menu) return;

    hamburger.addEventListener('click', () => {
        const open = menu.classList.toggle('active');
        hamburger.setAttribute('aria-expanded', open ? 'true' : 'false');
        hamburger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        const mobileLogin = menu.querySelector('.navbar-login-group-mobile');
        if (mobileLogin) {
            mobileLogin.style.display = open ? 'flex' : 'none';
            mobileLogin.style.flexDirection = 'column';
        }
    });

    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && !hamburger.contains(e.target)) {
            menu.classList.remove('active');
            hamburger.setAttribute('aria-expanded', 'false');
        }
    });
}

function initModuleCards() {
    document.querySelectorAll('.module-card[data-audio-prompt]').forEach(card => {
        let spoke = false;
        card.addEventListener('mouseenter', function () {
            if (spoke) return;
            const prompt = this.getAttribute('data-audio-prompt');
            if (prompt) playAudio(prompt);
            spoke = true;
            setTimeout(() => { spoke = false; }, 3000);
        });
    });
}

function initAccessibility() {
    const contrastBtn = document.getElementById('toggleContrast');
    const dyslexiaBtn = document.getElementById('toggleDyslexia');

    if (contrastBtn) {
        contrastBtn.addEventListener('click', () => {
            document.body.classList.toggle('high-contrast');
        });
    }
    if (dyslexiaBtn) {
        dyslexiaBtn.addEventListener('click', () => {
            document.body.classList.toggle('dyslexia-mode');
        });
    }
}

function initBounceButtons() {
    document.querySelectorAll('.btn-bounce').forEach(btn => {
        btn.addEventListener('click', function () {
            this.classList.remove('btn-bounce');
            void this.offsetWidth;
            this.classList.add('btn-bounce');
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initNavbar();
    initModuleCards();
    initAccessibility();
    initBounceButtons();
    initHeroBackgroundSlider();

    if ('speechSynthesis' in window) {
        window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
    }
});

/* Hero Background Slider Functions */
let currentBgIndex = 0;
let bgInterval;
const bgImages = [
    'assets/images/1.jpeg',
    'assets/images/2.jpeg',
    'assets/images/3.jpeg',
    'assets/images/4.jpeg'
];

function initHeroBackgroundSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    if (!slides.length) return;

    // Start slide slideshow
    bgInterval = setInterval(() => {
        // Remove active class from current slide
        slides[currentBgIndex].classList.remove('active');
        slides[currentBgIndex].classList.add('prev-slide');

        // Move to next slide
        currentBgIndex = (currentBgIndex + 1) % slides.length;

        // Add active class to new slide
        slides[currentBgIndex].classList.add('active');

        // Remove prev-slide class from the slide that was before the current one
        const prevIndex = (currentBgIndex - 1 + slides.length) % slides.length;
        setTimeout(() => {
            slides[prevIndex].classList.remove('prev-slide');
        }, 800);
    }, 10000);
}
