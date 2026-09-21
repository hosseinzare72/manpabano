# ماه‌بانو ۳٫۱٫۲ — بستن پرونده

نسخهٔ ممیزی و رفع نهایی. پس از این نسخه، فقط **پچ امنیتی، باگ واقعی و کارایی** مجاز است.

> این اپ جایگزین تشخیص یا درمان پزشکی نیست.

## ریشهٔ مشترک چهار باگ

لایهٔ ۳٫۱ (N3 پروفایل سلامت، N4 اضطراری، N6 ویرایشگر چرخه، N9 سوالات متداول،
N10 غربالگری) قالب و اکشن جاوااسکریپت داشت، ولی **نه مسیر روتر، نه اندپوینت REST و
نه قرارداد داده**. هر چهار باگ گزارش‌شده از همین حلقه‌های باز می‌آمدند.

## B1 — دکمهٔ SOS بی‌پاسخ

- **ریشه:** اکشن `sos` در `app.js` به `POST /emergency/sos` می‌زد و این مسیر **هرگز ثبت نشده بود**؛
  `MB_Emergency::sos()` کد کامل داشت و از هیچ‌جا صدا زده نمی‌شد.
- ثبت `moonbanu/v1/emergency/sos` و `emergency/status` با `permission_callback` بانو،
  نانس REST و rate limit (پنج درخواست در ده دقیقه).
- مودال تأیید پیش از ارسال؛ لمس اشتباهی هشدار نمی‌فرستد.
- اگر توگل خاموش است یا شمارهٔ اضطراری ثبت نشده: پاسخ `setup:true` و **باز شدن
  ویرایشگر پروفایل سلامت** با پیام راهنما. بی‌پاسخ‌بودن حذف شد.
- cooldown شش‌ساعته با پیام «چند دقیقه دیگر»؛ پیامک بدون هیچ جزئیات پزشکی (متن ثابت).
- ارزیابی خودکار اضطرار (`evaluate_log`) به `POST /log` وصل شد: خونریزی زیاد سه روز
  متوالی و خونریزی زیاد + درد بالا دوباره فعال شدند.

## B2 — دکمهٔ «تنظیم چرخه» بی‌پاسخ

- **ریشه:** `goto` با `route='cycle-editor'` به روتر می‌رسید ولی `render_route()` هیچ
  `case 'cycle-editor'` نداشت و به `default` (خانه) می‌افتاد. قالب `24-cycle-editor.php`
  ساخته شده بود و به هیچ مسیری وصل نبود. دکمهٔ ذخیرهٔ همان قالب هم `data-mb="submit"`
  داشت که هندلری برایش وجود ندارد (هندلر واقعی `cycle-save` است).
- افزودن `case 'cycle-editor'` + `data_cycle_editor()` (طول چرخه، طول قاعدگی، آغاز آخرین
  قاعدگی، تاریخچه، حالت نامنظم).
- ثبت `POST /cycles/editor/save` با اعتبارسنجی تاریخ شمسی، رد تاریخ آینده و
  **بازمحاسبهٔ آنی** (پاک‌شدن کش آمار و وضعیت) و بازگشت به خانه.
- ورودی تاریخ از `input[type=date]` میلادی به پیکر شمسی (`۱۴۰۴/۰۷/۰۵`) تغییر کرد.
- `حالت نامنظم` حالا ماندگار است: `MB_Irregular::set_manual()` / `manual()` و
  `is_irregular()` انتخاب دستی را محترم می‌شمارد.

## B3 — کادر طلایی دوتایی/زشت

- **ریشه:** در `style.css` دو قاب روی هم بود:
  `.glass.card.gold-line{border-top:3px solid;border-image:var(--grad-gold) 1}` که
  `border-image` با slice برابر ۱ روی **هر چهار ضلع** می‌نشیند و `border-radius` را
  بی‌اثر می‌کند (مستطیل تیز)، به‌علاوهٔ `.gold-line::before` که قاب گرد درست را می‌کشد.
- `border-image` و `border-top` حذف شد؛ تنها قاب باقی‌مانده `::before` با
  `border-radius:inherit` است. شعاع ۲۴ روی خود `.gold-line` هم تثبیت شد.
- همهٔ نُه کاربرد `.gold-line` روی `.glass card` بازبینی شد؛ قاب واحد و گرد.

## B4 — نام کاربر نمایش داده نمی‌شد

