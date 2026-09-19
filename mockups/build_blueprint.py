# -*- coding: utf-8 -*-
"""Self-contained product blueprint HTML (with tiny embedded previews)."""
import base64, io, pathlib
from PIL import Image

SHOTS = pathlib.Path("/home/user/screenshots")
OUT = pathlib.Path("/home/user/screens/moonbanu-blueprint.html")

ORDER = [
    ("01-splash", "اسپلش", "برندینگ"),
    ("02-onboarding", "آنبوردینگ", "برندینگ"),
    ("03-today-home", "صفحهٔ امروز", "اصلی"),
    ("04-calendar", "تقویم شمسی", "اصلی"),
    ("05-day-detail", "جزئیات روز", "اصلی"),
    ("06-log-symptoms", "ثبت نشانه‌ها", "داده"),
    ("07-insights", "تحلیل هوشمند", "هوشمند"),
    ("08-cycle-map", "نقشهٔ چرخه", "هوشمند"),
    ("09-health-center", "مرکز سلامت", "آموزش"),
    ("10-partner-link", "اتصال همسر", "همسر"),
    ("11-partner-home", "خانهٔ همسر", "همسر"),
    ("12-partner-alert", "یادآور مهربانی", "همسر"),
    ("13-partner-calm", "حالت آرامش", "همسر"),
]


def thumb(name, w=118):
    im = Image.open(SHOTS / f"{name}.png").convert("RGB")
    im = im.resize((w, int(im.height * w / im.width)), Image.LANCZOS)
    b = io.BytesIO(); im.save(b, "JPEG", quality=62, optimize=True)
    return "data:image/jpeg;base64," + base64.b64encode(b.getvalue()).decode()


