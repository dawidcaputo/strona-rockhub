// ============================================================
//  script.js – Karuzela + Auth + PHP API
// ============================================================

let currentSlide = 0;
let slides, dots;

// ID koncertu przypisany do każdego slajdu (kolejność = kolejność .car w HTML)
const slideToId = [1, 2, 3, 4];

// Nazwy koncertów dla każdego slajdu (wyświetlane na przycisku)
const slideNames = ['Slipknot', 'Korn', "Guns N' Roses", 'Bring Me The Horizon'];

function buildDots() {
    const carousel = document.querySelector('.carousel');

    // ── przyciski "Kup bilety" na każdym slajdzie ──
    slides.forEach((slide, i) => {
        // gradient na dole slajdu
        const grad = document.createElement('div');
        grad.style.cssText = `
            position:absolute; bottom:0; left:0; right:0; height:45%;
            background:linear-gradient(to top, rgba(0,0,0,0.85) 0%, transparent 100%);
            pointer-events:none; z-index:2; border-radius:0 0 24px 24px;
        `;
        slide.appendChild(grad);

        // nazwa + przycisk
        const info = document.createElement('div');
        info.style.cssText = `
            position:absolute; bottom:32px; left:0; right:0;
            display:flex; flex-direction:column; align-items:center; gap:14px;
            z-index:3; pointer-events:none;
        `;

        const name = document.createElement('span');
        name.textContent = slideNames[i] || '';
        name.style.cssText = `
            font-family:'Limelight',cursive; font-size:28px; color:#fff;
            letter-spacing:2px; text-shadow:0 2px 12px rgba(0,0,0,0.8);
        `;

        const btn = document.createElement('a');
        btn.textContent = 'Kup bilety →';
        btn.href = 'koncert.html?id=' + (slideToId[i] || 1);
        btn.style.cssText = `
            background:#e63946; color:#fff; border:none;
            padding:12px 36px; border-radius:30px;
            font-family:'Inter',sans-serif; font-size:14px;
            font-weight:700; letter-spacing:2px; text-transform:uppercase;
            text-decoration:none; pointer-events:all; cursor:pointer;
            box-shadow:0 4px 20px rgba(230,57,70,0.5);
            transition:background 0.2s, transform 0.15s;
        `;
        btn.onmouseenter = () => { btn.style.background = '#c1121f'; btn.style.transform = 'scale(1.05)'; };
        btn.onmouseleave = () => { btn.style.background = '#e63946'; btn.style.transform = 'scale(1)'; };

        info.appendChild(name);
        info.appendChild(btn);
        slide.appendChild(info);
        slide.style.position = 'relative';
    });

    // ── kropki ──
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

// ── AUTH TABS ──
function switchTab(tab, clickedBtn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));

    if (clickedBtn) {
        clickedBtn.classList.add('active');
    } else {
        const idx = tab === 'login' ? 0 : 1;
        document.querySelectorAll('.tab-btn')[idx].classList.add('active');
    }

    document.getElementById('form-' + tab).classList.add('active');
    document.querySelectorAll('.auth-message').forEach(el => el.remove());
}

function showMessage(form, text, isError = false) {
    form.querySelectorAll('.auth-message').forEach(el => el.remove());
    const msg = document.createElement('p');
    msg.className = 'auth-message';
    msg.textContent = text;
    msg.style.cssText = `margin-top:10px;text-align:center;font-size:13px;font-family:'Inter',sans-serif;color:${isError ? '#ff5555' : '#55ff99'};`;
    form.appendChild(msg);
}

function setLoading(btn, loading) {
    btn.disabled = loading;
    btn.textContent = loading ? 'Ładowanie…' : btn.dataset.originalText;
}