- **ریشه:** هدر خانه متن ثابت `سلام` داشت و کنترل‌کننده هیچ کلید `user_name` نمی‌داد.
- `data_common()` کلید `user_name` می‌فرستد: `first_name` با فال‌بک `display_name`.
- قالب خانه و داشبورد همسر: `سلام [نام کوچک]` با `esc_html`؛ نام خالی → فقط `سلام`.

## یافته‌های ممیزی استاتیک (بخش ۲)

### A) قالب‌ها و قرارداد داده — شش صفحه با کلید گم‌شده
- `woman/03-home` کلیدهای `today` / `cycle` / `care` را می‌خواند، `data_home()` فقط
  `snap` می‌داد؛ نتیجه: **خانه همیشه به کارت «چرخه‌ات را تنظیم کن» می‌افتاد**، حتی با
  چرخهٔ ثبت‌شده. هر سه کلید اضافه شد (به‌علاوهٔ `sos` برای وضعیت توگل و cooldown).
- `woman/07-analysis` → `insights` اضافه شد (`MB_Insights::bundle()` ساخته شده بود و
  هیچ‌وقت صدا زده نمی‌شد).
- `woman/05-day` → `note_private` اضافه شد (یادداشت خصوصی همان روز، فقط مالک).
- `support` → `faq_grouped` و `sla_text` اضافه شد (`MB_FAQ` و `MB_SLA` بی‌مصرف بودند).
- `account` → `user`, `role`, `subscription`, `health`, `share_status` اضافه شد.
- `partner/11-partner` → `snap`, `today_status`, `share_status`, `ack_history` اضافه شد.
  `phase_label` عمداً خالی می‌ماند: باند کیفی مجاز است، نام فاز نه.
- `woman/22-screenings` کلیدهای `name` / `description` / `key` می‌خواند، ولی
  `MB_Screening::listing()` کلیدهای `title` / `intro` / `slug` می‌دهد → عنوان‌ها خالی
  رندر می‌شدند. قالب با قرارداد واقعی هم‌خوان شد.
- پس از اصلاح: **صفر کلید گم‌شده** در همهٔ قالب‌های نگاشته‌شده به یک کنترل‌کننده.

### B) app.js در برابر قالب‌ها
- یتیم‌ها (بدون هندلر): `submit` در `10-health.php`، `24-cycle-editor.php` و
  `support.php` → به `health-save`، `cycle-save` و `ticket-create` تصحیح شد.
  `support-submit` حذف شد.
- فیلدهای فرم پشتیبانی با شناسه‌های هندلر هم‌نام شدند (`mb-ticket-subject` / `mb-ticket-body`).
- `health-save` با `q(...).value` روی فیلد نبوده خطای JS می‌داد و **کل هندلر را می‌کشت**؛
  خواندن ایمن و تبدیل ارقام فارسی اضافه شد.
- `screening-submit` پاسخ‌ها را با کلید `q_0` می‌فرستاد، سرور کلید عددی می‌خواهد →
  هم‌خوان شد؛ پرسش بی‌پاسخ پیش از ارسال هشدار می‌گیرد.
- `data-mb` روی `<select>` یادآورها (`rem-hour` / `rem-channel`) اکشن نیست، فقط
  قلاب انتخاب برای `initReminderInputs` است؛ `onClick` نام ناشناخته را بی‌اثر رد می‌کند
  و `preventDefault` نمی‌زند. عمدی و بی‌خطر.

### C) روتر
- مسیرهای بدون `case`: `cycle-editor`, `health-profile`, `screenings`, `screening`,
  `screening-result`, `faq`. همه اضافه شدند. قالب‌های
  `10-health.php`, `22-screenings.php`, `23-screening-result.php`, `24-cycle-editor.php`
  پیش از این **مرده** بودند.
- `account.php` دکمهٔ «ویرایش پروفایل سلامت» را به `health` (مرکز محتوای سلامت)
  می‌فرستاد، نه `health-profile`. اصلاح شد.
- پس از اصلاح: هر رشتهٔ `route` در قالب‌ها و `app.js` یک `case` دارد.

### D) MB_UI
- همهٔ متدهای صدا زده‌شده وجود دارند و امضا درست است. `pnote()` فقط `warn|good`
  می‌پذیرفت و دو قالب `info` می‌فرستادند → واریانت `info` اضافه شد (با CSS).

### E) آیکن‌ها
- `menu`, `edit`, `logout`, `chevron`, `info` در قالب‌ها صدا زده می‌شدند و در
  `icon_paths()` نبودند؛ `icon()` بی‌صدا به `spark` فال‌بک می‌کرد (منوی خانه و خروج
  حساب، ستارهٔ بی‌ربط نشان می‌دادند). هر پنج مسیر اضافه شد. `assets/icons.svg`
  فقط اسپرایت مرجع طراحی است و منبع رندر نیست.