def build():
    cards = "".join(f'''<figure class="shot"><img src="{thumb(n)}" alt="{t}"><figcaption>{num}. {t}<span>{tag}</span></figcaption></figure>'''
                    for num, (n, t, tag) in enumerate(ORDER, start=1))
    html = f'''<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">
<title>ماه‌بانو — نقشهٔ راه محصول</title><style>
*{{box-sizing:border-box;margin:0;padding:0}}
body{{font-family:Tahoma,system-ui,sans-serif;background:#08050E;color:#F4EFF7;line-height:2.05;padding:0 0 80px}}
.wrap{{max-width:1080px;margin:0 auto;padding:0 30px}}
header{{background:radial-gradient(90% 120% at 85% -20%,#3A1B4E 0%,#160E24 45%,#08050E 100%);padding:56px 0 40px;border-bottom:1px solid rgba(217,180,91,.22)}}
.kicker{{color:#D9B45B;font-size:12.5px;letter-spacing:3px;margin-bottom:14px}}
h1{{font-size:36px;background:linear-gradient(135deg,#FAEFC8,#D9B45B 55%,#9C7228);-webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:12px}}
.tagline{{color:#B9AECB;font-size:14px;max-width:720px}}
.meta{{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}}
.pill{{border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.05);border-radius:999px;padding:6px 14px;font-size:12px;color:#E8E0F2}}
h2{{font-size:21px;margin:46px 0 6px;padding-right:14px;border-right:3px solid #D9B45B}}
h3{{font-size:15.5px;margin:24px 0 6px;color:#F7F1E4}}
p,li{{font-size:13.5px;color:#C7BEDA}}
ul,ol{{padding-right:22px;margin:8px 0}}
li{{margin:4px 0}}
table{{width:100%;border-collapse:collapse;margin:12px 0;font-size:12.6px}}
th,td{{border:1px solid rgba(255,255,255,.09);padding:9px 11px;text-align:right;vertical-align:top}}
th{{background:rgba(217,180,91,.10);color:#FAEFC8;font-weight:700}}
td{{background:rgba(255,255,255,.025);color:#C7BEDA}}
code{{background:rgba(255,255,255,.07);border-radius:6px;padding:2px 6px;font-size:12px;color:#F3E3B4;direction:ltr;display:inline-block}}
.grid{{display:grid;grid-template-columns:repeat(auto-fill,minmax(118px,1fr));gap:14px;margin:18px 0 6px}}
.shot{{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);border-radius:14px;padding:7px;text-align:center}}
.shot img{{width:100%;border-radius:10px;display:block}}
.shot figcaption{{font-size:10.5px;color:#B9AECB;margin-top:6px;line-height:1.6}}
.shot figcaption span{{display:block;color:#8D82A6;font-size:9.5px}}
.box{{border:1px solid rgba(255,255,255,.10);background:linear-gradient(180deg,rgba(255,255,255,.055),rgba(255,255,255,.02));
border-radius:18px;padding:16px 18px;margin:14px 0}}
.box.gold{{border-color:rgba(217,180,91,.35);background:linear-gradient(135deg,rgba(217,180,91,.13),rgba(255,255,255,.02))}}
.box.rose{{border-color:rgba(234,63,99,.30);background:linear-gradient(135deg,rgba(234,63,99,.12),rgba(255,255,255,.02))}}
.box.teal{{border-color:rgba(18,165,148,.30);background:linear-gradient(135deg,rgba(18,165,148,.12),rgba(255,255,255,.02))}}
.box h4{{font-size:14px;margin-bottom:6px;color:#FAEFC8}}
.sw{{display:flex;gap:12px;flex-wrap:wrap;margin:10px 0}}
.sw div{{width:112px;border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.12);font-size:10.5px;text-align:center}}
.sw i{{display:block;height:46px}}
.sw b{{display:block;padding:5px 4px;font-weight:600;color:#C7BEDA;direction:ltr}}
.two{{display:grid;grid-template-columns:1fr 1fr;gap:14px}}
@media(max-width:720px){{.two{{grid-template-columns:1fr}}}}
.flow{{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:10px 0}}
.flow span{{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:7px 11px;font-size:12px}}
.flow em{{color:#D9B45B;font-style:normal}}
footer{{color:#7C7191;font-size:12px;text-align:center;margin-top:50px;line-height:2.2}}
</style></head><body>
<header><div class="wrap">
  <div class="kicker">PRODUCT BLUEPRINT • نسخهٔ ۱٫۰</div>
  <h1>ماه‌بانو</h1>
  <div class="tagline">اپلیکیشن حرفه‌ای ردیابی چرخهٔ ماهانهٔ بانوان با تقویم شمسی، فاز‌بندی پزشکی، پیش‌بینی هوشمند، آموزش سلامت و حالت اختصاصی «همراهِ ماه» برای همسر — با تجربهٔ بصری لوکس، آرام و کاملاً محرمانه.</div>
  <div class="meta"><span class="pill">۱۳ صفحهٔ طراحی‌شده</span><span class="pill">فارسی / RTL</span>
  <span class="pill">iOS + Android</span><span class="pill">تقویم شمسی</span><span class="pill">پروندهٔ سلامت</span></div>
</div></header>
<div class="wrap">

<h2>۱. چشم‌انداز و جایگاه محصول</h2>
<p>ماه‌بانو فقط یک «تقویم پریود» نیست؛ یک همراه سلامتی است که سه کار را هم‌زمان و دقیق انجام می‌دهد:</p>
<ol>
  <li><b>پیش‌بینی و برنامه‌ریزی:</b> تاریخ دقیق قاعدگی، پنجرهٔ باروری، تخمک‌گذاری، PMS و روزهای عادی — روی تقویم شمسی و برای چند چرخهٔ آینده.</li>
  <li><b>ثبت و تحلیل سلامت:</b> نشانه‌ها، خلق، درد، خواب و انرژی؛ کشف الگوهای شخصی بدن و هشدارهای سلامتی.</li>
  <li><b>همراهی همسر:</b> اطلاع‌رسانی محترمانه و زمان‌بندی‌شده به همسر با راهنمای عملی حمایت — بدون افشای داده‌های خصوصی.</li>
</ol>
<div class="box gold"><h4>وعدهٔ برند</h4>
<p>«ماهِ تو را از قبل می‌دانیم، تا با آرامش زندگی کنی.» لحن محصول: مهربان، دقیق، بدون شرم و بدون قضاوت.</p></div>

<h2>۲. زبان بصری (Design System — Noir Bloom)</h2>
<p>ترکیب پس‌زمینهٔ بنفش–مشکی عمیق با جزئیات طلایی و شیشه‌ای (Glassmorphism) + رنگی‌کردن معنادار فازها. فونت: <code>Vazirmatn</code> (۴۰۰ تا ۸۰۰). شعاع کارت‌ها ۲۰–۲۶px، سایه‌های نرم، اعداد فارسی با تراز جدولی.</p>
<div class="sw">
  <div><i style="background:#0B0713"></i><b>#0B0713</b></div>
  <div><i style="background:linear-gradient(135deg,#FAEFC8,#D9B45B 60%,#9C7228)"></i><b>gold</b></div>
  <div><i style="background:#EA3F63"></i><b>#EA3F63 قاعدگی</b></div>
  <div><i style="background:#12A594"></i><b>#12A594 باروری</b></div>
  <div><i style="background:#8B6BF5"></i><b>#8B6BF5 تخمک‌گذاری</b></div>
  <div><i style="background:#EE9A2E"></i><b>#EE9A2E PMS</b></div>
  <div><i style="background:#B07C2A"></i><b>#B07C2A لوتئال</b></div>
  <div><i style="background:#3E86E8"></i><b>#3E86E8 فولیکولی</b></div>
</div>
<table>
  <tr><th>رنگ</th><th>معنا</th><th>کاربرد</th></tr>
  <tr><td>طلایی</td><td>هویت، امروز، پیشرفته</td><td>لوگو، CTA اصلی، حلقهٔ امروز، نسخهٔ Pro</td></tr>
  <tr><td>سرخابی</td><td>قاعدگی/خونریزی</td><td>سلول‌های روز پریود، نشانگر خونریزی، هشدار سلامتی</td></tr>
  <tr><td>فیروزه‌ای</td><td>پنجرهٔ باروری</td><td>روزهای پراحتمال، آیکون‌های سلامت</td></tr>
  <tr><td>بنفش</td><td>تخمک‌گذاری</td><td>روز اوج، نمودار انرژی</td></tr>
  <tr><td>نارنجی/خاکی</td><td>PMS و لوتئال</td><td>هشدارهای آرام‌سازی، کارت‌های مراقبت</td></tr>
</table>

<h2>۳. سیزده صفحهٔ طراحی‌شده</h2>
<div class="grid">{cards}</div>

<h2>۴. موتور محاسبهٔ چرخه (قلب فنی محصول)</h2>
<p>هر تاریخ شمسی به شمارهٔ روز مطلق (JDN) تبدیل می‌شود؛ فاز هر روز با «روز چرخه» تعیین می‌گردد. این منطق باید در اپ هم دقیقاً همین باشد:</p>
<table>
  <tr><th>متغیر</th><th>مقدار پیش‌فرض</th><th>توضیح</th></tr>
  <tr><td>طول چرخه</td><td>۲۸ روز</td><td>از میانگین ۳ تا ۶ چرخهٔ آخر کاربر به‌روزرسانی می‌شود (۲۱–۳۵ نرمال)</td></tr>
  <tr><td>طول قاعدگی</td><td>۵ روز</td><td>قابل تنظیم ۲–۷ روز</td></tr>
  <tr><td>فاز لوتئال</td><td>۱۴ روز</td><td>ثابت و نسبتاً پایدار؛ مبنای محاسبهٔ تخمک‌گذاری</td></tr>
  <tr><td>تخمک‌گذاری</td><td>روز ۱۴ = طول چرخه − ۱۴</td><td>بر پایهٔ مدل لوتئال ثابت، نه «۱۴ مطلق»</td></tr>
  <tr><td>پنجرهٔ باروری</td><td>روز ۹ تا ۱۵ (۷ روز)</td><td>۵ روز قبل از تخمک‌گذاری تا ۲۴ ساعت بعد (بقای اسپرم ~۵ روز)</td></tr>
  <tr><td>پنجرهٔ PMS</td><td>۷ روز آخر چرخه (روز ۲۲ به بعد)</td><td>در کاربر این نمونه ۳ روز زودتر از میانگین شروع می‌شود → یادگیری شخصی</td></tr>
</table>
<div class="box teal"><h4>نمونهٔ واقعی محاسبهٔ همین طرح</h4>
<p>آخرین قاعدگی: <b>۷ شهریور ۱۴۰۵</b> → چرخهٔ ۲۸ روزه → قاعدگی بعدی <b>۴ مهر</b>؛ پنجرهٔ باروری <b>۱۵ تا ۲۱ شهریور</b>؛ تخمک‌گذاری <b>۲۰ شهریور</b>؛ PMS از <b>۲۸ شهریور تا ۳ مهر</b>. امروز <b>۲۷ شهریور</b> = روز ۲۱ چرخه (فاز لوتئال). همهٔ این تاریخ‌ها در اسکرین‌شات‌ها با همین منطق نمایش داده شده‌اند.</p></div>
<h3>الگوریتم یادگیری (نسخهٔ ۲)</h3>
<ul>
  <li>میانگین متحرک وزنی طول چرخه + انحراف معیار برای «پنجرهٔ عدم قطعیت» (±۱ تا ۳ روز).</li>
  <li>مدل شخصی PMS: با ثبت روزانهٔ نشانه‌ها، تاریخ شروع واقعی PMS هر کاربر یاد گرفته می‌شود.</li>
  <li>پیش‌بینی احتمال هر نشانه در هر روز (مثل «نوسان خلق ۷۸٪») بر پایهٔ فراوانی تاریخی همان کاربر.</li>
  <li>در صورت بی‌نظمی (انحراف > ۷ روز در ۳ چرخه) → پیشنهاد مراجعه به پزشک.</li>
</ul>

<h2>۵. فهرست کامل امکانات (Feature Map)</h2>
<div class="two">
<div class="box"><h4>بخش بانوان — هستهٔ اصلی</h4>
<ul>
  <li>تقویم شمسی رنگ‌بندی‌شده + پیش‌بینی ۳ تا ۶ چرخهٔ آینده</li>
  <li>صفحهٔ امروز: فاز، روز چرخه، شمارش معکوس، انرژی، خواب، احتمال بارداری</li>
  <li>ثبت روزانه: خونریزی، خلق، درد، نشانه‌ها (۸+ مورد)، خواب، رابطه، یادداشت</li>
  <li>تنظیم چرخه: LMP، طول چرخه، طول قاعدگی، روزهای لکه‌بینی</li>
  <li>یادآورها: یک روز قبل قاعدگی، روز شروع، دارو/قرص، ورزش، پرکردن لاگ</li>
  <li>تحلیل: میانگین چرخه، نوسان، نمودار روند، الگوهای شخصی، دقت مدل</li>
  <li>مرکز سلامت: آموزش قاعدگی، پیشگیری و سلامت جنسی، تغذیه و ورزش هر فاز، سلامت روان، علائم هشدار، پرسش از متخصص</li>
  <li>حالت‌های ویژه: بارداری، شیردهی، یائسگی، پیش از یائسگی، PCOS/اندومتریوز</li>
</ul></div>
<div class="box"><h4>بخش همسر — «همراهِ ماه»</h4>
<ul>
  <li>پروفایل مستقل با کد/QR و رد و بدل کد دوطرفه٭‌</li>
  <li>نمای ۷ روز آینده با رنگ فازها (بدون نشانه‌های خصوصی)</li>
  <li>پیام «یادآور مهربانی»: ۲ روز قبل PMS + روز اول قاعدگی</li>
  <li>چک‌لیست حمایت روزانه (۳ کار کوچک و عملی)</li>
  <li>راهنمای «چه بگویم / چه نگویم» و «حالت آرامش»</li>
  <li>تعویض نوبتی مسئولیت‌ها و کمک‌های عملی (خرید دارو، آشپزی، سکوت و آرامش)</li>
  <li>کنترل کامل حریم خصوصی: هر بخش جداگانه روشن/خاموش، لغو دسترسی بی‌اطلاع طرف مقابل</li>
</ul></div>
</div>
<div class="box rose"><h4>قواعد حریم خصوصی (تضمینی — نباید نقض شود)</h4>
<ul>
  <li>نشانه‌ها، خلق، یادداشت‌های خصوصی، رابطهٔ جنسی و دیسپارونی <b>هرگز</b> به همسر نمایش داده نمی‌شود.</li>
  <li>پنجرهٔ باروری و تخمک‌گذاری به‌صورت پیش‌فرض <b>پنهان</b> است (خطر: تطبیق با اهداف پیشگیری/بارداری شخص سوم).</li>
  <li>دسترسی همسر می‌تواند در هر لحظه و بدون اطلاع او لغو شود.</li>
  <li>همهٔ داده‌ها رمزنگاری at-rest؛ قفل بیومتریک اختیاری روی اپ.</li>
</ul></div>

<h2>۶. منطق اطلاع‌رسانی به همسر (نباید شبیه کنترل باشد)</h2>
<table>
  <tr><th>رویداد</th><th>زمان ارسال</th><th>متن نمونه</th></tr>
  <tr><td>پیش‌هشدار PMS</td><td>۲ روز قبل از شروع پنجرهٔ PMS — ساعت ۹ صبح</td><td>«نگار از فردا ۷ روز حساس پیش رو دارد…»</td></tr>
  <tr><td>روز اول قاعدگی</td><td>روز شروع (با اجازهٔ کاربر)</td><td>«امروز روز اول قاعدگی نگار است؛ نیاز به استراحت و حمایت دارد.»</td></tr>
  <tr><td>روز آخر قاعدگی</td><td>روز پایانی</td><td>«قاعدگی نگار تمام شد؛ انرژی‌اش در روزهای آینده بهتر می‌شود.»</td></tr>
  <tr><td>کارهای روزانهٔ حمایتی</td><td>هر روز ۸ شب، حداکثر ۱ پیام در روز</td><td>«امشب یک ساعت آرامش و سکوت بده.»</td></tr>
</table>
<div class="box"><h4>قواعد ضد مزاحمت</h4>
<ul><li>حداکثر ۱ اعلان در روز؛ سقف ۱۲ اعلان در چرخه.</li>
<li>بدون پیام در ساعات ۲۳ تا ۸ صبح.</li>
<li>همسر نمی‌تواند درخواست دادهٔ بیشتر بدهد؛ فقط می‌تواند یادآورها را کم کند.</li>
<li>کاربر می‌تواند قبل از ارسال، متن را ویرایش کند (حالت «پیام آماده، ارسال با تأیید من»).</li></ul></div>

<h2>۷. دادهٔ پیشنهادی (Schema خلاصه)</h2>
<table>
  <tr><th>جدول</th><th>فیلدهای کلیدی</th></tr>
  <tr><td>user</td><td>id, name, birthDate, height, weight, locale(منطقه), biometricLock, createdAt</td></tr>
  <tr><td>cycle</td><td>id, userId, startDate(Jalali↔ISO), endDate, cycleLen, periodLen, ovulationDay, source(خودکار/دستی)، confidence</td></tr>
  <tr><td>dayLog</td><td>id, userId, date, flow(none/spotting/light/medium/heavy), pain(1..5), mood(1..5), sleepMin, energy, intercourse(bool), notes(رمزنگاری‌شده), symptoms[]</td></tr>
  <tr><td>symptom</td><td>id, key, labelFa, category, severityScale</td></tr>
  <tr><td>prediction</td><td>id, userId, date, phase, probability, modelVersion</td></tr>
  <tr><td>partnerLink</td><td>id, ownerUserId, partnerUserId, scopes(pms, periodStart, periodEnd, fertile, tasks), status, revokedAt</td></tr>
  <tr><td>notificationLog</td><td>id, userId, type, channel, scheduledAt, sentAt, status</td></tr>
  <tr><td>content</td><td>id, category, titleFa, bodyFa, expertReviewed, readTime, tags[]</td></tr>
</table>

<h2>۸. معماری فنی پیشنهادی</h2>
<div class="flow"><span>اپ موبایل: <em>Flutter</em> یا <em>React Native (Expo)</em></span><em>→</em>
<span>ذخیره‌سازی محلی: <em>SQLite / WatermelonDB</em> (آفلاین‌فرست)</span><em>→</em>
<span>سرور: <em>NestJS + PostgreSQL</em></span><em>→</em>
<span>اعلان: <em>FCM + APNs</em> و نوتیفیکیشن محلی</span></div>
<ul>
  <li><b>تقویم شمسی:</b> در Flutter پکیج <code>shamsi_date</code>؛ در RN <code>jalaali-js</code> یا <code>moment-jalaali</code>. منطق فازها را در یک ماژول مشترک <code>cycle-engine</code> بنویس و ۱۰۰٪ unit-test کن.</li>
  <li><b>آفلاین‌فرست:</b> همهٔ محاسبات پیش‌بینی روی دستگاه انجام شود؛ سرور فقط برای همگام‌سازی و اعلان همسر.</li>
  <li><b>رمزنگاری:</b> رمزنگاری فیلد یادداشت و دادهٔ سلامت با کلید مشتق از قفل دستگاه؛ TLS 1.3 برای انتقال.</li>
  <li><b>زمان‌بندی شمسی:</b> همهٔ یادآورها بر پایهٔ تاریخ محلی ایران (Asia/Tehran) و تقویم شمسی محاسبه شوند.</li>
  <li><b>تحلیل:</b> فاز اول آمار توصیفی سمت کلاینت؛ فاز دوم مدل سبک (on-device) برای یادگیری PMS شخصی.</li>
</ul>

<h2>۹. نقشهٔ راه ساخت</h2>
<table>
  <tr><th>مرحله</th><th>خروجی</th><th>زمان تقریبی</th></tr>
  <tr><td>M0 — پیاده‌سازی طراحی</td><td>Design tokens، کامپوننت‌ها، فونت، آیکون‌ها</td><td>۱ هفته</td></tr>
  <tr><td>M1 — MVP</td><td>تقویم شمسی + موتور چرخه + ثبت روزانه + صفحهٔ امروز + یادآور محلی</td><td>۳ تا ۴ هفته</td></tr>
  <tr><td>M2 — تحلیل و آموزش</td><td>تحلیل هوشمند، مرکز سلامت (۲۰ مقالهٔ اول)، انتخاب فاز دستی</td><td>۳ هفته</td></tr>
  <tr><td>M3 — بخش همسر</td><td>لینک دعوت، scopes حریم خصوصی، اعلان‌های حمایتی، راهنمای «حالت آرامش»</td><td>۳ هفته</td></tr>
  <tr><td>M4 — انتشار</td><td>تست‌های پزشکی/حریم خصوصی، نسخهٔ بتا، استورهای ایرانی + Google Play</td><td>۲ هفته</td></tr>
  <tr><td>M5 — نسخهٔ ۲</td><td>یادگیری شخصی، حالت بارداری، PCOS، پرسش از متخصص، ویجت و ساعت هوشمند</td><td>۶ هفته+</td></tr>
</table>

<h2>۱۰. نکات حقوقی و پزشکی</h2>
<div class="box rose"><ul>
  <li>در همهٔ صفحه‌ها تأکید شود: «این اپ جایگزین تشخیص یا درمان پزشکی نیست.»</li>
  <li>محتوای سلامت باید توسط متخصص زنان بازبینی و امضا شود.</li>
  <li>برای پیشگیری از بارداری، روش تقویمی به‌تنهایی توصیه نمی‌شود؛ روش‌های مطمئن‌تر پیشنهاد شود.</li>
  <li>رضایت صریح برای اشتراک‌گذاری با همسر، با امکان لغو یک‌طرفه و لاگ تغییرات.</li>
  <li>سازگاری با قوانین نگهداری دادهٔ سلامت و شرایط استورها (Privacy Nutrition Label / Data Safety).</li>
</ul></div>

<h2>۱۱. قدم بعدی</h2>
<ol>
  <li>تأیید همین ۱۳ طرح (رنگ، لحن، چیدمان).</li>
  <li>ساخت <b>Design Token</b> و کامپوننت‌های پایه (دکمه، کارت، چیپ، تقویم، حلقهٔ فاز).</li>
  <li>پیاده‌سازی <code>cycle-engine</code> با تست کامل روی ۱۰٬۰۰۰ تاریخ نمونه.</li>
  <li>ساخت MVP چهارصفحه‌ای و تست با ۱۰ کاربر واقعی.</li>
</ol>

<footer>ماه‌بانو • نقشهٔ راه محصول نسخهٔ ۱٫۰ — تاریخ مبنا: ۲۷ شهریور ۱۴۰۵<br>
فایل‌های طراحی: پوشهٔ <code>screenshots/</code> (PNG) و گالری <code>screens/moonbanu-ui-design.html</code></footer>
</div></body></html>'''
    OUT.write_text(html, encoding="utf-8")
    print("blueprint:", OUT, f"{OUT.stat().st_size//1024} KB")


if __name__ == "__main__":
    build()
