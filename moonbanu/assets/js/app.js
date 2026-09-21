/*!
ماه‌بانو | app.js 3.1.4+hotfix
روتر hash، رندر سمت سرور، اکشن‌های data-mb.
هیچ منطق اشتراک یا فلگ Pro در این فایل وجود ندارد؛ همه تصمیم‌ها روی سرور است.
*/
(function () {
'use strict';
var app, view, toastEl, cfg = { rest: '', nonce: '', authNonce: '', logged: false };
var busy = false;
var logState = null;
var history_ = [];
var quizState = {};
var printLoaded = false;
var otpTimer = null;

/* ------------------------------------------------------------------ */
/* ابزارها                                                            */
/* ------------------------------------------------------------------ */
function q(sel, root) { return (root || document).querySelector(sel); }
function qa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
function faNum(value) {
var fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
return String(value).replace(/[0-9]/g, function (d) { return fa[+d]; });
}
function enNum(value) {
return String(value)
.replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
.replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); });
}
function toast(message, bad) {
if (!toastEl) { return; }
toastEl.textContent = message;
toastEl.className = 'mb-toast on' + (bad ? ' bad' : '');
clearTimeout(toastEl._t);
toastEl._t = setTimeout(function () { toastEl.className = 'mb-toast'; }, 2800);
}
function api(path, options) {
var opt = options || {};
var url = cfg.rest + path;
var init = {
method: opt.method || 'GET',
headers: {
'X-WP-Nonce': cfg.nonce,
'X-MB-Nonce': cfg.authNonce,
'Accept': 'application/json'
},
credentials: 'same-origin'
};
if (opt.body) {
init.headers['Content-Type'] = 'application/json';
init.body = JSON.stringify(opt.body);
}
return fetch(url, init).then(function (res) {
return res.json().then(function (data) {
if (!res.ok) {
var msg = (data && data.message) ? data.message : 'خطای غیرمنتظره';
if (res.status === 401 || res.status === 403) {
if (data && (data.code === 'rest_cookie_invalid_nonce' || data.code === 'rest_forbidden')) {
if (cfg.logged) { sessionLost(); }
}
}
var error = new Error(msg);
error.status = res.status;
throw error;
}
return data;
}).catch(function (err) {
if (err instanceof SyntaxError) {
throw new Error(res.status >= 500 ? 'خطای سرور؛ کمی بعد دوباره تلاش کن' : 'پاسخ سرور خوانده نشد');
}
throw err;
});
}).catch(function (err) {
if (err && err.name === 'TypeError') {
throw new Error(navigator.onLine ? 'ارتباط با سرور برقرار نشد' : 'اینترنت قطع است');
}
throw err;
});
}
function sessionLost() {
cfg.logged = false;
if (app) { app.setAttribute('data-logged', '0'); }
notifyNativeBridgeLogout();
toast('برای ادامه دوباره وارد شو', true);
go('login', true);
}
function adoptSession(data) {
if (!data) { return; }
if (data.nonce) { cfg.nonce = data.nonce; if (app) { app.setAttribute('data-nonce', data.nonce); } }
if (data.auth_nonce) { cfg.authNonce = data.auth_nonce; }
if (typeof data.onboarded !== 'undefined' && app) {
app.setAttribute('data-onboarded', data.onboarded ? '1' : '0');
}
}
/* N14: Native Bridge Calls (Web → Mobile) */
function callNativeBridge(method) {
var args = Array.prototype.slice.call(arguments, 1);
if (typeof window.MoonBanuBridge !== 'undefined' && typeof window.MoonBanuBridge[method] === 'function') {
try {
window.MoonBanuBridge[method].apply(window.MoonBanuBridge, args);
} catch (e) {
console.warn('Bridge call failed:', method, e);
}
}
}
function notifyNativeBridgeAuth(data) {
var token = data.access_token || data.token || '';
var refresh = data.refresh_token || '';
var mode = data.role || 'woman';
if (token) {
callNativeBridge('onAuth', token, refresh, mode);
}
}
function notifyNativeBridgeLogout() {
callNativeBridge('onLogout');
}