### F) REST
- پنج اندپوینتی که `app.js` صدا می‌زد و ثبت نشده بودند: `/emergency/sos`,
  `/health-profile/save`, `/cycles/editor/save`, `/screenings/submit`, `/privacy/{key}`.
- هر پنج مورد با `permission_callback` (بانو)، نانس REST + نانس اپ و rate limit ثبت شدند.
- `/privacy/{key}` فقط فهرست سفید `share_pms|share_period|share_support|share_fertility|share_status`
  را می‌پذیرد و به همان منطق `invite/toggle` می‌رسد؛ هر کلید دیگر ۴۰۰.
- `MB_BOOT.nonce`, `MB_BOOT.authNonce`, `data-logged`, `data-mode`/`data-theme` در شل اپ
  حاضر و سالم بودند.
- پس از اصلاح: **صفر اندپوینت بدون `permission_callback`** و صفر فراخوانی بی‌مقصد.

### G) حریم خصوصی
- فهرست سفید همسر دست‌نخورده است: `bbt|mucus|sex|weight_kg|sleep_h|water_cups|
  exercise_min|meds|note_private|mood|pain` در هیچ مسیر همسر ساخته نمی‌شود.
- `today_status` فقط باند کیفی می‌دهد (انرژی و نیاز به حمایت) و **هیچ نام فاز، عدد یا
  نشانه‌ای** ندارد؛ `phase_label` در داشبورد همسر عمداً `—` می‌ماند.
- پیامک اضطراری متن ثابت و بی‌جزئیات دارد و مستقل از توگل‌های اشتراک‌گذاری است.
- `ep_day` هنوز بلوب رمزنگاری‌شدهٔ `note_private` را از پاسخ حذف می‌کند.

### H) Pro
- هر پنج مسیر Pro (`analysis`, `cycle`, `report`, `assistant`, `ttc`) هم توکن امضاشده و
  هم `MB_Subscription::can()` را سروری بررسی می‌کنند؛ `mb_pro_required` سر جایش است.
- هیچ فلگ Pro در پاسخ API نمی‌رود (`unset($data['is_pro'],$data['token'])`).
- اشتراک خانوادگی `couple_id` و نمای خانواده در ادمین بی‌تغییر و سالم.
- `flush_pro_cache()` حالا پس از ذخیرهٔ ویرایشگر چرخه هم اجرا می‌شود.

### I) Checkout
- `verify` idempotent، بازیابی پرداخت pending، قواعد کوپن (کف مبلغ، یک‌بار per کاربر،
  انقضا)، ایمیل فاکتور و کالبک `mb_verify` بازبینی شدند؛ بدون یافتهٔ باز.

### J) Cron
- `MB_Notify`, `MB_Subscription`, `MB_Reminders`, `MB_SLA`, `MB_Prune` همه در
  `activate()` و `maybe_upgrade()` زمان‌بندی می‌شوند؛ تب وضعیت ادمین اجرای بعدی و
  گزارش prune را نشان می‌دهد. بدون یافتهٔ باز.

### K) امنیت (L4)
- `MB_FIELD_KEY` فقط از `wp-config`، با فال‌بک HKDF و notice ادمین؛ هیچ کلیدی در
  options یا پاسخ API نیست. safe-mode و rollback OTP دست‌نخورده.
- **پس از این نسخه، بازسازی manifest لازم است** (فایل‌های `includes/` عوض شده‌اند).
  بامپ نسخه `maybe_upgrade()` را می‌اندازد و خودش `rebuild_manifest()` را صدا می‌زند،
  ولی برای اطمینان دکمهٔ «بازسازی manifest» در تب وضعیت ادمین را هم بزن.

### L) UX / RTL
- `screening-list`, `screening-card`, `bmi-row` و چیپ‌های پرسش‌نامه هیچ CSS نداشتند →
  گرید منظم، ارتفاع لمسی ≥۴۶ و حالت focus اضافه شد.
- دکمهٔ SOS: قاب واحد قرمز، ارتفاع ۴۸.
- `empty-state` با آیکن و راهنما برای فهرست خالی غربالگری‌ها اضافه شد.
- سلب مسئولیت در همهٔ قالب‌های نو حاضر است؛ در هر صفحه دقیقاً یک دکمهٔ طلایی.