// ============================================================
//  DOMContentLoaded
// ============================================================
document.addEventListener('DOMContentLoaded', () => {

    // sprawdź sesję
    fetch('session_check.php')
        .then(r => r.json())
        .then(d => { if (d.logged_in) updateNavAfterLogin(d.user.imie); })
        .catch(() => {});

    // lazy loading
    const lazyImgs = document.querySelectorAll('img[data-src]');
    if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    const img = e.target;
                    img.src = img.dataset.src;
                    img.addEventListener('load', () => img.classList.add('loaded'));
                    obs.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });
        lazyImgs.forEach(img => obs.observe(img));
    } else {
        lazyImgs.forEach(img => { img.src = img.dataset.src; img.classList.add('loaded'); });
    }

    // ── KARUZELA ──
    slides = document.querySelectorAll('.car');
    if (slides.length) {
        buildDots();
        showSlide(0);


    }

    // zapisz oryginalne teksty przycisków
    document.querySelectorAll('.submit-btn').forEach(btn => {
        btn.dataset.originalText = btn.textContent;
    });

    // ── LOGOWANIE ──
    const formLogin = document.getElementById('form-login');
    if (formLogin) {
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = formLogin.querySelector('input[type="email"]').value.trim();
            const haslo = formLogin.querySelector('input[type="password"]').value.trim();
            const btn   = formLogin.querySelector('.submit-btn');
            setLoading(btn, true);
            try {
                const res  = await fetch('login.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({email,haslo}) });
                const data = await res.json();
                if (data.success) {
                    showMessage(formLogin, '✓ ' + data.message, false);
                    setTimeout(() => { document.getElementById('log').checked = false; updateNavAfterLogin(data.user.imie); }, 1500);
                } else {
                    showMessage(formLogin, '✗ ' + data.message, true);
                }
            } catch { showMessage(formLogin, '✗ Błąd sieci – sprawdź serwer.', true); }
            finally  { setLoading(btn, false); }
        });
    }

    // ── REJESTRACJA ──
    const formRegister = document.getElementById('form-register');
    if (formRegister) {
        formRegister.addEventListener('submit', async (e) => {
            e.preventDefault();
            const inputs = formRegister.querySelectorAll('input');
            const imie   = inputs[0].value.trim();
            const email  = inputs[1].value.trim();
            const haslo  = inputs[2].value.trim();
            const haslo2 = inputs[3].value.trim();
            const btn    = formRegister.querySelector('.submit-btn');
            if (haslo !== haslo2) { showMessage(formRegister, '✗ Hasła nie są identyczne.', true); return; }
            setLoading(btn, true);
            try {
                const res  = await fetch('register.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({imie,email,haslo}) });
                const data = await res.json();
                if (data.success) {
                    showMessage(formRegister, '✓ ' + data.message + ' Możesz się teraz zalogować.', false);
                    setTimeout(() => { switchTab('login', null); document.querySelector('#form-login input[type="email"]').value = email; }, 2000);
                } else {
                    showMessage(formRegister, '✗ ' + data.message, true);
                }
            } catch { showMessage(formRegister, '✗ Błąd sieci – sprawdź serwer.', true); }
            finally  { setLoading(btn, false); }
        });
    }
});

function updateNavAfterLogin(imie) {
    const btn = document.querySelector('.log-btn');
    if (btn) btn.style.display = 'none';
    document.querySelectorAll('.nav-user-info').forEach(el => el.remove());
    const nav = document.querySelector('nav');
    const wrapper = document.createElement('div');
    wrapper.className = 'nav-user-info';
    const nameSpan = document.createElement('span');
    nameSpan.className = 'nav-username';
    nameSpan.textContent = imie;
    const logoutBtn = document.createElement('button');
    logoutBtn.className = 'nav-logout-btn';
    logoutBtn.textContent = 'Wyloguj';
    logoutBtn.addEventListener('click', handleLogout);
    wrapper.appendChild(nameSpan);
    wrapper.appendChild(logoutBtn);
    nav.appendChild(wrapper);
}

function handleLogout() {
    fetch('logout.php', { method: 'POST' }).catch(() => {});
    document.querySelectorAll('.nav-user-info').forEach(el => el.remove());
    const btn = document.querySelector('.log-btn');
    if (btn) btn.style.display = 'block';
}
// ============================================================
//  SIATKA – ładowanie eventów z bazy
// ============================================================
async function loadGrid() {
    const grid = document.querySelector('.grid');
    if (!grid) return;

    try {
        const res  = await fetch('pobierz_eventy.php');
        const data = await res.json();
        if (!data.success || !data.eventy.length) return;

        grid.innerHTML = '';

        data.eventy.forEach(ev => {
            const div = document.createElement('div');
            div.id = 'miejsce';
            div.style.cursor = 'pointer';
            div.onclick = () => window.location.href = 'koncert.html?id=' + ev.id;

            div.innerHTML = `
                <img data-src="${ev.plakat_url}" class="zdj" src="data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=">
                <div class="overlay"></div>
                <div class="label">
                    <span class="gatunek">${ev.gatunek}</span>
                    <span class="cena">${Math.round(ev.cena_stojace)}zł</span>
                </div>
            `;
            grid.appendChild(div);
        });

        // lazy load nowych obrazków
        const lazyImgs = grid.querySelectorAll('img[data-src]');
        if ('IntersectionObserver' in window) {
            const obs = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        const img = e.target;
                        img.src = img.dataset.src;
                        img.addEventListener('load', () => img.classList.add('loaded'));
                        obs.unobserve(img);
                    }
                });
            }, { rootMargin: '100px' });
            lazyImgs.forEach(img => obs.observe(img));
        } else {
            lazyImgs.forEach(img => { img.src = img.dataset.src; img.classList.add('loaded'); });
        }

    } catch(e) {
        console.error('Błąd ładowania siatki:', e);
    }
}

document.addEventListener('DOMContentLoaded', loadGrid);