/* ------------------------------------------------------------------ */
/* روتر                                                               */
/* ------------------------------------------------------------------ */
var GUEST_ROUTES = /^(login|register|forgot|reset|otp|about)$/;
var ALWAYS_OPEN = /^(onboarding(?:\/.*)?|setup|support(?:\/.*)?|about|account)$/;
function currentRoute() {
var hash = (location.hash || '').replace(/^#\/?/, '').trim();
var route = hash || app.getAttribute('data-boot') || 'home';
if (!cfg.logged) {
return GUEST_ROUTES.test(route) ? route : 'login';
}
if (GUEST_ROUTES.test(route) && route !== 'about') { return 'home'; }
if (app.getAttribute('data-onboarded') !== '1' && !ALWAYS_OPEN.test(route)) {
return 'onboarding';
}
return route;
}
function go(route, replace) {
var target = '#/' + String(route).replace(/^#?\/?/, '');
if (location.hash === target) { render(route); return; }
if (replace) { location.replace(target); } else { location.hash = target; }
}
function render(route) {
if (busy) { return; }
busy = true;
view.classList.add('loading');
api('/view?route=' + encodeURIComponent(route)).then(function (data) {
if (typeof data.logged !== 'undefined') {
cfg.logged = !!data.logged;
app.setAttribute('data-logged', data.logged ? '1' : '0');
}
view.innerHTML = data.html;
afterRender();
window.scrollTo({ top: 0, behavior: 'auto' });
}).catch(function (err) {
toast(err.message || 'بارگذاری انجام نشد', true);
}).then(function () {
busy = false;
view.classList.remove('loading');
});
}
function onHashChange() {
var route = currentRoute();
if (history_[history_.length - 1] !== route) { history_.push(route); }
if (history_.length > 30) { history_.shift(); }
render(route);
}
function goBack() {
history_.pop();
var prev = history_[history_.length - 1];
if (prev) { go(prev, true); } else if (window.history.length > 1) { window.history.back(); } else { go('home', true); }
}

/* ------------------------------------------------------------------ */
/* پس از هر رندر                                                      */
/* ------------------------------------------------------------------ */
function afterRender() {
logState = null;
if (otpTimer) { clearInterval(otpTimer); otpTimer = null; }
initLogState();
initSliders();
initQr();
initAuthExtras();
initThread();
initQuizState();
initReminderInputs();
initFaqSearch();
if (/^report/.test(currentRoute())) { ensurePrintCss(); }
}
function initReminderInputs() {
qa('.rem').forEach(function (card) {
qa('select', card).forEach(function (sel) {
sel.addEventListener('change', function () { saveReminder(card); });
});
});
}
function initAuthExtras() {
var form = q('#mb-auth-form');
if (!form) { return; }
if (form.getAttribute('data-nonce')) { cfg.authNonce = form.getAttribute('data-nonce'); }
var pass = q('#mb-reg-pass');
var meter = q('#mb-pass-meter');
if (pass && meter) {
pass.addEventListener('input', function () { paintMeter(pass.value, meter); });
}
form.addEventListener('keydown', function (event) {
if (event.key !== 'Enter') { return; }
var tag = (event.target.tagName || '').toLowerCase();
if ('textarea' === tag) { return; }
event.preventDefault();
var panel = q('.auth-panel.on', form);
if (!panel) { return; }
var submit;
if ('otp' === panel.getAttribute('data-panel')) {
var step2 = panel.querySelector('#mb-otp-step2');
submit = (step2 && !step2.hidden)
? panel.querySelector('[data-mb="otp-verify"]')
: panel.querySelector('[data-mb="otp-request"]');
} else {
submit = panel.querySelector('[data-mb="auth-login"],[data-mb="auth-register"],[data-mb="auth-forgot"],[data-mb="auth-reset"]');
}
if (submit && !submit.hasAttribute('disabled')) { submit.click(); }
}, false);
var otpCode = q('#mb-otp-code');
if (otpCode) {
otpCode.addEventListener('input', function () {
otpCode.value = enNum(otpCode.value).replace(/[^0-9]/g, '').slice(0, 6);
if (otpCode.value.length === 6) {
var go_ = q('[data-mb="otp-verify"]');
if (go_ && !go_.hasAttribute('disabled')) { go_.click(); }
}
}, false);
}
var first = q('.auth-panel.on input:not([type=hidden]):not(.mb-hp)', form);
if (first && window.matchMedia && !window.matchMedia('(max-width: 520px)').matches) {
try { first.focus({ preventScroll: true }); } catch (e) { first.focus(); }
}
}
function paintMeter(value, meter) {
var score = 0;
if (value.length >= 8) { score++; }
if (value.length >= 12) { score++; }
if (/[0-9]/.test(value) && /[^0-9]/.test(value)) { score++; }
if (/[^A-Za-z0-9\u0600-\u06FF]/.test(value)) { score++; }
meter.setAttribute('data-score', String(score));
}
function initFaqSearch() {
var input = q('#faq-search');
if (!input || input.getAttribute('data-bound') === '1') { return; }
input.setAttribute('data-bound', '1');
input.addEventListener('input', function () { filterFaq(input.value); }, false);
}
function filterFaq(value) {
var query = String(value || '').trim().toLowerCase();
qa('.accordion-item').forEach(function (item) {
var question = (q('.accordion-trigger', item) || {}).textContent || '';
var answer = (q('.accordion-body', item) || {}).textContent || '';
var match = !query
|| question.toLowerCase().indexOf(query) >= 0
|| answer.toLowerCase().indexOf(query) >= 0;
item.style.display = match ? '' : 'none';
});
qa('.accordion-group').forEach(function (group) {
var any = qa('.accordion-item', group).some(function (item) { return item.style.display !== 'none'; });
group.style.display = any ? '' : 'none';
});
}
function startOtpCountdown(btn, seconds) {
if (!btn) { return; }
var label = btn.querySelector('span');
var original = btn.getAttribute('data-label') || (label ? label.textContent : 'ارسال دوباره');
btn.setAttribute('data-label', original);
var left = Math.max(1, parseInt(seconds, 10) || 90);
if (otpTimer) { clearInterval(otpTimer); }
var paint = function () {
if (label) { label.textContent = 'ارسال دوباره تا ' + faNum(left) + ' ثانیه'; }
};
btn.setAttribute('disabled', 'disabled');
paint();
otpTimer = setInterval(function () {
left--;
if (left <= 0) {
clearInterval(otpTimer);
otpTimer = null;
btn.removeAttribute('disabled');
if (label) { label.textContent = original; }
return;
}
paint();
}, 1000);
}
function initThread() {
var thread = q('#mb-ticket-thread');
if (!thread) { return; }
var last = thread.lastElementChild;
if (last && last.scrollIntoView) { last.scrollIntoView({ block: 'nearest' }); }
}
function authMsg(text, bad) {
var box = q('#mb-auth-msg');
if (!box) { toast(text, bad); return; }
box.textContent = text;
box.className = 'auth-msg on' + (bad ? ' bad' : ' good');
}
function switchPanel(mode) {
var form = q('#mb-auth-form');
if (!form) { go(mode); return; }
var found = false;
qa('.auth-panel', form).forEach(function (panel) {
var on = panel.getAttribute('data-panel') === mode;
panel.classList.toggle('on', on);
if (on) { found = true; }
});
if (!found) { go(mode); return; }
qa('.auth-tab').forEach(function (tab) {
var on = tab.getAttribute('data-mode') === mode;
tab.classList.toggle('on', on);
tab.setAttribute('aria-selected', on ? 'true' : 'false');
});
var box = q('#mb-auth-msg');
if (box) { box.className = 'auth-msg'; box.textContent = ''; }
if (location.hash !== '#/' + mode) { history.replaceState(null, '', '#/' + mode); }
var first = q('.auth-panel.on input:not([type=hidden]):not(.mb-hp)', form);
if (first) { try { first.focus({ preventScroll: true }); } catch (e) {} }
}
/* پس از ورود موفق، اپ بدون بارگذاری مجدد صفحه وارد حالت کاربر می‌شود. */
function enterApp(data) {
adoptSession(data);
cfg.logged = true;
if (app) { app.setAttribute('data-logged', '1'); }
notifyNativeBridgeAuth(data); /* N14 */
toast(data.message || 'خوش آمدی');
go(data.redirect || 'home', true);
}
function initLogState() {
var form = q('#mb-log-form');
if (!form) { return; }
var moodOn = q('.emoj.on');
var painOn = q('.pain.on');
logState = {
date: form.getAttribute('data-date'),
mood: moodOn ? parseInt(moodOn.getAttribute('data-value'), 10) : null,
bleeding: (q('.chip.pick.on') || {}).getAttribute ? q('.chip.pick.on').getAttribute('data-value') : 'none',
pain: painOn ? parseInt(painOn.getAttribute('data-value'), 10) : 0,
symptoms: qa('.sym.on').map(function (el) { return el.getAttribute('data-value'); })
};
}
function initSliders() {
bindSlider('#mb-cycle-len', '#mb-cycle-out');
bindSlider('#mb-period-len', '#mb-period-out');
}
function bindSlider(input, output) {
var el = q(input), out = q(output);
if (!el || !out) { return; }
el.addEventListener('input', function () { out.textContent = faNum(el.value); });
}
function initQr() {
var box = q('#mb-qr');
if (!box) { return; }
var link = box.getAttribute('data-link');
if (!link) { box.innerHTML = ''; return; }
if (window.MBQR) { window.MBQR.render(box, link, { dark: '#07040C', light: '#ffffff', quiet: 2 }); }
}

/* ------------------------------------------------------------------ */
/* کمکی‌های نسخهٔ ۳٫۰                                                  */
/* ------------------------------------------------------------------ */
function fieldNum(sel) {
var el = q(sel);
if (!el) { return null; }
var raw = enNum(String(el.value || '').trim()).replace('٫', '.').replace('،', '.');
if (raw === '') { return null; }
var num = parseFloat(raw);
return isNaN(num) ? null : num;
}
function fieldVal(sel, fallback) {
var el = q(sel);
return el ? el.value : fallback;
}
function medsPayload() {
var chips = qa('#mb-meds .chip.pick');
if (!chips.length) { return []; }
return chips.map(function (chip) {
return { name: chip.getAttribute('data-value'), taken: chip.classList.contains('on') ? 1 : 0 };
});
}
function ensurePrintCss() {
if (printLoaded) { return; }
var url = window.MB_BOOT && MB_BOOT.printCss;
if (!url) { return; }
var link = document.createElement('link');
link.rel = 'stylesheet';
link.media = 'print';
link.href = url;
document.head.appendChild(link);
printLoaded = true;
}
function bubble(text, who, extra) {
var log = q('#mb-chat-log');
if (!log) { return; }
var div = document.createElement('div');
div.className = 'bubble ' + (who || 'bot');
div.innerHTML = '<p>' + escapeHtml(text) + '</p>' + (extra || '');
log.appendChild(div);
log.scrollTop = log.scrollHeight;
}
function initQuizState() {
quizState = {};
var form = q('#mb-quiz-form');
if (!form) { return; }
quizState.slug = form.getAttribute('data-slug');
quizState.answers = {};
quizState.total = qa('.qz', form).length;
}

var actions = {
goto: function (el) {
var route = el.getAttribute('data-route');
if (route) { go(route); }
},
back: function () { goBack(); },
'auth-mode': function (el) {
switchPanel(el.getAttribute('data-mode') || 'login');
},
'toggle-pass': function (el) {
var input = q('#' + el.getAttribute('data-target'));
if (!input) { return; }
var show = input.type === 'password';
input.type = show ? 'text' : 'password';
el.classList.toggle('on', show);
el.setAttribute('aria-label', show ? 'پنهان کردن رمز' : 'نمایش رمز');
},
'auth-login': function (el) {
var ident = q('#mb-login-identity');
var pass = q('#mb-login-pass');
var remember = q('#mb-remember');
if (!ident || !ident.value.trim()) { authMsg('ایمیل یا موبایلت را وارد کن', true); return; }
if (!pass || !pass.value) { authMsg('رمز عبور را وارد کن', true); return; }
lock(el, true);
api('/auth/login', {
method: 'POST',
body: {
identity: enNum(ident.value.trim()),
password: pass.value,
remember: remember ? !!remember.checked : true,
mb_nonce: cfg.authNonce
}
}).then(function (data) {
authMsg(data.message || 'خوش آمدی', false);
enterApp(data);
}).catch(function (err) { authMsg(err.message, true); })
.then(function () { lock(el, false); });
},
'auth-register': function (el) {
var name = q('#mb-reg-name');
var email = q('#mb-reg-email');
var pass = q('#mb-reg-pass');
var mobile = q('#mb-reg-mobile');
var terms = q('#mb-terms');
var hp = q('#mb-hp');
if (!name || name.value.trim().length < 2) { authMsg('نامت را وارد کن', true); return; }
if (!email || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email.value.trim())) { authMsg('ایمیل معتبر وارد کن', true); return; }
if (!pass || pass.value.length < 8) { authMsg('رمز عبور باید دست‌کم ۸ نویسه باشد', true); return; }
if (!/[0-9]/.test(pass.value) || !/[^0-9]/.test(pass.value)) { authMsg('رمز باید هم حرف و هم عدد داشته باشد', true); return; }
if (!terms || !terms.checked) { authMsg('پذیرش قوانین و حریم خصوصی لازم است', true); return; }
lock(el, true);
api('/auth/register', {
method: 'POST',
body: {
name: name.value.trim(),
email: email.value.trim(),
password: pass.value,
mobile: mobile ? enNum(mobile.value.trim()) : '',
terms: true,
website: hp ? hp.value : '',
mb_nonce: cfg.authNonce
}
}).then(function (data) {
authMsg(data.message || 'حسابت ساخته شد', false);
enterApp(data);
}).catch(function (err) { authMsg(err.message, true); })
.then(function () { lock(el, false); });
},
'auth-forgot': function (el) {
var ident = q('#mb-forgot-identity');
if (!ident || !ident.value.trim()) { authMsg('ایمیل یا موبایلت را وارد کن', true); return; }
lock(el, true);
api('/auth/forgot', { method: 'POST', body: { identity: enNum(ident.value.trim()), mb_nonce: cfg.authNonce } })
.then(function (data) {
authMsg(data.message || 'کد فرستاده شد', false);
var target = q('#mb-reset-identity');
if (target) { target.value = ident.value.trim(); }
switchPanel('reset');
}).catch(function (err) { authMsg(err.message, true); })
.then(function () { lock(el, false); });
},
'auth-reset': function (el) {
var ident = q('#mb-reset-identity');
var code = q('#mb-reset-code');
var pass = q('#mb-reset-pass');
if (!ident || !ident.value.trim()) { authMsg('ایمیل یا موبایلت را وارد کن', true); return; }
if (!code || enNum(code.value).replace(/[^0-9]/g, '').length !== 6) { authMsg('کد شش‌رقمی را کامل وارد کن', true); return; }
if (!pass || pass.value.length < 8) { authMsg('رمز تازه باید دست‌کم ۸ نویسه باشد', true); return; }
lock(el, true);
api('/auth/reset', {
method: 'POST',
body: {
identity: enNum(ident.value.trim()),
code: enNum(code.value).replace(/[^0-9]/g, ''),
password: pass.value,
mb_nonce: cfg.authNonce
}
}).then(function (data) {
authMsg(data.message || 'رمز تازه ثبت شد', false);
enterApp(data);
}).catch(function (err) { authMsg(err.message, true); })
.then(function () { lock(el, false); });
},
'otp-request': function (el) {
var ident = q('#mb-otp-identity');
if (!ident || !ident.value.trim()) { authMsg('موبایل یا ایمیلت را وارد کن', true); return; }
lock(el, true);
api('/auth/otp/request', {
method: 'POST',
body: { identity: enNum(ident.value.trim()), mb_nonce: cfg.authNonce }
}).then(function (data) {
authMsg(data.message || 'کد ورود فرستاده شد', false);
var step = q('#mb-otp-step2');
if (step) { step.hidden = false; }
var code = q('#mb-otp-code');
if (code) { code.value = ''; try { code.focus({ preventScroll: true }); } catch (e) { code.focus(); } }
startOtpCountdown(el, 90);
}).catch(function (err) {
authMsg(err.message, true);
lock(el, false);
});
},
'otp-verify': function (el) {
var ident = q('#mb-otp-identity');
var code = q('#mb-otp-code');
if (!ident || !ident.value.trim()) { authMsg('موبایل یا ایمیلت را وارد کن', true); return; }
var digits = code ? enNum(code.value).replace(/[^0-9]/g, '') : '';
if (digits.length !== 6) { authMsg('کد شش‌رقمی را کامل وارد کن', true); return; }
lock(el, true);
api('/auth/otp/verify', {
method: 'POST',
body: { identity: enNum(ident.value.trim()), code: digits, mb_nonce: cfg.authNonce }
}).then(function (data) {
if (otpTimer) { clearInterval(otpTimer); otpTimer = null; }
authMsg(data.message || 'خوش آمدی', false);
enterApp(data);
}).catch(function (err) {
authMsg(err.message, true);
if (code) { code.value = ''; try { code.focus({ preventScroll: true }); } catch (e) {} }
}).then(function () { lock(el, false); });
},
'auth-logout': function (el) {
if (!window.confirm('از حساب خارج می‌شوی؟')) { return; }
lock(el, true);
api('/auth/logout', { method: 'POST', body: {} })
.then(function (data) {
cfg.logged = false;
if (data.auth_nonce) { cfg.authNonce = data.auth_nonce; }
if (app) { app.setAttribute('data-logged', '0'); }
toast(data.message || 'خارج شدی');
window.location.href = (window.MB_BOOT && MB_BOOT.appUrl ? MB_BOOT.appUrl : '') + '#/login';
window.location.reload();
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'account-save': function (el) {
var name = q('#mb-acc-name');
var mobile = q('#mb-acc-mobile');
lock(el, true);
api('/auth/account', {
method: 'POST',
body: {
name: name ? name.value.trim() : '',
mobile: mobile ? enNum(mobile.value.trim()) : ''
}
}).then(function (data) {
adoptSession(data);
toast(data.message || 'ذخیره شد');
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'account-pass': function (el) {
var cur = q('#mb-acc-cur');
var nue = q('#mb-acc-new');
if (!cur || !cur.value) { toast('رمز فعلی را وارد کن', true); return; }
if (!nue || nue.value.length < 8) { toast('رمز تازه باید دست‌کم ۸ نویسه باشد', true); return; }
lock(el, true);
api('/auth/account', { method: 'POST', body: { current_password: cur.value, new_password: nue.value } })
.then(function (data) {
adoptSession(data);
cur.value = '';
nue.value = '';
toast('رمز عبور عوض شد');
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'ticket-create': function (el) {
var subject = q('#mb-ticket-subject');
var body = q('#mb-ticket-body');
var topic = q('#mb-ticket-topic');
var prio = q('#mb-ticket-priority');
var contact = q('#mb-ticket-contact');
if (!subject || subject.value.trim().length < 3) { toast('عنوان درخواست را بنویس', true); return; }
if (!body || body.value.trim().length < 15) { toast('توضیح را کامل‌تر بنویس (دست‌کم ۱۵ نویسه)', true); return; }
lock(el, true);
api('/tickets', {
method: 'POST',
body: {
subject: subject.value.trim(),
body: body.value.trim(),
topic: topic ? topic.value : 'general',
priority: prio ? prio.value : 'normal',
contact: contact ? contact.value.trim() : ''
}
}).then(function (data) {
toast(data.message || 'ثبت شد');
go('support/' + data.id);
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'ticket-reply': function (el) {
var body = q('#mb-ticket-reply');
var id = parseInt(el.getAttribute('data-id'), 10);
if (!body || body.value.trim().length < 2) { toast('پیامت خالی است', true); return; }
lock(el, true);
api('/tickets/reply', { method: 'POST', body: { id: id, body: body.value.trim() } })
.then(function (data) {
toast(data.message || 'ثبت شد');
body.value = '';
render('support/' + id);
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'ticket-close': function (el) {
var id = parseInt(el.getAttribute('data-id'), 10);
if (!window.confirm('این درخواست بسته شود؟')) { return; }
lock(el, true);
api('/tickets/close', { method: 'POST', body: { id: id } })
.then(function (data) {
toast(data.message || 'بسته شد');
go('support');
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
mood: function (el) {
if (!logState) { initLogState(); }
var value = parseInt(el.getAttribute('data-value'), 10);
var isOn = el.classList.contains('on');
qa('.emoj').forEach(function (b) { b.classList.remove('on'); b.setAttribute('aria-pressed', 'false'); });
if (isOn) { logState.mood = null; } else {
el.classList.add('on');
el.setAttribute('aria-pressed', 'true');
logState.mood = value;
}
},
bleeding: function (el) {
if (!logState) { initLogState(); }
qa('.chip.pick').forEach(function (b) { b.classList.remove('on'); });
el.classList.add('on');
logState.bleeding = el.getAttribute('data-value');
},
symptom: function (el) {
if (!logState) { initLogState(); }
var value = el.getAttribute('data-value');
var idx = logState.symptoms.indexOf(value);
if (idx >= 0) {
logState.symptoms.splice(idx, 1);
el.classList.remove('on');
el.setAttribute('aria-pressed', 'false');
} else {
logState.symptoms.push(value);
el.classList.add('on');
el.setAttribute('aria-pressed', 'true');
}
},
pain: function (el) {
if (!logState) { initLogState(); }
var labels = { 1: 'خیلی کم', 2: 'کم', 3: 'متوسط', 4: 'زیاد', 5: 'خیلی زیاد' };
var value = parseInt(el.getAttribute('data-value'), 10);
var isOn = el.classList.contains('on');
qa('.pain').forEach(function (b) { b.classList.remove('on'); });
var label = q('#mb-pain-label');
if (isOn) {
logState.pain = 0;
if (label) { label.textContent = 'بدون درد'; }
} else {
el.classList.add('on');
logState.pain = value;
if (label) { label.textContent = labels[value] || ''; }
}
},
'save-log': function (el) {
if (!logState) { initLogState(); }
if (!logState) { return; }
var note = q('#mb-note');
lock(el, true);
api('/log', {
method: 'POST',
body: {
date: logState.date,
mood: logState.mood,
bleeding: logState.bleeding,
pain: logState.pain,
symptoms: logState.symptoms,
note: note ? note.value : '',
weight_kg: fieldNum('#mb-weight'),
sleep_h: fieldNum('#mb-sleep'),
water_cups: fieldNum('#mb-water'),
exercise_min: fieldNum('#mb-exercise'),
bbt: fieldNum('#mb-bbt'),
mucus: fieldVal('#mb-mucus', 'none'),
sex: fieldVal('#mb-sex', 'none'),
meds: medsPayload()
}
}).then(function (data) {
toast(data.message || 'ذخیره شد');
if (data.ovulation) { toast('صعود دما ثبت شد: ' + data.ovulation); }
setTimeout(function () { go('home'); }, 700);
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'preg-start': function (el) {
var jy = q('#mb-preg-jy'), jm = q('#mb-preg-jm'), jd = q('#mb-preg-jd');
if (!jy || !jm || !jd) { return; }
var y = enNum(jy.value).trim(), m = enNum(jm.value).trim(), d = enNum(jd.value).trim();
if (!y || !m || !d) { toast('تاریخ آخرین قاعدگی را کامل وارد کن', true); return; }
lock(el, true);
api('/pregnancy/start', { method: 'POST', body: { lmp: y + '/' + m + '/' + d } })
.then(function (data) {
toast(data.message || 'فعال شد');
if (data.due) { toast('موعد تقریبی: ' + data.due); }
render('pregnancy');
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'preg-stop': function (el) {
if (!window.confirm('حالت بارداری خاموش شود و پیش‌بینی چرخه برگردد؟')) { return; }
lock(el, true);
api('/pregnancy/stop', { method: 'POST', body: {} })
.then(function (data) { toast(data.message || 'خاموش شد'); render('pregnancy'); })
.catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'preg-share': function (el) {
var on = !el.classList.contains('on');
el.classList.toggle('on', on);
el.setAttribute('aria-checked', on ? 'true' : 'false');
api('/pregnancy/share', { method: 'POST', body: { on: on } })
.then(function (data) { toast(data.message || 'ذخیره شد'); })
.catch(function (err) {
el.classList.toggle('on', !on);
el.setAttribute('aria-checked', !on ? 'true' : 'false');
toast(err.message, true);
});
},
'preg-check': function (el) {
var done = !el.classList.contains('on');
el.classList.toggle('on', done);
el.setAttribute('aria-pressed', done ? 'true' : 'false');
api('/pregnancy/checklist', { method: 'POST', body: { key: el.getAttribute('data-key'), done: done } })
.catch(function (err) {
el.classList.toggle('on', !done);
toast(err.message, true);
});
},
'rem-toggle': function (el) {
var card = el.closest('.rem');
if (!card) { return; }
var on = !el.classList.contains('on');
el.classList.toggle('on', on);
el.setAttribute('aria-checked', on ? 'true' : 'false');
saveReminder(card);
},
'med-add': function (el) {
var name = q('#mb-med-name'), time = q('#mb-med-time');
if (!name || !time || !name.value.trim() || !time.value.trim()) {
toast('نام دارو و ساعت را وارد کن', true);
return;
}
lock(el, true);
api('/reminders/med', { method: 'POST', body: { name: name.value.trim(), time: enNum(time.value.trim()) } })
.then(function (data) { toast(data.message || 'اضافه شد'); render('reminders'); })
.catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'med-remove': function (el) {
lock(el, true);
api('/reminders/med/remove', { method: 'POST', body: { index: parseInt(el.getAttribute('data-index'), 10) } })
.then(function (data) { toast(data.message || 'حذف شد'); render('reminders'); })
.catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'med-toggle': function (el) {
el.classList.toggle('on');
},
'community-cat': function (el) {
var cat = el.getAttribute('data-value') || '';
go(cat === '' ? 'community' : 'community/' + cat);
},
'quiz-pick': function (el) {
var index = parseInt(el.getAttribute('data-q'), 10);
var value = parseInt(el.getAttribute('data-value'), 10);
var group = el.closest('.qz');
if (!group) { return; }
qa('.qz-opt', group).forEach(function (opt) { opt.classList.remove('on'); });
el.classList.add('on');
if (!quizState.answers) { initQuizState(); }
quizState.answers[index] = value;
},
'quiz-submit': function (el) {
if (!quizState.slug) { initQuizState(); }
var answered = Object.keys(quizState.answers || {}).length;
if (answered < quizState.total) {
toast('به همهٔ پرسش‌ها پاسخ بده (' + faNum(answered) + ' از ' + faNum(quizState.total) + ')', true);
return;
}
lock(el, true);
api('/quiz/submit', { method: 'POST', body: { slug: quizState.slug, answers: quizState.answers } })
.then(function (data) { paintQuizResult(data); })
.catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'assistant-chip': function (el) {
var box = q('#mb-chat-q');
if (!box) { return; }
box.value = el.getAttribute('data-value') || '';
box.focus();
},
'assistant-ask': function (el) {
var box = q('#mb-chat-q');
if (!box || box.value.trim().length < 2) { toast('پرسشت را بنویس', true); return; }
var question = box.value.trim();
bubble(question, 'me');
box.value = '';
lock(el, true);
bubble((MB_BOOT.i18n && MB_BOOT.i18n.thinking) || 'در حال بررسی…', 'bot pending');
api('/assistant', { method: 'POST', body: { q: question } })
.then(function (data) {
var pending = q('.bubble.pending');
if (pending) { pending.remove(); }
var extra = '';
if (data.article && data.article.title) {
extra += '<a class="chip gold" href="#/health/' + escapeHtml(data.article.cat || '') + '">' + escapeHtml(data.article.title) + '</a>';
}
if (data.fallback) {
extra += '<a class="chip dark" href="#/support">ثبت تیکت پشتیبانی</a>';
}
extra += '<span class="tiny muted block">' + escapeHtml(data.label || '') + '</span>';
bubble(data.answer, 'bot', extra);
}).catch(function (err) {
var pending = q('.bubble.pending');
if (pending) { pending.remove(); }
toast(err.message, true);
}).then(function () { lock(el, false); });
},
'report-range': function (el) {
go('report/' + (el.getAttribute('data-value') || '6'));
},
'report-print': function () {
ensurePrintCss();
toast((MB_BOOT.i18n && MB_BOOT.i18n.printing) || 'در حال آماده‌سازی چاپ…');
setTimeout(function () { window.print(); }, 260);
},
'copy-ref': function (el) {
var link = el.getAttribute('data-link') || '';
if (!link) { return; }
copyText(link);
},
'jpick-apply': function () {
var jy = q('#mb-jy'), jm = q('#mb-jm'), jd = q('#mb-jd'), target = q('#mb-last-period');
if (!jy || !jm || !jd || !target) { return; }
var y = enNum(jy.value), m = enNum(jm.value), d = enNum(jd.value);
target.value = faNum(y + '/' + (m.length < 2 ? '0' + m : m) + '/' + (d.length < 2 ? '0' + d : d));
toast('تاریخ گذاشته شد');
},
'save-profile': function (el) {
var last = q('#mb-last-period'), cycle = q('#mb-cycle-len'), period = q('#mb-period-len');
var mobile = q('input[name="mobile"]');
if (!last || !last.value.trim()) { toast('تاریخ آخرین قاعدگی را وارد کن', true); return; }
lock(el, true);
api('/profile', {
method: 'POST',
body: {
last_period_jalali: enNum(last.value),
cycle_len: cycle ? parseInt(cycle.value, 10) : 28,
period_len: period ? parseInt(period.value, 10) : 5,
mobile: mobile ? enNum(mobile.value) : ''
}
}).then(function (data) {
toast(data.message || 'ذخیره شد');
app.setAttribute('data-onboarded', '1');
setTimeout(function () { go('home'); }, 600);
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'invite-send': function (el) {
var channel = el.getAttribute('data-channel') || 'email';
var target = q('#mb-invite-target');
lock(el, true);
api('/invite', { method: 'POST', body: { channel: channel, target: target ? enNum(target.value.trim()) : '' } })
.then(function (data) {
toast(data.message || 'دعوت‌نامه ساخته شد', !data.sent && data.error);
go('connect');
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'invite-revoke': function (el) {
lock(el, true);
api('/invite/revoke', { method: 'POST', body: {} }).then(function (data) {
toast(data.message || 'لغو شد');
go('connect');
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'share-toggle': function (el) {
var key = el.getAttribute('data-key');
var on = el.classList.contains('on');
api('/invite/toggle', { method: 'POST', body: { key: key, value: on ? 0 : 1 } })
.then(function (data) {
el.classList.toggle('on');
el.setAttribute('aria-checked', el.classList.contains('on') ? 'true' : 'false');
toast(data.message || 'ذخیره شد');
}).catch(function (err) { toast(err.message, true); });
},
'copy-link': function () {
var chip = q('#mb-invite-link');
if (!chip) { return; }
var text = chip.textContent.trim();
if (navigator.clipboard && navigator.clipboard.writeText) {
navigator.clipboard.writeText(text).then(function () { toast('لینک کپی شد'); }, function () { fallbackCopy(text); });
} else { fallbackCopy(text); }
},
'support-send': function (el) {
lock(el, true);
api('/support/send', { method: 'POST', body: { text: 'امروز به کمی همراهی بیشتر نیاز دارم.' } })
.then(function (data) { toast(data.message || 'فرستاده شد'); })
.catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'partner-ack': function (el) {
lock(el, true);
api('/partner/ack', { method: 'POST', body: {} })
.then(function (data) { toast(data.message || 'ثبت شد'); })
.catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'partner-snooze': function (el) {
lock(el, true);
api('/partner/snooze', { method: 'POST', body: {} })
.then(function (data) { toast(data.message || 'فردا یادآوری می‌شود'); })
.catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'partner-toggle': function (el) {
var key = el.getAttribute('data-key');
api('/partner/toggle', { method: 'POST', body: { key: key } }).then(function (data) {
if (el.classList.contains('toggle')) {
el.classList.toggle('on', !!data.value);
el.setAttribute('aria-checked', data.value ? 'true' : 'false');
} else {
el.classList.toggle('on', !!data.value);
el.setAttribute('aria-pressed', data.value ? 'true' : 'false');
}
toast(faNum(data.done) + ' از ' + faNum(data.total) + ' انجام شد');
}).catch(function (err) { toast(err.message, true); });
},
notifications: function (el) {
api('/notifications').then(function (data) {
showPanel('اعلان‌ها', data.items);
return api('/notifications/read', { method: 'POST', body: {} });
}).then(function () {
var badge = q('.ic.n.bell .badge');
if (badge) { badge.remove(); }
}).catch(function (err) { toast(err.message, true); });
},
'cal-search': function () { togglePanel('#mb-cal-search', '#mb-cal-jump'); },
'cal-jump': function () {
var input = q('#mb-cal-jump');
if (!input) { return; }
var value = enNum(input.value.trim());
var parts = value.split(/[\/\-]/);
if (parts.length < 2) { toast('تاریخ را مثل ۱۴۰۵/۰۶/۲۷ بنویس', true); return; }
go('calendar/' + parts[0] + '-' + (parts[1].length < 2 ? '0' + parts[1] : parts[1]));
},
'health-search': function () { togglePanel('#mb-health-search', '#mb-health-q'); },
'health-query': function () {
var input = q('#mb-health-q');
if (!input) { return; }
api('/articles?s=' + encodeURIComponent(input.value.trim())).then(function (data) {
renderArticles(data.items);
}).catch(function (err) { toast(err.message, true); });
},
'article-open': function (el) {
var id = el.getAttribute('data-id');
api('/articles?id=' + encodeURIComponent(id)).then(function (data) {
var found = (data.items && data.items.length) ? data.items[0] : null;
var box = q('#mb-article-view');
if (!box) { return; }
if (!found || !found.content) {
toast(data.locked ? 'این راهنما در نسخهٔ پیشرفته باز می‌شود' : 'متن راهنما یافت نشد', true);
return;
}
box.innerHTML = '<h2 class="h2">' + escapeHtml(found.title) + '</h2>' + found.content;
box.hidden = false;
box.scrollIntoView({ behavior: 'smooth', block: 'start' });
}).catch(function (err) { toast(err.message, true); });
},
'article-save': function (el) {
api('/articles/save', { method: 'POST', body: { id: parseInt(el.getAttribute('data-id'), 10) } })
.then(function (data) { toast(data.message || 'ذخیره شد'); })
.catch(function (err) { toast(err.message, true); });
},
'ask-toggle': function () {
var box = q('#mb-ask-form');
if (box) { box.hidden = !box.hidden; }
},
'ask-send': function (el) {
var input = q('#mb-question');
if (!input || input.value.trim().length < 10) { toast('پرسش را کمی کامل‌تر بنویس', true); return; }
lock(el, true);
var anon = q('#mb-question-anon');
var cat = q('#mb-question-cat');
api('/question', {
method: 'POST',
body: {
body: input.value.trim(),
share_anon: !!(anon && anon.checked),
cat: cat ? cat.value : 'period'
}
})
.then(function (data) {
toast(data.message || 'ثبت شد');
input.value = '';
go('health');
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'plan-select': function (el) {
qa('.plan-choice').forEach(function (choice) { choice.classList.remove('on'); choice.setAttribute('aria-checked', 'false'); var radio = q('input', choice); if (radio) { radio.checked = false; } });
el.classList.add('on');
el.setAttribute('aria-checked', 'true');
var input = q('input', el); if (input) { input.checked = true; }
},
'coupon-apply': function (el) {
var coupon = q('#mb-coupon');
var selected = q('.plan-choice.on');
var plan = selected ? parseInt(selected.getAttribute('data-plan'), 10) : 1;
if (!coupon || !coupon.value.trim()) { toast('کد تخفیف را وارد کن', true); return; }
lock(el, true);
api('/checkout/coupon', { method: 'POST', body: { coupon: coupon.value.trim(), plan: plan } })
.then(function (data) {
var box = q('#mb-coupon-result');
if (box) {
box.className = 'pnote good';
box.innerHTML = '<span>' + escapeHtml(data.percent ? data.percent + '٪ تخفیف اعمال شد' : 'تخفیف اعمال شد') +
' — پرداختی: ' + escapeHtml(data.amount_fa) + ' تومان (' + escapeHtml(data.off_fa) + ' تومان کمتر)</span>';
box.hidden = false;
}
toast(data.message || 'کد اعمال شد');
}).catch(function (err) {
var box = q('#mb-coupon-result');
if (box) { box.className = 'pnote warn'; box.innerHTML = '<span>' + escapeHtml(err.message) + '</span>'; box.hidden = false; }
toast(err.message, true);
}).then(function () { lock(el, false); });
},
'checkout-recover': function (el) {
lock(el, true);
api('/checkout/recover', { method: 'POST', body: {} })
.then(function (data) {
toast(data.message, !data.ok);
if (data.ok) { setTimeout(function () { render('checkout'); }, 900); }
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
checkout: function (el) {
var coupon = q('#mb-coupon');
lock(el, true);
var selected = q('.plan-choice.on');
var plan = selected ? parseInt(selected.getAttribute('data-plan'), 10) : 1;
api('/checkout/create', { method: 'POST', body: { coupon: coupon ? coupon.value.trim() : '', plan: plan } })
.then(function (data) {
if (data.redirect) {
toast('در حال انتقال به درگاه…');
window.location.href = data.redirect;
} else { toast('درگاه پاسخ نداد', true); }
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
logout: function (el) {
if (!confirm('مطمئن‌ای؟')) { return; }
lock(el, true);
api('/auth/logout', { method: 'POST', body: {} })
.then(function (data) {
notifyNativeBridgeLogout();
cfg.logged = false;
if (app) { app.setAttribute('data-logged', '0'); }
toast(data.message || 'خروج انجام شد');
go('login', true);
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
sos: function (el) {
if (!window.confirm('هشدار اضطراری فرستاده شود؟\nبه مخاطب اضطراری و همسرت پیام می‌رود، بدون هیچ جزئیات پزشکی.')) { return; }
lock(el, true);
api('/emergency/sos', { method: 'POST', body: {} })
.then(function (data) {
if (data && data.setup) {
toast(data.message || 'ابتدا پروفایل سلامت را کامل کن', true);
go(data.route || 'health-profile');
return;
}
toast(data.message || 'اطلاع‌رسانی ارسال شد', !(data && data.ok));
}).catch(function (err) { toast(err.message || 'ارسال هشدار انجام نشد', true); })
.then(function () { lock(el, false); });
},
'health-save': function (el) {
var form = q('#health-form');
if (!form) { return; }
var val = function (sel) {
var node = q(sel, form);
return node ? String(node.value || '').trim() : '';
};
lock(el, true);
var body = {
height_cm: fieldNum('#health-height'),
weight_kg: fieldNum('#health-weight'),
waist_cm: fieldNum('#health-waist'),
blood_type: val('#health-blood'),
national_id: enNum(val('#health-nid')),
emergency_name: val('#health-ename'),
emergency_phone: enNum(val('#health-ephone')),
medical_note: val('#health-note'),
emergency_alert: !!(q('#health-alert', form) && q('#health-alert', form).checked)
};
api('/health-profile/save', { method: 'POST', body: body })
.then(function (data) {
toast(data.message || 'ذخیره شد');
if (data.bmi_label) { toast('BMI: ' + (data.bmi_fa || '') + ' · ' + data.bmi_label); }
})
.catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'screening-submit': function (el) {
var form = q('#screening-form');
if (!form) { return; }
lock(el, true);
var answers = {};
var missing = 0;
qa('fieldset.qz', form).forEach(function (set, index) {
var picked = set.querySelector('input[type=radio]:checked');
if (picked) { answers[index] = parseInt(picked.value, 10); } else { missing++; }
});
if (missing > 0) {
lock(el, false);
toast('به همهٔ پرسش‌ها پاسخ بده', true);
return;
}
var toolKey = form.getAttribute('data-tool') || '';
api('/screenings/submit', { method: 'POST', body: { tool: toolKey, answers: answers } })
.then(function (data) {
toast(data.message || 'ثبت شد');
go('screening-result/' + (data.slug || toolKey));
}).catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'cycle-save': function (el) {
var form = q('#cycle-form');
if (!form) { return; }
var last = q('#cycle-last', form);
var jalali = last ? enNum(String(last.value || '').trim()).replace(/[^0-9\/\-]/g, '') : '';
if (jalali && !/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/.test(jalali)) {
toast('تاریخ شمسی را به شکل ۱۴۰۴/۰۷/۰۵ وارد کن', true);
return;
}
lock(el, true);
var body = {
cycle_length: fieldNum('#cycle-len') || 28,
period_length: fieldNum('#period-len') || 5,
last_period_jalali: jalali,
is_irregular: !!(q('#cycle-irregular', form) && q('#cycle-irregular', form).checked)
};
api('/cycles/editor/save', { method: 'POST', body: body })
.then(function (data) {
toast(data.message || 'ذخیره شد');
setTimeout(function () { go(data.redirect || 'home', true); }, 600);
})
.catch(function (err) { toast(err.message, true); })
.then(function () { lock(el, false); });
},
'faq-search': function () {
var input = q('#faq-search');
if (!input) { return; }
var query = input.value.trim().toLowerCase();
qa('.accordion-item').forEach(function (item) {
var question = (q('.accordion-trigger', item) || {}).textContent || '';
var answer = (q('.accordion-body', item) || {}).textContent || '';
var match = question.toLowerCase().indexOf(query) >= 0 || answer.toLowerCase().indexOf(query) >= 0;
item.style.display = match ? '' : 'none';
});
},
'accordion-toggle': function (el) {
var body = el.nextElementSibling;
if (!body || !body.classList.contains('accordion-body')) { return; }
var closed = body.hidden || body.style.display === 'none';
body.hidden = !closed;
body.style.display = closed ? '' : 'none';
el.classList.toggle('open', closed);
el.setAttribute('aria-expanded', closed ? 'true' : 'false');
},
'toggle': function (el) {
var action = el.getAttribute('data-action');
if (!/^share_[a-z_]+$/.test(String(action || ''))) { return; }
var isBox = 'INPUT' === el.tagName && 'checkbox' === el.type;
var next = isBox ? !!el.checked : el.getAttribute('aria-checked') !== 'true';
var paint = function (value) {
if (isBox) { el.checked = value; return; }
el.classList.toggle('on', value);
el.setAttribute('aria-checked', value ? 'true' : 'false');
};
paint(next);
lock(el, true);
var body = {};
body[action] = next;
api('/privacy/' + action, { method: 'POST', body: body })
.then(function (data) { toast(data.message || 'ذخیره شد'); })
.catch(function (err) { paint(!next); toast(err.message, true); })
.then(function () { lock(el, false); });
}
};

/* ------------------------------------------------------------------ */
/* کمکی‌های رابط                                                      */
/* ------------------------------------------------------------------ */
function lock(el, state) {
if (!el) { return; }
if (state) { el.setAttribute('disabled', 'disabled'); } else { el.removeAttribute('disabled'); }
}
function togglePanel(panelSel, focusSel) {
var panel = q(panelSel);
if (!panel) { return; }
panel.hidden = !panel.hidden;
var input = q(focusSel);
if (!panel.hidden && input) { input.focus(); }
}
function fallbackCopy(text) {
var ta = document.createElement('textarea');
ta.value = text;
ta.setAttribute('readonly', 'readonly');
ta.style.position = 'fixed';
ta.style.opacity = '0';
document.body.appendChild(ta);
ta.select();
try { document.execCommand('copy'); toast('لینک کپی شد'); } catch (e) { toast('کپی نشد؛ دستی انتخاب کن', true); }
document.body.removeChild(ta);
}
function escapeHtml(str) {
return String(str).replace(/[&<>"']/g, function (c) {
return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
});
}
function renderArticles(items) {
var list = q('#mb-article-list .list');
if (!list) { return; }
if (!items || !items.length) {
list.innerHTML = '<p class="body">چیزی پیدا نشد.</p>';
return;
}
list.innerHTML = items.map(function (item) {
return '<button type="button" class="lrow" data-mb="article-open" data-id="' + item.id + '">' +
'<span class="lrow-body"><span class="lrow-t">' + escapeHtml(item.title) + '</span>' +
'<span class="lrow-s">' + faNum(Math.max(1, item.minutes)) + ' دقیقه</span></span></button>';
}).join('');
}
function saveReminder(card) {
var sw = q('.sw', card);
var hour = q('[data-mb="rem-hour"]', card);
var chan = q('[data-mb="rem-channel"]', card);
api('/reminders/save', {
method: 'POST',
body: {
trigger: card.getAttribute('data-trigger'),
on: !!(sw && sw.classList.contains('on')),
hour: hour ? parseInt(hour.value, 10) : 9,
channel: chan ? chan.value : 'inapp'
}
}).then(function (data) { toast(data.message || 'ذخیره شد'); })
.catch(function (err) { toast(err.message, true); });
}
function paintQuizResult(data) {
var box = q('#mb-quiz-result');
var form = q('#mb-quiz-form');
if (!box) { return; }
var article = '';
if (data.article && data.article.title) {
article = '<a class="chip gold" href="#/health/' + escapeHtml(data.article.cat || '') + '">' + escapeHtml(data.article.title) + '</a>';
}
box.innerHTML = '<div class="glass card center">' +
'<div class="qr-score"><span class="hero-num">' + faNum(data.percent) + '٪</span>' +
'<span class="small">' + faNum(data.score) + ' از ' + faNum(data.max) + '</span></div>' +
'<h2 class="h1">' + escapeHtml(data.label || '') + '</h2>' +
'<p class="body">' + escapeHtml(data.advice || '') + '</p>' +
'<div class="chips center-chips">' + article + '</div>' +
'<p class="small disclaimer">' + escapeHtml(data.note || '') + '</p>' +
'</div>';
box.hidden = false;
if (form) { form.hidden = true; }
box.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
function copyText(text) {
var done = function () { toast((MB_BOOT.i18n && MB_BOOT.i18n.copied) || 'کپی شد'); };
if (navigator.clipboard && navigator.clipboard.writeText) {
navigator.clipboard.writeText(text).then(done).catch(function () { legacyCopy(text, done); });
return;
}
legacyCopy(text, done);
}
function legacyCopy(text, done) {
var ta = document.createElement('textarea');
ta.value = text;
ta.setAttribute('readonly', 'readonly');
ta.style.position = 'fixed';
ta.style.opacity = '0';
document.body.appendChild(ta);
ta.select();
try { document.execCommand('copy'); done(); } catch (e) { done(); }
document.body.removeChild(ta);
}
function showPanel(title, items) {
var existing = q('#mb-panel');
if (existing) { existing.remove(); }
var rows = (items && items.length) ? items.map(function (item) {
return '<div class="lrow"><span class="lrow-body"><span class="lrow-t">' + escapeHtml(item.title) + '</span>' +
'<span class="lrow-s">' + escapeHtml(item.body || '') + ' · ' + escapeHtml(item.date || '') + '</span></span></div>';
}).join('') : '<p class="body">اعلانی نداری.</p>';
var panel = document.createElement('div');
panel.id = 'mb-panel';
panel.className = 'glass card list';
panel.style.position = 'fixed';
panel.style.left = '50%';
panel.style.right = 'auto';
panel.style.transform = 'translateX(-50%)';
panel.style.bottom = 'calc(var(--tabbar-h) + 16px)';
panel.style.width = 'calc(100% - 32px)';
panel.style.maxWidth = '398px';
panel.style.maxHeight = '54vh';
panel.style.overflowY = 'auto';
panel.style.zIndex = '30';
panel.innerHTML = '<div class="calm-head"><h2 class="h2">' + escapeHtml(title) + '</h2>' +
'<button type="button" class="ic n" data-mb="panel-close" aria-label="بستن">✕</button></div>' + rows;
app.appendChild(panel);
}

/* ------------------------------------------------------------------ */
/* راه‌اندازی                                                          */
/* ------------------------------------------------------------------ */
function onClick(event) {
var el = event.target.closest ? event.target.closest('[data-mb]') : null;
if (!el) { return; }
var name = el.getAttribute('data-mb');
if ('panel-close' === name) {
var panel = q('#mb-panel');
if (panel) { panel.remove(); }
return;
}
if (!actions[name]) { return; }
var tag = el.tagName;
var isField = 'INPUT' === tag || 'TEXTAREA' === tag || 'SELECT' === tag;
if (!isField) { event.preventDefault(); }
actions[name](el, event);
}
function boot() {
app = q('#mb-app');
if (!app) { return; }
view = q('#mb-view');
toastEl = q('#mb-toast');
cfg.rest = app.getAttribute('data-rest') || (window.MB_BOOT ? window.MB_BOOT.rest : '');
cfg.nonce = app.getAttribute('data-nonce') || (window.MB_BOOT ? window.MB_BOOT.nonce : '');
cfg.authNonce = app.getAttribute('data-auth-nonce') || (window.MB_BOOT ? window.MB_BOOT.authNonce : '');
cfg.logged = app.getAttribute('data-logged') === '1';
/* پوستهٔ نیتیو: سرویس‌ورکر کهنه نباید نسخهٔ کش‌شده سرو کند. */
if (/\bMoonBanuApp\//.test(navigator.userAgent) && 'serviceWorker' in navigator) {
try {
navigator.serviceWorker.getRegistrations().then(function (regs) {
(regs || []).forEach(function (reg) { reg.unregister(); });
});
} catch (e) {}
}
document.addEventListener('click', onClick, false);
window.addEventListener('hashchange', onHashChange, false);
history_.push(currentRoute());
afterRender();
var route = currentRoute();
if (route && route !== app.getAttribute('data-boot')) { render(route); }
}
if (document.readyState === 'loading') {
document.addEventListener('DOMContentLoaded', boot);
} else { boot(); }
}());