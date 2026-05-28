// ============================================================
//  script.js – Karuzela + Auth Tabs + Połączenie z PHP API
// ============================================================

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

    // wyczyść komunikaty przy przełączaniu zakładek
    document.querySelectorAll('.auth-message').forEach(el => el.remove());
}

// ── HELPER: pokaż komunikat pod formularzem ──
function showMessage(form, text, isError = false) {
    form.querySelectorAll('.auth-message').forEach(el => el.remove());

    const msg = document.createElement('p');
    msg.className = 'auth-message';
    msg.textContent = text;
    msg.style.cssText = `
        margin-top: 10px;
        text-align: center;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        color: ${isError ? '#ff5555' : '#55ff99'};
    `;
    form.appendChild(msg);
}

// ── HELPER: zablokuj/odblokuj przycisk submit ──
function setLoading(btn, loading) {
    btn.disabled = loading;
    btn.textContent = loading ? 'Ładowanie…' : btn.dataset.originalText;
}

// ============================================================
//  DOMContentLoaded
// ============================================================
document.addEventListener('DOMContentLoaded', () => {

    // karuzela
    slides = document.querySelectorAll('.car');
    if (slides.length) {
        buildDots();
        showSlide(0);
    }

    // zapisz oryginalne teksty przycisków
    document.querySelectorAll('.submit-btn').forEach(btn => {
        btn.dataset.originalText = btn.textContent;
    });

    // ── FORMULARZ LOGOWANIA ──
    const formLogin = document.getElementById('form-login');
    formLogin.addEventListener('submit', async (e) => {
        e.preventDefault();

        const email = formLogin.querySelector('input[type="email"]').value.trim();
        const haslo = formLogin.querySelector('input[type="password"]').value.trim();
        const btn   = formLogin.querySelector('.submit-btn');

        setLoading(btn, true);

        try {
            const res  = await fetch('login.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ email, haslo }),
            });

            const data = await res.json();

            if (data.success) {
                showMessage(formLogin, `✓ ${data.message}`, false);

                // zamknij modal po 1.5 s i zaktualizuj nav
                setTimeout(() => {
                    document.getElementById('log').checked = false;
                    updateNavAfterLogin(data.user.imie);
                }, 1500);
            } else {
                showMessage(formLogin, `✗ ${data.message}`, true);
            }

        } catch (err) {
            showMessage(formLogin, '✗ Błąd sieci – sprawdź serwer.', true);
        } finally {
            setLoading(btn, false);
        }
    });

    // ── FORMULARZ REJESTRACJI ──
    const formRegister = document.getElementById('form-register');
    formRegister.addEventListener('submit', async (e) => {
        e.preventDefault();

        const inputs = formRegister.querySelectorAll('input');
        const imie   = inputs[0].value.trim();
        const email  = inputs[1].value.trim();
        const haslo  = inputs[2].value.trim();
        const haslo2 = inputs[3].value.trim();
        const btn    = formRegister.querySelector('.submit-btn');

        if (haslo !== haslo2) {
            showMessage(formRegister, '✗ Hasła nie są identyczne.', true);
            return;
        }

        setLoading(btn, true);

        try {
            const res  = await fetch('register.php', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ imie, email, haslo }),
            });

            const data = await res.json();

            if (data.success) {
                showMessage(formRegister, `✓ ${data.message} Możesz się teraz zalogować.`, false);

                // przełącz na zakładkę logowania po 2 s
                setTimeout(() => {
                    switchTab('login', null);
                    document.querySelector('#form-login input[type="email"]').value = email;
                }, 2000);
            } else {
                showMessage(formRegister, `✗ ${data.message}`, true);
            }

        } catch (err) {
            showMessage(formRegister, '✗ Błąd sieci – sprawdź serwer.', true);
        } finally {
            setLoading(btn, false);
        }
    });
});

// ── zmień przycisk "Logowanie" na imię użytkownika ──
function updateNavAfterLogin(imie) {
    const btn = document.querySelector('.log-btn');
    if (btn) {
        btn.textContent = `Cześć, ${imie}!`;
        btn.style.display = 'block';   // pokaż z powrotem
    }
}