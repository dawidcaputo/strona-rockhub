let currentSlide = 0;
let slides, dots;

function buildDots() {
    const carousel = document.querySelector('.carousel');
    const dotsEl = document.createElement('div');
    dotsEl.className = 'dots';
    slides.forEach((_, i) => {
        const d = document.createElement('span');
        d.className = 'dot' + (i === 0 ? ' active' : '');
        d.addEventListener('click', () => showSlide(i));
        dotsEl.appendChild(d);
    });
    carousel.appendChild(dotsEl);
    dots = dotsEl.querySelectorAll('.dot');
}

function showSlide(n) {
    currentSlide = (n + slides.length) % slides.length;

    slides.forEach(s => s.classList.remove('active'));
    slides[currentSlide].classList.add('active');

    if (dots) {
        dots.forEach(d => d.classList.remove('active'));
        dots[currentSlide].classList.add('active');
    }
}

function plusSlides(dir) {
    showSlide(currentSlide + dir);
}

document.addEventListener('DOMContentLoaded', () => {
    slides = document.querySelectorAll('.car');
    if (!slides.length) return;
    buildDots();
    showSlide(0);
});

// ── AUTH TABS ──
function switchTab(tab, clickedBtn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));

    if (clickedBtn) {
        clickedBtn.classList.add('active');
    } else {
        // wywołane z linka – znajdź odpowiedni przycisk
        const idx = tab === 'login' ? 0 : 1;
        document.querySelectorAll('.tab-btn')[idx].classList.add('active');
    }

    document.getElementById('form-' + tab).classList.add('active');
}