### M) کارایی
- `font-display:swap` روی هر چهار وزن Vazirmatn سر جایش است.
- `precache` سرویس‌ورکر (فونت/آیکن/style/app) و `network-first` صفحه‌ها بی‌تغییر؛
  REST، `wp-admin` و کالبک پرداخت کش نمی‌شوند.
- بدون وابستگی جدید، بدون فایل جدید جاوااسکریپت؛ حجم CSS ‎+۱٫۴KB.
- **Lighthouse نیاز به اجرای واقعی دارد و در این ممیزی اندازه‌گیری نشد**؛ در چک‌لیست
  تست دستی مانده است.

## فایل‌های تغییریافته

```
moonbanu.php                       (VERSION 3.1.2)
includes/class-api.php             (روتر، پنج اندپوینت تازه، قرارداد داده)
includes/class-assets.php          (پنج آیکن، واریانت pnote)
includes/class-irregular.php       (حالت نامنظم دستی)
includes/class-jalali.php          (to_jalali_string)
includes/class-screening.php       (save_result با شناسه، last_result)
assets/js/app.js                   (sos, health-save, cycle-save, screening-submit, toggle)
assets/css/style.css               (B3 قاب واحد + استایل‌های ۳٫۱)
templates/woman/03-home.php        (B4)
templates/woman/10-health.php      (اکشن ذخیره)
templates/woman/22-screenings.php  (قرارداد + پرسش‌نامه + empty-state)
templates/woman/24-cycle-editor.php (B2: اکشن ذخیره + پیکر شمسی + تاریخچه)
templates/partner/11-partner.php   (B4)
templates/account.php              (مسیر پروفایل سلامت)
templates/support.php              (فرم تیکت)
README-fa.md                       (قاعدهٔ بستن پرونده)
CHANGELOG-3.1.2-fa.md              (این فایل)
```

**فایل حذف‌شده:** هیچ.

## چک‌لیست تست نهایی

دستی:
1. **B1** — SOS با توگل خاموش → ویرایشگر پروفایل سلامت باز شود. با توگل روشن →
   مودال، پیام موفق، پیامک بی‌جزئیات به مخاطب و همسر. بار دوم → پیام cooldown.
2. **B2** — «تنظیم چرخه» → صفحهٔ N6 باز شود؛ ذخیره → خانه با روز چرخهٔ تازه.
3. **B3** — خانه، تقویم، روز، تحلیل، چرخه، نتیجهٔ غربالگری، پیام همسر، خرید:
   هر کارت فقط یک قاب طلایی گرد.
4. **B4** — هدر خانه و داشبورد همسر: «سلام [نام]»؛ حساب بی‌نام → فقط «سلام».
5. حریم خصوصی همسر: هیچ عدد/نشانه/خلق/درد/یادداشت در هیچ صفحهٔ همسر.
6. خرید خانوادگی توسط همسر → Pro برای هر دو.
7. یادداشت روز قبل در جزئیات روز.
8. `WP_DEBUG=true` و عبور از همهٔ مسیرها → صفر warning.
9. Lighthouse موبایل ≥۸۵ · عرض ۳۹۰px بدون سرریز · صفحهٔ آفلاین · نصب PWA با آیکن ماه‌بانو.
10. prune cron، تایمر SLA، خروج دو نقش + `onLogout` پل، intentهای OTP.
11. **بازسازی manifest** در تب وضعیت ادمین (L4).

curl:
```
curl -i -X POST $REST/emergency/sos -H "X-WP-Nonce: $N" -b cookies.txt
curl -i -X POST $REST/cycles/editor/save -H "X-WP-Nonce: $N" -H 'Content-Type: application/json' \
  -b cookies.txt -d '{"cycle_length":29,"period_length":5,"last_period_jalali":"1404/07/05"}'
curl -i -X POST $REST/health-profile/save -H "X-WP-Nonce: $N" -b cookies.txt -d '{"emergency_alert":true}'
curl -i -X POST $REST/screenings/submit -H "X-WP-Nonce: $N" -b cookies.txt \
  -d '{"tool":"phq9","answers":{"0":0,"1":1,"2":0,"3":1,"4":0,"5":0,"6":0,"7":0,"8":0}}'
curl -i -X POST $REST/privacy/note_private -H "X-WP-Nonce: $N" -b cookies.txt   # باید ۴۰۰ بدهد
curl -i $REST/partner/home -b partner_cookies.txt | grep -Ei 'bbt|mucus|pain|mood|note'  # باید خالی باشد
```
