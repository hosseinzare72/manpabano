# چک‌لیست اتصال ماه‌بانو به ملی‌پیامک و زیبال

## ۱) اطلاعاتی که باید آماده باشد

### ملی‌پیامک
- نام کاربری پنل
- API Key یا رمز عبور وب‌سرویس
- شماره فرستنده اختصاصی/خدماتی
- فعال بودن وب‌سرویس روی پنل. طبق مستندات ملی‌پیامک، برای برخی امکانات پنل پایه کافی نیست.
- برای OTP و پیام‌های تراکنشی، پترن خدماتی و `bodyId` تأییدشده را از پنل بگیرید. نسخه فعلی افزونه ارسال پیامک دعوت‌نامه را پیاده کرده و OTP ورود را ایجاد نمی‌کند.

### زیبال
- کد مرچنت درگاه
- دامنه واقعی و HTTPS
- دسترسی خروجی سرور به `gateway.zibal.ir` روی HTTPS
- callback افزونه به‌صورت خودکار ساخته می‌شود و قالب آن `https://DOMAIN/?mb_verify=REF` است؛ این URL را دستی با trackId ثابت وارد نکنید.

## ۲) تنظیمات وردپرس

- نسخهٔ ۱.۱.۲ در نصب/ارتقا یک برگهٔ اپ با مسیر `/moonbanu/` می‌سازد، مگر اینکه برگهٔ اپ دیگری در تنظیمات انتخاب شده باشد.
- PWA فقط در scope همان برگه ثبت می‌شود؛ بنابراین با PWA دیگری که روی ریشهٔ دامنه نصب است تداخل scope ندارد. نصب را از HTTPS و از همان برگه انجام دهید.
- در تب «محتوا» رنگ‌ها، تصویر پس‌زمینه، لوگو، متن‌های قابل جایگزینی و راهنماهای سلامت قابل مدیریت‌اند.

1. افزونه را نصب و فعال کنید.
2. یک برگه با شورت‌کد `[moonbanu_app]` بسازید و در «ماه‌بانو ← تنظیمات ← عمومی» انتخاب کنید.
3. در «اشتراک و درگاه»، درگاه فعال را روی «زیبال» بگذارید، کد مرچنت را وارد کنید و واحد را روی «ریال» بگذارید. قیمت‌ها داخل پنل به تومان‌اند و افزونه آن‌ها را ×۱۰ به ریال تبدیل می‌کند. پلن‌های پیش‌فرض: یک‌ماهه ۲۵۰٬۰۰۰، سه‌ماهه ۵۰۰٬۰۰۰ و شش‌ماهه ۱٬۰۰۰٬۰۰۰ تومان.
4. در «پیامک»، سرویس «ملی‌پیامک» را انتخاب کنید و نام کاربری، API Key/رمز عبور و شماره فرستنده را وارد کنید.
5. HTTPS، REST API و WP-Cron را فعال کنید. برای سایت پرترافیک، cron واقعی سرور بهتر از WP-Cron است.
6. یک‌بار پیوندهای یکتا را ذخیره کنید.

## ۳) جریان پرداخت زیبال

- `POST https://gateway.zibal.ir/v1/request`
- بدنه شامل `merchant`, `amount`, `callbackUrl`, `description`, `orderId` و در صورت وجود `mobile` است.
- کاربر به `https://gateway.zibal.ir/start/{trackId}` منتقل می‌شود.
- زیبال به callback برمی‌گردد و افزونه `trackId`, `success` و `orderId` را با سفارش تطبیق می‌دهد.
- افزونه سپس `POST https://gateway.zibal.ir/v1/verify` را اجرا می‌کند.
- فقط نتیجه موفق ۱۰۰ یا نتیجه «قبلاً تأیید شده» ۲۰۱ پذیرفته می‌شود؛ مبلغ پرداخت هم با مبلغ سفارش مقایسه می‌شود.
- بازبینی شبانه از `v1/inquiry` استفاده می‌کند و خطای شبکه باعث لغو اشتراک نمی‌شود.

## ۴) جریان پیامک ملی‌پیامک

افزونه از سمت سرور به این سرویس وصل می‌شود:

`POST https://rest.payamak-panel.com/api/SendSMS/SendSMS`

فیلدهای فرم: `username`, `password`, `to`, `from`, `text`.

کلیدها هرگز به HTML یا JavaScript اپ فرستاده نمی‌شوند. پاسخ HTTP 200 به‌تنهایی موفقیت نیست؛ افزونه کدهای خطای عددی ملی‌پیامک را هم بررسی می‌کند. پیامک دعوت شامل لینک است و ممکن است روی خط تبلیغاتی یا تنظیمات فیلتر پیامک رد شود؛ برای پیامک‌های حساس از خط خدماتی/پترن تأییدشده استفاده کنید.

## ۵) API فعلی برای اپ

پایه:

`https://DOMAIN/wp-json/moonbanu/v1`

احراز هویت نسخه فعلی، نشست وردپرس + کوکی + هدر `X-WP-Nonce` است. WebView/PWA همین مدل را بدون کار اضافه پشتیبانی می‌کند؛ اپ Native به JWT/OAuth و CORS نیاز دارد که در این نسخه پیاده نشده است.

### عمومیِ کاربر واردشده

- `GET /view?route=splash|onboarding|home|calendar|day|log|analysis|cycle|health|connect|partner|checkout`
- `GET /today`
- `GET /calendar?m=YYYY-MM`
- `GET /day?d=YYYY-MM-DD`
- `GET /analysis`
- `GET /cycle-map`
- `GET /notifications`
- `GET /subscription/status`
- `POST /log` با `date`, `mood`, `bleeding`, `pain`, `symptoms[]`, `note`
- `POST /profile` با `last_period_jalali`, `cycle_len`, `period_len`, `mobile`
- `POST /notifications/read`
- `POST /support/send` با `text`
- `GET /articles?cat=...&q=...`
- `POST /articles/save` با `id`
- `POST /question` با `body`

### دعوت و همراه

- `POST /invite` با `channel=email|sms`, `target` و توگل‌های اشتراک‌گذاری
- `GET /invite/status`
- `POST /invite/toggle` با `key` و `value`
- `POST /invite/revoke`
- `POST /invite/accept` با `token`, `name`, `email`, `password`
- `GET /partner/home?token=...` یا نشست همراه
- `POST /partner/ack`
- `POST /partner/snooze`
- `POST /partner/toggle`

### پرداخت

- `POST /checkout/create` با `coupon`، پاسخ شامل `redirect` است.
- `GET /checkout/verify` برای تازه‌سازی وضعیت پس از بازگشت به اپ.
- callback بانکی، API اپ نیست: `GET /?mb_verify=REF`.

## ۶) تست پذیرش قبل از انتشار

- درخواست پرداخت با merchant آزمایشی، برگشت موفق، فعال شدن اشتراک و ثبت `refNumber`.
- callback تکراری نباید ۳۰ روز را دوباره اضافه کند.
- تغییر مبلغ یا trackId باید رد شود.
- پرداخت ناموفق باید اشتراک را فعال نکند.
- پیامک موفق، موجودی ناکافی، خطای API و شماره نامعتبر را تست کنید.
- دسترسی به کلیدها از View Source، REST response و JavaScript ممکن نباشد.
- کاربر رایگان نباید `/analysis` و `/cycle-map` را ببیند.
