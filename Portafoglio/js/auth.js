/**
 * auth.js
 * Gestisce lo stato di autenticazione: area utente nell'header,
 * submit di login/registrazione, e protezione della pagina "nuovo avvistamento".
 *
 * Lo stato di login è determinato
 * dal cookie di sessione PHP: chiamiamo /api/auth/session.php per sapere
 * se l'utente è autenticato (nessun dato salvato lato client).
 */

async function getCurrentUser() {
  try {
    return await apiRequest('/auth/session.php');
  } catch (e) {
    return null;
  }
}

async function renderUserArea() {
  const el = document.getElementById('userArea');
  if (!el) return;

  const user = await getCurrentUser();

  if (user) {
    el.innerHTML = `
      <span class="user-chip">@${pulisciTesto(user.username)}</span>
      <span>|</span>
      <a class="logout-Link "href="#" id="logoutLink">Log Out</a>
    `;
    document.getElementById('logoutLink').addEventListener('click', async (e) => {
      e.preventDefault();
      try {
        await apiRequest('/auth/logout.php', { method: 'POST' });
        window.location.href = 'index.html';
      } catch (err) {
        alert(err.message);
      }
    });
  } else {
    el.innerHTML = `
      <a class="login-btn" href="login.html">Accedi</a>
      <span>|</span>
      <a class="register-btn" href="register.html" >Registrati</a>
    `;
  }
}

/** Da chiamare nelle pagine che richiedono login (es. nuovo.html). */
async function requireAuth() {
  const user = await getCurrentUser();
  if (!user) {
    window.location.href = 'login.html';
    return false;
  }
  return true;
}

function showFormMessage(el, message, type = 'error') {
  el.textContent = message;
  el.className = `form-msg ${type}`;
}

function initLoginForm() {
  const form = document.getElementById('loginForm');
  if (!form) return;
  const msg = document.getElementById('loginMsg');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    const body = {
      email: form.email.value.trim(),
      password: form.password.value
    };

    try {
      await apiRequest('/auth/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      window.location.href = 'index.html';
    } catch (err) {
      showFormMessage(msg, err.message, 'error');
    } finally {
      submitBtn.disabled = false;
    }
  });
}

function initRegisterForm() {
  const form = document.getElementById('registerForm');
  if (!form) return;
  const msg = document.getElementById('registerMsg');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (form.password.value !== form.passwordConfirm.value) {
      showFormMessage(msg, 'Le due password non coincidono.', 'error');
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    const body = {
      username: form.username.value.trim(),
      email: form.email.value.trim(),
      password: form.password.value
    };

    try {
      await apiRequest('/auth/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      window.location.href = 'login.html';
    } catch (err) {
      showFormMessage(msg, err.message, 'error');
    } finally {
      submitBtn.disabled = false;
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  renderUserArea();
  initLoginForm();
  initRegisterForm();
});
