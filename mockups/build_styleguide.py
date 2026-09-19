# -*- coding: utf-8 -*-
"""Build a single self-contained design-handoff file for ماه‌بانو."""
import base64, pathlib, sys

ROOT = pathlib.Path("/home/user/mockups")
sys.path.insert(0, str(ROOT))
from components import ICONS, icon, ring_svg, logo  # noqa: E402
from jalali import Cycle  # noqa: E402

C = Cycle()
OUT = pathlib.Path("/home/user/screens/moonbanu-style-guide.html")

# ------------------------------------------------------------------ fonts
FONTS = ["Regular", "Medium", "SemiBold", "Bold", "ExtraBold"]
WEIGHT = {"Regular": 400, "Medium": 500, "SemiBold": 600, "Bold": 700, "ExtraBold": 800}
font_css = []
for f in FONTS:
    b64 = base64.b64encode((ROOT / f"fonts/Vazirmatn-{f}.woff2").read_bytes()).decode()
    font_css.append(f"@font-face{{font-family:'Vazirmatn';src:url(data:font/woff2;base64,{b64}) format('woff2');"
                    f"font-weight:{WEIGHT[f]};font-display:block}}")
app_css = (ROOT / "style.css").read_text(encoding="utf-8")
# strip original @font-face lines (embedded above as base64) and comment header
app_css = "\n".join(l for l in app_css.splitlines() if not l.strip().startswith("@font-face"))

GUIDE_CSS = """
body{line-height:1.9;padding:0 0 90px;background:
  radial-gradient(100% 55% at 82% -10%, rgba(120,66,170,.30) 0%, rgba(10,6,16,0) 60%),
  radial-gradient(90% 45% at 5% 4%, rgba(206,60,105,.16) 0%, rgba(10,6,16,0) 55%), #07040C}
.gn{background:#05030A}
.wrap{max-width:1120px;margin:0 auto;padding:0 26px}
.hero{padding:54px 0 34px;border-bottom:1px solid rgba(217,180,91,.20);
  background:radial-gradient(90% 130% at 80% -30%, rgba(120,66,170,.42) 0%, rgba(9,5,14,0) 60%)}
.hero .kicker{color:#D9B45B;font-size:12px;letter-spacing:3px;font-weight:600;margin-bottom:12px}
.hero h1{font-size:35px;font-weight:800;background:linear-gradient(135deg,#FAEFC8,#D9B45B 55%,#9C7228);
  -webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:12px}
.hero p{color:#B9AECB;font-size:13.5px;max-width:760px;line-height:2.1}
.gpills{display:flex;gap:9px;flex-wrap:wrap;margin-top:18px}
.gpill{border:1px solid rgba(255,255,255,.13);background:rgba(255,255,255,.05);border-radius:999px;padding:6px 14px;font-size:12px;color:#E8E0F2}
h2.sec{font-size:21px;font-weight:800;margin:46px 0 4px;padding-right:14px;border-right:3px solid #D9B45B}
h3.sub{font-size:15px;font-weight:700;margin:22px 0 4px;color:#F7F1E4}
p.lead{color:#B9AECB;font-size:13px;margin-bottom:6px}
table.g{width:100%;border-collapse:collapse;margin:12px 0;font-size:12.6px}
table.g th,table.g td{border:1px solid rgba(255,255,255,.09);padding:8px 11px;text-align:right;vertical-align:top}
table.g th{background:rgba(217,180,91,.10);color:#FAEFC8;font-weight:700;white-space:nowrap}
table.g td{background:rgba(255,255,255,.022);color:#C7BEDA;line-height:1.85}
table.g td.c{font-family:monospace;direction:ltr;text-align:left;color:#F3E3B4;white-space:nowrap;font-size:12px}
table.g td.m{direction:rtl;text-align:right;color:#EDE4F6;font-size:12.2px}
table.g td.m b{color:#FAEFC8;font-weight:700}
.sw-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin:14px 0}
.swd{border:1px solid rgba(255,255,255,.12);border-radius:14px;overflow:hidden;background:rgba(255,255,255,.03)}
.swd i{display:block;height:56px}
.swd div{padding:8px 10px;font-size:11px;line-height:1.7}
.swd b{display:block;font-family:monospace;direction:ltr;text-align:left;color:#F3E3B4;font-size:11.5px}
.swd span{color:#9C93AE;font-size:10.5px}
.lab{font-size:12px;color:#9C93AE;margin:10px 0 4px;font-weight:600}
.demo{border-radius:22px;padding:18px;border:1px solid rgba(255,255,255,.09);margin:12px 0;
  background:
    radial-gradient(115% 72% at 78% -8%, rgba(120,66,170,.42) 0%, rgba(58,28,86,.24) 40%, rgba(10,6,16,0) 70%),
    radial-gradient(95% 55% at 8% 6%, rgba(206,60,105,.20) 0%, rgba(10,6,16,0) 60%),
    linear-gradient(180deg,#150E22 0%,#0B0713 60%,#070410 100%);}
.demo.p{background:
    radial-gradient(115% 70% at 20% -6%, rgba(46,86,140,.42) 0%, rgba(16,32,54,.24) 42%, rgba(8,10,18,0) 70%),
    radial-gradient(90% 55% at 92% 10%, rgba(217,180,91,.14) 0%, rgba(8,10,18,0) 60%),
    linear-gradient(180deg,#0E1729 0%,#0A1120 60%,#070A12 100%);}
.demo .cap{font-size:10.5px;color:#8D82A6;margin-top:10px}
.rowdemo{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.codeblock{background:#0A0611;border:1px solid rgba(255,255,255,.10);border-radius:16px;padding:14px 16px;margin:12px 0;
  direction:ltr;text-align:left;font-family:ui-monospace,Menlo,monospace;font-size:12px;color:#E9DFF3;overflow-x:auto;white-space:pre;line-height:1.85}
.codeblock .k{color:#D9B45B}.codeblock .v{color:#9EE6D8}.codeblock .c{color:#7C7191}
.icon-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(96px,1fr));gap:9px;margin:14px 0}
.icon-cell{border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.035);border-radius:13px;
  padding:10px 6px;display:flex;flex-direction:column;align-items:center;gap:6px;color:#E6DDF2}
.icon-cell span{font-size:9.5px;color:#8D82A6;font-family:monospace;direction:ltr}
.note{border:1px solid rgba(217,180,91,.30);background:linear-gradient(135deg,rgba(217,180,91,.11),rgba(255,255,255,.02));
  border-radius:16px;padding:13px 16px;margin:12px 0;font-size:12.5px;color:#E9E1F2;line-height:2}
.note.rose{border-color:rgba(234,63,99,.30);background:linear-gradient(135deg,rgba(234,63,99,.11),rgba(255,255,255,.02))}
.note.teal{border-color:rgba(18,165,148,.30);background:linear-gradient(135deg,rgba(18,165,148,.11),rgba(255,255,255,.02))}
.note b{color:#FAEFC8}
footer.g{color:#7C7191;font-size:12px;text-align:center;margin-top:48px;line-height:2.2}
.tabstatic{position:relative;height:88px;border-radius:0 0 26px 26px;overflow:hidden}
.phoneframe{width:390px;border-radius:34px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.55);
  border:1px solid rgba(255,255,255,.10);background:#070410;position:relative}
.two{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:820px){.two{grid-template-columns:1fr}}
"""

# ------------------------------------------------------------------ data
PHASE_ROWS = [
    # phase, css var, hex, dot glow, chip b/border, cell gradient, meaning
    ("قاعدگی", "--period", "#EA3F63", "rgba(234,63,99,.80)", "rgba(234,63,99,.14) / .34",
     "rgba(234,63,99,.30) → rgba(234,63,99,.14)", "خونریزی؛ روزهای ۱ تا ۵ (پیش‌فرض)"),
    ("پنجرهٔ باروری", "--fertile", "#12A594", "rgba(18,165,148,.80)", "rgba(18,165,148,.14) / .34",
     "rgba(18,165,148,.24) → rgba(18,165,148,.10)", "روزهای ۹ تا ۱۵؛ احتمال بالای بارداری"),
    ("تخمک‌گذاری", "--ovul", "#8B6BF5", "rgba(139,107,245,.80)", "rgba(139,107,245,.16) / .38",
     "rgba(139,107,245,.34) → rgba(139,107,245,.14)", "روز ۱۴؛ اوج احتمال باروری"),
    ("PMS", "--pms", "#EE9A2E", "rgba(238,154,46,.80)", "rgba(238,154,46,.15) / .36",
     "rgba(238,154,46,.26) → rgba(238,154,46,.10)", "۷ روز آخر چرخه؛ نوسان خلق و خستگی"),
    ("لوتئال", "--luteal", "#B07C2A", "—", "— (در چرخه با dot نمایش داده می‌شود)",
     "—", "پس از تخمک‌گذاری تا شروع PMS"),
    ("فولیکولی", "--foll", "#3E86E8", "—", "—", "—", "پس از قاعدگی تا پنجرهٔ باروری"),
    ("روزهای عادی", "--norm", "#6E7A93", "—", "—", "rgba(255,255,255,.035) / .05", "بدون نشانهٔ ویژه"),
]

BUTTONS = [
    # name, class, size, colors, where used
    ("دکمهٔ اصلی طلایی", "btn gold", "46px • r16 • 13.5px/700", "گرادیان #FDEFC4→#E7C877→#C79A3E→#9C7228 • متن #2A1D04 • سایه 0 12px 26px rgba(196,150,60,.30) + لبهٔ داخلی سفید", "«ثبت نشانهٔ امروز»، «شروع کنیم»، «ذخیره در دفترچهٔ سلامت»، «ارسال دعوت‌نامه»، «فهمیدم، حواسم هست»"),
    ("دکمهٔ ثانویه (شیشه‌ای)", "btn ghost", "46px • r16 • 13.5px/700", "پس‌زمینه rgba(255,255,255,.055) • حاشیه rgba(255,255,255,.105) • متن #F8F3F9", "«راهنمای امروز»، «خواندن راهنمای کامل…»، «پیامک»"),
    ("دکمهٔ تیره", "btn dark", "46px • r16", "پس‌زمینه rgba(0,0,0,.34) • حاشیه rgba(255,255,255,.08)", "روی کارت‌های روشن/گرادیانی و مودال‌ها"),
    ("دکمهٔ کوچک", "btn sm", "40px • r13 • 12.5px", "همان طرح‌های gold / ghost", "«مطالعهٔ راهنما»، «ذخیره»، «آمدن این روزها را یادآوری کن»، «یادآوری فردا»"),
    ("دکمهٔ خیلی کوچک", "btn xs", "34px • r11 • 11.5px", "همان طرح‌ها", "«ارسال» (پیام حمایت)، «کپی» (لینک دعوت)"),
    ("دکمهٔ تمام‌عرض", "btn wide", "عرض ۱۰۰٪", "ترکیب با هر طرح", "دکمه‌های پایانی صفحه‌ها"),
    ("دکمهٔ شناور میانی", "fab", "56×56 • r20", "گرادیان #FDEFC4→#DDBA63 45%→#A97C31 • آیکون + با ضخامت 2.4 و رنگ #2A1D04 • سایه 0 14px 30px rgba(190,145,55,.42)", "میان تب‌بار — ثبت سریع نشانه"),
    ("دکمهٔ آیکونی گرد", "ic n (32px)", "32×32 • r11", "پس‌زمینه rgba(255,255,255,.06) • آیکون #E6DDF2", "فلش ماه قبل/بعد تقویم، بازگشت در جزئیات روز"),
]

CHIPS = [
    ("chip (پیش‌فرض)", "height 28px • r999 • 11.5px/600", "پس‌زمینه rgba(255,255,255,.06) • حاشیه stroke • متن #F8F3F9", "برچسب‌های عمومی و لِجند"),
    ("chip period", "همان ابعاد", "bg rgba(234,63,99,.14) • border rgba(234,63,99,.34) • متن #FFD7E0", "هشدار قاعدگی، وضعیت خونریزی"),
    ("chip fertile", "همان ابعاد", "bg rgba(18,165,148,.14) • border .34 • متن #CBF3EC", "پنجرهٔ باروری، پیام‌های سلامت"),
    ("chip pms", "همان ابعاد", "bg rgba(238,154,46,.15) • border .36 • متن #FFE6C2", "وضعیت PMS، «متوسط» درد"),
    ("chip ovul", "همان ابعاد", "bg rgba(139,107,245,.16) • border .38 • متن #E2DAFF", "روز تخمک‌گذاری"),
    ("chip gold", "همان ابعاد", "گرادیان طلایی .20/.10 • border rgba(217,180,91,.42) • متن #FAEFC8", "ویژه/Pro، دقت مدل، لینک دعوت، «تغییر»"),
    ("chip dark", "همان ابعاد", "bg rgba(0,0,0,.28)", "تراشه‌های داخل کارت‌های رنگی (تا قاعدگی، نیاز اصلی…)"),
]

TYPESCALE = [
    ("برند (اسپلش)", "38px", "800", "1", "متن گرادیان طلایی", "«ماه‌بانو»"),
    ("hero-num", "38–44px", "800", "1", "طلایی روی حلقه", "شمارش روز تا قاعدگی"),
    ("h1", "19–22px", "800", "1.45", "#F8F3F9", "عنوان صفحه‌ها"),
    ("h2", "14–16px", "700", "1.6", "#F8F3F9", "عنوان کارت‌ها"),
    ("h3", "12–14px", "600", "1.7", "#F8F3F9", "عنوان ردیف‌ها و کاشی‌ها"),
    ("body", "11–12.5px", "400", "1.95", "#ABA0BC", "متن توضیحی"),
    ("small", "11px", "500", "1.8", "#7C7191", "زیرعنوان، واحدها"),
    ("tiny", "9–10px", "500", "1.8", "#7C7191", "یادداشت‌های ریز، برچسب‌ها"),
    ("chip / tag", "9.5–11.5px", "600", "1", "#متغیر", "چیپ‌ها و برچسب‌ها"),
    ("button", "11.5–13.5px", "700", "1", "#2A1D04 / #F8F3F9", "متن دکمه‌ها"),
    ("kpi", "16–19px", "800", "1.2", "#F8F3F9", "اعداد کلیدی (میانگین چرخه…)"),
]

SCREEN_ACTIONS = [
    ("۰۱ اسپلش", [
        ("چیپ «دقت پیش‌بینی ۹۶٪»", "chip gold + آیکون sparkle", "اعتمادسازی؛ غیرفعال"),
        ("چیپ «حریم خصوصی کامل»", "chip + آیکون lock", "تأکید بر محرمانگی"),
        ("حلقهٔ فاز پس‌زمینه", "ring_svg 300px", "دکوراسیون برند"),
    ]),
    ("۰۲ آنبوردینگ", [
        ("«رد کردن»", "متن small، گوشهٔ بالا-چپ", "رفتن مستقیم به صفحهٔ اصلی"),
        ("نقاط صفحهٔ ۳ گانه", "سه نقطهٔ ۵×۶ پیکسل؛ فعال طلایی، غیرفعال سفیدِ محو", "نمایش پیشرفت"),
        ("«شروع کنیم»", "btn gold wide + آیکون chevL", "ورود به تنظیمات اولیه"),
    ]),
    ("۰۳ صفحهٔ امروز", [
        ("زنگ اعلان + بج «۳»", "آیکون bell + badge (گرادیان #FF6B8B→#EA3F63)", "مرکز اعلان‌ها"),
        ("چیپ‌های فاز/روز/باروری", "chip و chip fertile", "اطلاع وضعیت لحظه‌ای"),
        ("«ثبت نشانهٔ امروز»", "btn gold grow + آیکون plus", "باز کردن فرم ثبت (صفحهٔ ۰۶)"),
        ("«راهنمای امروز»", "btn ghost با عرض ثابت ۱۱۸ پیکسل", "صفحهٔ جزئیات روز (۰۵)"),
        ("«ارسال» پیام حمایت", "btn xs ghost با حاشیهٔ طلایی", "ارسال پیام آماده به همسر"),
        ("۳ کارت راهنمای روز", "کارت‌های شیشه‌ای با آیکون رنگی", "نمایش توصیهٔ فاز: مکمل، پیاده‌روی، کافئین"),
    ]),
    ("۰۴ تقویم", [
        ("فلش ماه قبل / بعد", "ic n 32px + chev/chevL", "جابه‌جایی ماه شمسی"),
        ("فیلتر و جست‌وجو", "آیکون‌های filter و search", "فیلتر فازها / جست‌وجوی تاریخ"),
        ("سلول روز", "cell + period/fertile/ovul/pms/today/pred", "لمس → صفحهٔ جزئیات روز"),
        ("لِجند رنگ‌ها", "مربع‌های رنگی ۱۰ پیکسلی", "راهنمای رنگ فازها"),
        ("کارت «چرخهٔ بعدی»", "chip period «۸ روز دیگر»", "اطلاع تاریخ قاعدگی بعد"),
        ("بنر پیش‌بینی هوشمند", "کارت شیشه‌ای + آیکون sparkle طلایی", "نمایش دقت مدل"),
    ]),
    ("۰۵ جزئیات روز", [
        ("بازگشت", "ic n 34px + chev", "بازگشت به تقویم"),
        ("چیپ‌های وضعیت", "chip dark + آیکون time / drop / heart", "تا قاعدگی، خونریزی، احتمال بارداری"),
        ("۳ ردیف مراقبت", "lrow با شِوران chev", "تغذیه / حرکت / هشدار پیشگیری"),
    ]),
    ("۰۶ ثبت نشانه‌ها", [
        ("بج «۶ روز پیاپی ثبت»", "chip gold + sparkle", "گیمیفیکیشن سبک"),
        ("۵ ایموجی خلق", "دایرهٔ ۴۴ پیکسلی (تک‌انتخابی) با حلقهٔ طلایی در حالت فعال", "ثبت حال روزانه"),
        ("۵ چیپ خونریزی", "چیپ سرخابی برای گزینهٔ فعال", "بدون خونریزی، لکه‌بینی، سبک، متوسط، سنگین"),
        ("۸ کاشی نشانه", "کاشی ۴۲ پیکسلی (چندانتخابی) با حالت فعالِ طلایی", "گرفتگی، سردرد، نفخ، تهوع، بی‌خوابی…"),
        ("۵ دکمهٔ شدت درد", "دکمه‌های ۳۰ پیکسلی؛ سطح فعال سرخابی", "مقیاس درد از ۱ تا ۵"),
        ("ردیف یادداشت خصوصی", "lrow + چِوران", "یادداشت رمزنگاری‌شدهٔ شخصی"),
        ("«ذخیره در دفترچهٔ سلامت»", "btn gold wide", "ذخیرهٔ لاگ روز"),
    ]),
    ("۰۷ تحلیل هوشمند", [
        ("چیپ «نسخهٔ پیشرفته»", "chip gold + sparkle", "اشاره به قابلیت Pro"),
        ("نمودار ۶ چرخه", "میله‌های گرادیان طلایی ۱۳px", "روند طول چرخه"),
        ("کارت‌های الگو", "lrow با آیکون‌های m / p", "الگوهای شخصی و دقت مدل"),
    ]),
    ("۰۸ چرخهٔ من", [
        ("حلقهٔ ۲۸ روزه", "حلقهٔ فاز با نشانگر روز", "نمایش بصری فازها"),
        ("۶ کاشی فاز", "گرید ۲ ستونه با dot رنگی", "تاریخ دقیق هر فاز"),
        ("یادداشت هشدار", "pnote warn + آیکون alert", "هشدار عدم اتکای صرف به تقویم"),
    ]),
    ("۰۹ مرکز سلامت", [
        ("آیکون جست‌وجو", "ic n 34px", "جست‌وجو در آموزش‌ها"),
        ("«مطالعهٔ راهنما»", "btn gold sm grow", "باز کردن مقالهٔ ویژه"),
        ("«ذخیره»", "btn ghost sm (۹۲px)", "ذخیره در فهرست خواندنی‌ها"),
        ("۶ کاشی دسته‌بندی", "گرید ۳ ستونه با آیکون رنگی", "قاعدگی، پیشگیری، تغذیه، روان، هشدار، بارداری"),
        ("ردیف «پرسش از متخصص»", "lrow + شِوران", "چت محرمانه با متخصص زنان"),
    ]),
    ("۱۰ اتصال همسر", [
        ("QR + لینک دعوت", "کد QR سفید ۱۰۴ پیکسلی + چیپ طلایی", "اتصال با اسکن"),
        ("«کپی»", "btn xs ghost", "کپی لینک دعوت"),
        ("«ارسال دعوت‌نامه»", "btn gold sm grow + share", "اشتراک‌گذاری لینک"),
        ("«پیامک»", "btn ghost sm + phone", "ارسال پیامک دعوت"),
        ("۴ کلید توگل", "کلید کشویی؛ روشن با گرادیان طلایی", "PMS، شروع/پایان قاعدگی، کارهای حمایتی، باروری (پیش‌فرض خاموش)"),
    ]),
    ("۱۱ خانهٔ همسر", [
        ("زنگ اعلان + بج «۲»", "badge", "اعلان‌های همسر"),
        ("«آمدن این روزها را یادآوری کن»", "btn gold sm wide", "یادآور شخصی همسر"),
        ("نوار ۷ روز آینده", "۷ سلول ۳۶×۵۲px رنگی + «امروز»", "نمای کلی بدون جزئیات خصوصی"),
        ("کارت «شروع قاعدگی بعدی»", "chip gold «۸ روز دیگر»", "اطلاع زمان‌بندی"),
        ("۲ توگل کارهای حمایتی", "کلید کشویی روشن", "پذیرش یا رد پیشنهادها"),
    ]),
    ("۱۲ یادآور مهربانی", [
        ("چیپ وضعیت", "chip pms «PMS از فردا»", "خلاصهٔ وضعیت"),
        ("«فهمیدم، حواسم هست»", "btn gold sm grow + check", "تأیید دریافت و تعهد"),
        ("«یادآوری فردا»", "btn ghost sm + time", "به تعویق انداختن"),
        ("۲ کارت اقدام", "کارت شیشه‌ای با آیکون فنجان و هدیه", "«آرام باش» و «کمک کن»"),
        ("«خواندن راهنمای کامل…»", "btn ghost wide", "رفتن به صفحهٔ ۱۳"),
    ]),
    ("۱۳ حالت آرامش", [
        ("۵ کاشی کار مؤثر", "ic 34px رنگی + برچسب", "چای گرم، حمام گرم، ماساژ، غذای سبک، خواب"),
        ("چیپ «۲ از ۵ انجام شد»", "chip dark + check", "پیشرفت چک‌لیست"),
        ("۳ جملهٔ «بگو» و ۳ جملهٔ «نگو»", "ic کوچک ۲۴px سبز/سرخابی", "راهنمای گفت‌وگو"),
        ("۴ چیپ کمک عملی", "chip با آیکون", "خرید دارو، تهیهٔ میوه، پیاده‌روی، نیم ساعت سکوت"),
    ]),
]

TABS = [("خانه", "home"), ("تقویم", "cal"), ("ثبت", "plus (FAB)"), ("تحلیل", "chart"), ("من", "user")]

DID = [
    ("۱. موتور تقویم شمسی", "<code>mockups/jalali.py</code> — تبدیل دقیق شمسی↔میلادی بر پایهٔ شمارهٔ روز مطلق (JDN، مبدأ ۱۹۴۸۳۲۰)، محاسبهٔ روز هفته، افزودن/کاهش روز، ارقام فارسی. با ۵۰۰۰ تاریخ نمونه در برابر <code>datetime</code> تست و تأیید شد."),
    ("۲. موتور فاز چرخه", "کلاس <code>Cycle</code> — تعیین فاز هر تاریخ: قاعدگی، فولیکولی، پنجرهٔ باروری، تخمک‌گذاری، لوتئال، PMS؛ به‌همراه توابع <code>period_range / fertile_range / pms_range / next_period / day_index</code>."),
    ("۳. سیستم طراحی Noir Bloom", "<code>mockups/style.css</code> — توکن‌های رنگ، تایپوگرافی وزیرمتن، کارت‌های شیشه‌ای، گرادیان طلایی، حلقهٔ فاز، تقویم، تب‌بار و ۲۰+ کامپوننت."),
    ("۴. کتابخانهٔ آیکون", "<code>mockups/components.py</code> — ۴۵ آیکون خطی SVG (viewBox 24، stroke 1.7–1.8، سرگرد) به‌علاوهٔ لوگوی گل/ماه، حلقهٔ فاز و تولید QR."),
    ("۵. کامپوننت‌های مشترک", "نوار وضعیت، تب‌بار با دکمهٔ شناور، هدر، سکشن، لِجند، بج، توگل، اسلایدر، کارت‌های هشدار (pnote) و کاشی‌های نشانه."),
    ("۶. سیزده صفحهٔ کامل", "۹ صفحهٔ بخش بانوان + ۴ صفحهٔ بخش همسر («همراهِ ماه») — همه با تاریخ‌های واقعی و منسجم شمسی."),
    ("۷. رندر خودکار PNG", "<code>mockups/build.py</code> — رندر ۱۳ صفحه با Playwright در ابعاد ۳۹۰×۸۴۴ و مقیاس ۲x، به‌همراه گزارش خودکار «سرریز محتوا» برای هر صفحه (همه صفر شدند)."),
    ("۸. گالری ارائه و تصویر کلی", "<code>mockups/build_presentation.py</code> — تصویر کلی <code>screenshots/00-overview.png</code> و گالری خودکفا <code>screens/moonbanu-ui-design.html</code>."),
    ("۹. نقشهٔ راه محصول", "<code>mockups/build_blueprint.py</code> — سند <code>screens/moonbanu-blueprint.html</code>: چشم‌انداز، توکن‌ها، موتور چرخه، اسکیمای دیتابیس، معماری فنی، نقشهٔ راه ۶ مرحله‌ای M0–M5 و نکات حقوقی."),
    ("۱۰. این راهنما + README", "سند تحویل طراحی (همین فایل) و <code>README.md</code> با نقشهٔ فایل‌ها و دستورهای بازتولید."),
]

REPRO = """cd /home/user/mockups
python3 build.py                 # رندر همهٔ اسکرین‌شات‌ها → ../screenshots
python3 build.py 03-today-home   # رندر فقط یک صفحه (نام‌ها مثل فهرست بالا)
python3 build_presentation.py    # ساخت گالری + تصویر کلی
python3 build_blueprint.py       # ساخت نقشهٔ راه محصول
python3 build_styleguide.py      # ساخت همین فایل راهنما"""

TOKENS_JSON = """{
  "color": {
    "bg":    { "body": "#05030A", "0": "#07040C", "1": "#150E22", "2": "#241537" },
    "text":  { "primary": "#F8F3F9", "muted": "#ABA0BC", "muted2": "#7C7191", "onGold": "#2A1D04" },
    "gold":  { "1": "#FAEFC8", "2": "#D9B45B", "3": "#9C7228" },
    "phase": {
      "period": "#EA3F63", "fertile": "#12A594", "ovulation": "#8B6BF5",
      "pms": "#EE9A2E", "luteal": "#B07C2A", "follicular": "#3E86E8", "normal": "#6E7A93"
    },
    "status": { "success": "#71E0D1", "info": "#93BEFF", "warning": "#FFC271",
                "danger": "#FF9FB4", "onDanger": "#FFD7E0", "onFertile": "#CBF3EC" },
    "surface": { "glass": "rgba(255,255,255,.058)", "stroke": "rgba(255,255,255,.105)" }
  },
  "radius": { "card": 24, "cardSoft": 20, "button": 16, "cell": 14, "chip": 999, "fab": 20 },
  "shadow": {
    "card": "0 18px 44px rgba(0,0,0,.45)",
    "buttonGold": "0 12px 26px rgba(196,150,60,.30)",
    "fab": "0 14px 30px rgba(190,145,55,.42)"
  },
  "font": { "family": "Vazirmatn", "emojiFallback": "Noto Color Emoji",
            "weights": [400, 500, 600, 700, 800] },
  "cycle": { "cycleLen": 28, "periodLen": 5, "lutealLen": 14,
             "ovulationDay": "cycleLen - lutealLen", "fertileWindow": [9, 15], "pmsDays": 7 }
}"""

TOKENS_CSS = """:root{
  --bg0:#07040C; --bg1:#150E22; --bg2:#241537;
  --txt:#F8F3F9; --muted:#ABA0BC; --muted2:#7C7191; --on-gold:#2A1D04;
  --gold1:#FAEFC8; --gold2:#D9B45B; --gold3:#9C7228;
  --period:#EA3F63; --fertile:#12A594; --ovul:#8B6BF5; --pms:#EE9A2E;
  --luteal:#B07C2A; --foll:#3E86E8; --norm:#6E7A93;
  --glass:rgba(255,255,255,.058); --stroke:rgba(255,255,255,.105);
  --r-card:24px; --r-btn:16px; --r-chip:999px;
  --shadow:0 18px 44px rgba(0,0,0,.45);
  --grad-gold:linear-gradient(135deg,#FDEFC4 0%,#E7C877 38%,#C79A3E 78%,#9C7228 100%);
  --grad-gold-text:linear-gradient(135deg,#FAEFC8,#D9B45B 55%,#9C7228);
}"""


def swatches(items):
    return '<div class="sw-grid">' + "".join(
        f'''<div class="swd"><i style="{s}"></i><div><b>{t}</b><span>{d}</span></div></div>'''
        for s, t, d in items) + '</div>' 


def build():
    # live component samples -------------------------------------------------
    btns = f'''<div class="demo">
      <div class="lab">دکمهٔ اصلی (btn gold) — دکمهٔ ثانویه (btn ghost) — دکمهٔ تیره (btn dark)</div>
      <div class="rowdemo">
        <div class="btn gold" style="width:190px">{icon('plus',17,'','#2A1D04',2.2)} ثبت نشانهٔ امروز</div>
        <div class="btn ghost" style="width:150px">راهنمای امروز</div>
        <div class="btn dark" style="width:120px">تیره</div>
      </div>
      <div class="lab">اندازه‌های کوچک‌تر (btn sm / btn xs)</div>
      <div class="rowdemo">
        <div class="btn gold sm">{icon('share',16,'','#2A1D04',2.1)} ارسال دعوت‌نامه</div>
        <div class="btn ghost sm">{icon('phone',16)} پیامک</div>
        <div class="btn gold xs">مطالعهٔ راهنما</div>
        <div class="btn xs ghost" style="border-color:rgba(217,180,91,.5);color:#FAEFC8">ارسال</div>
      </div>
      <div class="lab">دکمهٔ شناور (fab) • آواتار طلایی • توگل خاموش/روشن</div>
      <div class="rowdemo">
        <div class="fab">{icon('plus',26)}</div>
        <div class="avatar ring">ن</div><div class="avatar">آ</div>
        <div class="toggle"><i></i></div><div class="toggle on"><i></i></div>
      </div>
      <div class="cap">نمونه‌های زنده — با همان کلاس‌های اپ (btn gold / ghost / dark / sm / xs / fab / avatar / toggle)</div></div>'''

    chips = f'''<div class="demo">
      <div class="lab">چیپ‌ها</div>
      <div class="rowdemo">
        <div class="chip">{icon('sparkle',13)} پیش‌فرض</div>
        <div class="chip period">{icon('drop',13)} قاعدگی</div>
        <div class="chip fertile">پنجرهٔ باروری</div>
        <div class="chip ovul">تخمک‌گذاری</div>
        <div class="chip pms">{icon('moon',13)} PMS</div>
        <div class="chip gold">{icon('star',13)} ویژهٔ هفته</div>
        <div class="chip dark">تا قاعدگی: ۸ روز</div>
      </div>
      <div class="lab">بج‌ها</div>
      <div class="rowdemo"><span class="badge">۳</span><span class="badge gold">Pro</span></div>
      <div class="cap">chip / chip.period / chip.fertile / chip.ovul / chip.pms / chip.gold / chip.dark / badge</div></div>'''

    cells = f'''<div class="demo">
      <div class="lab">سلول‌های تقویم</div>
      <div class="rowdemo" style="gap:8px">
        <div class="cell" style="width:56px"><div class="fa-num">۱۲</div><div class="bar" style="opacity:.25"></div></div>
        <div class="cell period" style="width:56px"><div class="fa-num">۷</div><div class="bar"></div></div>
        <div class="cell fertile" style="width:56px"><div class="fa-num">۱۶</div><div class="bar"></div></div>
        <div class="cell ovul" style="width:56px"><div class="fa-num">۲۰</div><div class="bar"></div></div>
        <div class="cell pms" style="width:56px"><div class="fa-num">۲۹</div><div class="bar"></div></div>
        <div class="cell today" style="width:56px"><div class="fa-num">۲۷</div><div class="bar" style="opacity:.25"></div></div>
        <div class="cell pred" style="width:56px"><div class="fa-num">۴</div><div class="bar" style="opacity:.25"></div></div>
        <div class="cell dim" style="width:56px"></div>
      </div>
      <div class="cap">cell / period / fertile / ovul / pms / today / pred (خط‌چین) / dim — ارتفاع ۵۰px، شعاع ۱۴px، نوار ۱۶×۳ زیر عدد</div></div>'''

    misc = f'''<div class="demo">
      <div class="lab">حلقهٔ فاز (ring_svg) — ۲۸ روز، ۷ بخش رنگی با فاصلهٔ نیم‌روز و نشانگر روز</div>
      <div class="rowdemo">
        <div class="ring-wrap">{ring_svg(C, 132, 11, 21)}</div>
        <div class="ring-wrap">{ring_svg(C, 92, 9, 21)}</div>
        <div class="ring-wrap">{ring_svg(C, 64, 7, 21)}</div>
      </div>
      <div class="lab">نوار پیشرفت و اسلایدر</div>
      <div class="col" style="gap:10px;max-width:420px">
        <div class="bar-track"><i class="bar-fill" style="width:62%;background:linear-gradient(90deg,#8B6BF5,#C9B7FF)"></i></div>
        <div class="slider"><i></i><b></b></div>
      </div>
      <div class="lab">کاشی آیکون (ic) و کاشی نشانه (sym) و ایموجی (emoj)</div>
      <div class="rowdemo">
        <div class="ic p">{icon('drop',19)}</div><div class="ic f">{icon('shield',19)}</div>
        <div class="ic o">{icon('activity',19)}</div><div class="ic m">{icon('apple',19)}</div>
        <div class="ic g">{icon('heart',19)}</div><div class="ic b">{icon('sleep',19)}</div><div class="ic n">{icon('settings',19)}</div>
      </div>
      <div class="rowdemo" style="margin-top:10px">
        <div class="sym on">{icon('drop',17)}گرفتگی</div><div class="sym">{icon('alert',17)}سردرد</div>
        <div class="emoj on">🙂</div><div class="emoj">😢</div>
      </div>
      <div class="lab">کارت شیشه‌ای + حاشیهٔ طلایی (glass + gold-line) و کارت‌های یادداشت</div>
      <div class="two" style="margin-top:6px">
        <div class="glass gold-line pad col" style="gap:6px">
          <div class="row between"><div class="h3">کارت طلایی</div><div class="chip gold">{icon('sparkle',13)} Pro</div></div>
          <div class="body" style="font-size:11px">گرادیان سطحی + حاشیهٔ طلایی ۱px با ماسک گرادیانی.</div>
        </div>
        <div class="col" style="gap:8px">
          <div class="pnote good">{icon('lock',16,'','#71E0D1')}<div class="tiny" style="line-height:1.8">یادداشت محرمانه (pnote good)</div></div>
          <div class="pnote warn">{icon('alert',16,'','#FFC271')}<div class="tiny" style="line-height:1.8">هشدار (pnote warn)</div></div>
        </div>
      </div>
      <div class="lrow" style="margin-top:10px"><div class="ic b">{icon('coffee',19)}</div>
        <div class="col grow"><div class="h3" style="font-size:12.5px">ردیف فهرستی (lrow)</div><div class="tiny">آیکون ۳۸px + متن + شِوران</div></div>{icon('chev',18,'','#7C7191')}</div>
      <div class="cap">ring_svg / bar / slider / ic / sym / emoj / glass gold-line / pnote / lrow</div></div>'''

    tabdemo = f'''<div class="demo" style="padding-bottom:0">
      <div class="lab">تب‌بار (۴ تب + دکمهٔ شناور میانی)</div>
      <div style="position:relative;height:96px">
        <div class="tabbar" style="position:absolute">{''.join(
            f'<div class="tab {"active" if i == 0 else ""}">{icon(k,21)}<span>{n}</span></div>' if n != "ثبت" else
            f'<div class="col center" style="flex:1"><div class="fab">{icon("plus",26)}</div></div>'
            for i, (n, k) in enumerate(TABS))}</div>
      </div>
      <div class="cap">تب فعال طلایی (#F3E3B4 + هالهٔ نور) • غیرفعال #7C7191 • ارتفاع نوار ۸۸px با بلور ۱۸px</div>
      <div class="lab">نمونهٔ تب‌بار بخش همسر (طرح آبی)</div>
      <div class="tabbar" style="position:relative;background:linear-gradient(180deg,rgba(14,23,41,.35),rgba(10,17,32,.94) 55%,rgba(7,10,18,.98))">{''.join(
          f'<div class="tab {"active" if i == 0 else ""}">{icon(k,21)}<span>{n}</span></div>'
          for i, (n, k) in enumerate([("خانه","home"),("تقویم","cal"),("پیام‌ها","chat"),("راهنما","book")]))}</div>
      </div>'''

    icons_grid = "".join(f'<div class="icon-cell">{icon(n,22,"",'#E6DDF2',1.7)}<span>{n}</span></div>'
                         for n in ICONS)

    # tables -----------------------------------------------------------------
    phase_tbl = "".join(f'''<tr><td><span class="dot" style="display:inline-block;background:{h};box-shadow:0 0 8px {g};margin-left:6px"></span>{n}</td>
      <td class="c">{v}</td><td class="c">{h}</td><td class="c">{ch}</td><td class="c">{cel}</td><td>{mean}</td></tr>'''
      for n, v, h, g, ch, cel, mean in PHASE_ROWS)
    btn_tbl = "".join(f'<tr><td><b>{n}</b></td><td class="c">{c}</td><td class="m">{s}</td><td class="m">{col}</td><td class="m">{u}</td></tr>'
                      for n, c, s, col, u in BUTTONS)
    chip_tbl = "".join(f'<tr><td><b>{n}</b></td><td class="m">{s}</td><td class="m">{c}</td><td class="m">{u}</td></tr>'
                       for n, s, c, u in CHIPS)
    type_tbl = "".join(f'<tr><td><b>{n}</b></td><td class="c">{s}</td><td class="c">{w}</td><td class="c">{lh}</td>'
                       f'<td class="c">{c}</td><td>{u}</td></tr>' for n, s, w, lh, c, u in TYPESCALE)
    action_html = ""
    for scr, rows in SCREEN_ACTIONS:
        action_html += f'<h3 class="sub">{scr}</h3><table class="g"><tr><th>دکمه / عنصر تعاملی</th><th>استایل</th><th>کارکرد</th></tr>'
        action_html += "".join(f'<tr><td><b>{a}</b></td><td class="m">{b}</td><td class="m">{c}</td></tr>' for a, b, c in rows)
        action_html += "</table>"
    did_html = "".join(f'<div class="note"><b>{t}</b><br>{d}</div>' for t, d in DID)

    html = f'''<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">
<title>ماه‌بانو — راهنمای طراحی و تحویل</title><style>{''.join(font_css)}{app_css}{GUIDE_CSS}</style></head>
<body class="gn">
<div class="hero"><div class="wrap">
  <div class="kicker">DESIGN HANDOFF • نسخهٔ ۱٫۰</div>
  <h1>ماه‌بانو — راهنمای طراحی و تحویل</h1>
  <p>این یک فایل است: فونت، تمام کدهای رنگ، توکن‌ها، انواع دکمه‌ها و کامپوننت‌ها (با نمونهٔ زنده)، فهرست تک‌تک دکمه‌ها و کارهای ۱۳ صفحه، و همهٔ کارهایی که برای این طرح انجام شده. هر مقداری که برای ساخت اپ لازم داری، همین‌جاست.</p>
  <div class="gpills"><span class="gpill">۱۳ صفحه</span><span class="gpill">فونت وزیرمتن (۵ وزن)</span>
  <span class="gpill">۲۲ رنگ اصلی</span><span class="gpill">۲۰+ کامپوننت</span><span class="gpill">۴۵ آیکون</span>
  <span class="gpill">فارسی / RTL</span><span class="gpill">۳۹۰×۸۴۴ @2x</span></div>
</div></div>
<div class="wrap">

<h2 class="sec">۱. فونت</h2>
<p class="lead">کل رابط کاربری با یک فونت ساخته شده: <b>وزیرمتن</b>. شماره نسخه و محل دریافت آن هم آمده است.</p>
<table class="g">
  <tr><th>مورد</th><th>مقدار</th></tr>
  <tr><td>نام فونت</td><td><b>Vazirmatn (وزیرمتن)</b> — نسخهٔ ۳۳٫۰٫۳</td></tr>
  <tr><td>مجوز</td><td>SIL Open Font License 1.1 — <b>رایگان برای استفادهٔ تجاری</b> و قابل جاسازی در اپ</td></tr>
  <tr><td>دریافت</td><td class="c">https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/fonts/webfonts/</td></tr>
  <tr><td>وزن‌های استفاده‌شده</td><td class="c">Regular 400 · Medium 500 · SemiBold 600 · Bold 700 · ExtraBold 800</td></tr>
  <tr><td>فایل‌های ذخیره‌شده</td><td class="c">mockups/fonts/Vazirmatn-[weight].woff2</td></tr>
  <tr><td>فونت پشتیبان ایموجی</td><td class="c">'Noto Color Emoji' (برای 😄🙂😐😔😢)</td></tr>
  <tr><td>پشتهٔ نهایی CSS</td><td class="c">font-family:'Vazirmatn','Noto Color Emoji',system-ui,sans-serif</td></tr>
  <tr><td>اعداد</td><td>ارقام فارسی (۰–۹) + <code>font-variant-numeric: tabular-nums</code> برای هم‌ترازی اعداد در جدول‌ها و تقویم</td></tr>
</table>
<div class="demo">
  <div style="font-weight:400;font-size:19px">وزن ۴۰۰ (Regular) — چرخهٔ ماهانه، دقیق و آرام</div>
  <div style="font-weight:500;font-size:19px">وزن ۵۰۰ (Medium) — چرخهٔ ماهانه، دقیق و آرام</div>
  <div style="font-weight:600;font-size:19px">وزن ۶۰۰ (SemiBold) — چرخهٔ ماهانه، دقیق و آرام</div>
  <div style="font-weight:700;font-size:19px">وزن ۷۰۰ (Bold) — چرخهٔ ماهانه، دقیق و آرام</div>
  <div style="font-weight:800;font-size:19px" class="gold-text">وزن ۸۰۰ (ExtraBold) — چرخهٔ ماهانه، دقیق و آرام</div>
  <div class="cap">نمونهٔ زندهٔ هر ۵ وزن</div>
</div>

<h2 class="sec">۲. تایپوگرافی (مقیاس اندازه‌ها)</h2>
<table class="g"><tr><th>عنصر</th><th>اندازه</th><th>وزن</th><th>ارتفاع خط</th><th>رنگ</th><th>کاربرد</th></tr>{type_tbl}</table>

<h2 class="sec">۳. رنگ‌ها — کدهای دقیق</h2>
<h3 class="sub">۳٫۱ پایه و متن</h3>
{swatches([
 ("background:#05030A","#05030A","پس‌زمینهٔ کل صفحه (body)"),
 ("background:#07040C","#07040C","تیرگی عمیق (bg0) — لبه‌ها"),
 ("background:#150E22","#150E22","بنفش‌تیرهٔ بالا (bg1)"),
 ("background:#241537","#241537","بنفش متوسط (bg2) — سایه‌های رنگی"),
 ("background:#F8F3F9","#F8F3F9","متن اصلی (txt)"),
 ("background:#ABA0BC","#ABA0BC","متن ثانویه (muted)"),
 ("background:#7C7191","#7C7191","متن کم‌رنگ (muted2) و تب غیرفعال"),
 ("background:#2A1D04","#2A1D04","متن روی دکمهٔ طلایی (onGold)"),
])}
<h3 class="sub">۳٫۲ خانوادهٔ طلایی (هویت برند)</h3>
{swatches([
 ("background:linear-gradient(135deg,#FAEFC8,#D9B45B 55%,#9C7228)","gold-text","گرادیان متن طلایی (عناوین و اعداد اوج)"),
 ("background:#FAEFC8","#FAEFC8","طلایی روشن (gold1)"),
 ("background:#D9B45B","#D9B45B","طلایی میانی (gold2)"),
 ("background:#9C7228","#9C7228","طلایی تیره (gold3)"),
 ("background:linear-gradient(135deg,#FDEFC4 0%,#E7C877 38%,#C79A3E 78%,#9C7228 100%)","btn gold","گرادیان دکمهٔ اصلی (۴ توقف)"),
 ("background:linear-gradient(135deg,#FF6B8B,#EA3F63)","badge","گرادیان بج اعلان (سرخابی)"),
])}
<h3 class="sub">۳٫۳ رنگ فازهای چرخه (معنای رنگ — همه‌جای اپ یکسان)</h3>
<table class="g">
  <tr><th>فاز</th><th>متغیر CSS</th><th>کد رنگ</th><th>چیپ (پس‌زمینه/حاشیه)</th><th>سلول تقویم (گرادیان)</th><th>معنا</th></tr>
  {phase_tbl}
</table>
<div class="note">قاعدهٔ استفاده: <b>هر فاز در همهٔ صفحه‌ها با همان رنگ</b> دیده می‌شود — تقویم، حلقه، چیپ، نمودار و پیام همسر. رنگ‌های «soft» (مثل #FDE7EC) فقط برای نسخهٔ روشن/چاپ در لایهٔ داده ذخیره شده‌اند.</div>
<h3 class="sub">۳٫۴ رنگ‌های وضعیت و متن‌های روی رنگ</h3>
{swatches([
 ("background:#71E0D1","#71E0D1","متن موفقیت / روی فیروزه‌ای"),
 ("background:#93BEFF","#93BEFF","اطلاع‌رسانی (آبی)"),
 ("background:#FFC271","#FFC271","هشدار (نارنجی)"),
 ("background:#FF9FB4","#FF9FB4","خطر / قاعدگی در متن"),
 ("background:#FFD7E0","#FFD7E0","متن روی چیپ قاعدگی"),
 ("background:#CBF3EC","#CBF3EC","متن روی چیپ باروری"),
 ("background:#FFE6C2","#FFE6C2","متن روی چیپ PMS"),
 ("background:#E2DAFF","#E2DAFF","متن روی چیپ تخمک‌گذاری"),
 ("background:#D9D2E4","#D9D2E4","اعداد روزهای تقویم"),
 ("background:#E6DDF2","#E6DDF2","آیکون‌های اصلی"),
 ("background:#FFE1E8","#FFE1E8","متن سلول قاعدگی"),
 ("background:#D6F6F0","#D6F6F0","متن سلول باروری"),
 ("background:#EAE4FF","#EAE4FF","متن سلول تخمک‌گذاری"),
 ("background:#FFEBD0","#FFEBD0","متن سلول PMS"),
 ("background:#FFF4D6","#FFF4D6","متن سلول «امروز»"),
])}
<h3 class="sub">۳٫۵ سطوح شیشه‌ای و حاشیه‌ها</h3>
<table class="g">
  <tr><th>توکن</th><th>مقدار</th><th>کاربرد</th></tr>
  <tr><td>glass</td><td class="c">rgba(255,255,255,.058)</td><td>پس‌زمینهٔ شیشه‌ای کارت‌ها</td></tr>
  <tr><td>stroke</td><td class="c">rgba(255,255,255,.105)</td><td>حاشیهٔ کارت‌ها، چیپ‌ها، توگل</td></tr>
  <tr><td>card gradient</td><td class="c">linear-gradient(180deg, rgba(255,255,255,.082), rgba(255,255,255,.026))</td><td>بدنهٔ کارت شیشه‌ای</td></tr>
  <tr><td>lrow / sym surface</td><td class="c">rgba(255,255,255,.045) / .04</td><td>ردیف‌ها و کاشی‌ها</td></tr>
  <tr><td>حاشیهٔ طلایی کارت</td><td class="c">gradient rgba(250,239,200,.55) → rgba(217,180,91,.16) → rgba(255,255,255,.04) با mask</td><td>کلاس gold-line</td></tr>
  <tr><td>بلور</td><td class="c">backdrop-filter: blur(14px) — تب‌بار blur(18px)</td><td>شیشه‌ای‌بودن واقعی</td></tr>
</table>
<h3 class="sub">۳٫۶ پس‌زمینهٔ صفحه‌ها</h3>
<div class="note">اپ بانوان: گرادیان بنفش از بالا-راست (rgba(120,66,170,.55)) + هالهٔ سرخابی پایین-چپ (rgba(206,60,105,.28)) + هالهٔ طلایی (rgba(255,190,90,.16)) روی <code>linear-gradient(180deg,#150E22,#0B0713 55%,#070410)</code>؛ همراه سه لکهٔ نوری محو (blur 58px) و بافت دانه‌ای ۳px با شفافیت ۰٫۰۵. تم بخش همسر: پایهٔ سرمه‌ای <code>linear-gradient(180deg,#0E1729,#0A1120 55%,#070A12)</code> با هالهٔ آبی <code>rgba(46,86,140,.42)</code> و تأکید طلایی — تا حس «آرام و حمایتی» بدهد و از بخش بانوان قابل تشخیص باشد.</div>

<h2 class="sec">۴. گرادیان‌ها، سایه‌ها، شعاع و فاصله‌ها</h2>
<table class="g">
  <tr><th>توکن</th><th>مقدار</th><th>کاربرد</th></tr>
  <tr><td>گرادیان دکمهٔ طلایی</td><td class="c">135deg: #FDEFC4 0% → #E7C877 38% → #C79A3E 78% → #9C7228 100%</td><td>btn gold، fab، آواتار</td></tr>
  <tr><td>گرادیان متن طلایی</td><td class="c">135deg: #FAEFC8 → #D9B45B 55% → #9C7228</td><td>gold-text</td></tr>
  <tr><td>گرادیان تب‌بار</td><td class="c">180deg: rgba(16,10,26,.35) → rgba(12,8,20,.92) 55% → rgba(10,6,16,.98)</td><td>پس‌زمینهٔ نوار پایین</td></tr>
  <tr><td>جداکنندهٔ طلایی</td><td class="c">90deg: transparent → rgba(217,180,91,.5) → transparent</td><td>کلاس divider</td></tr>
  <tr><td>سایهٔ کارت</td><td class="c">0 18px 44px rgba(0,0,0,.45)</td><td>glass</td></tr>
  <tr><td>سایهٔ کارت نرم</td><td class="c">0 10px 26px rgba(0,0,0,.32)</td><td>glass soft</td></tr>
  <tr><td>سایهٔ دکمهٔ طلایی</td><td class="c">0 12px 26px rgba(196,150,60,.30) + inset 0 1px 0 rgba(255,255,255,.6)</td><td>btn gold</td></tr>
  <tr><td>سایهٔ دکمهٔ شناور</td><td class="c">0 14px 30px rgba(190,145,55,.42) + inset 0 1px 0 rgba(255,255,255,.65)</td><td>fab</td></tr>
  <tr><td>هالهٔ سلول امروز</td><td class="c">0 0 0 1px rgba(217,180,91,.35) + 0 10px 22px rgba(190,145,55,.20)</td><td>cell.today</td></tr>
  <tr><td>هالهٔ نقطه‌های فاز</td><td class="c">0 0 8px [رنگ فاز با ۸۰٪]</td><td>dot.p / dot.f / dot.o / dot.m</td></tr>
  <tr><td>شعاع‌ها</td><td class="c">۲۶ · ۲۴ · ۲۲ · ۲۰ · ۱۸ · ۱۶ · ۱۴ · ۱۳ · ۱۲ · ۱۱ · ۱۰ · ۹۹۹</td><td>کارت/مودال ۲۴–۲۶ • دکمه ۱۶ • سلول ۱۴ • چیپ ۹۹۹</td></tr>
  <tr><td>فاصله‌ها</td><td class="c">۴ · ۵ · ۶ · ۸ · ۹ · ۱۰ · ۱۱ · ۱۲ · ۱۴ · ۱۶ · ۱۸</td><td>فاصلهٔ بین کارت‌ها ۹px • حاشیهٔ کناری صفحه ۱۸px • پدینگ کارت ۱۴px</td></tr>
</table>

<h2 class="sec">۵. دکمه‌ها و کامپوننت‌ها (نمونهٔ زنده)</h2>
<h3 class="sub">۵٫۱ دکمه‌ها</h3>
{btns}
<table class="g"><tr><th>نام</th><th>کلاس</th><th>ابعاد</th><th>رنگ‌ها</th><th>کاربرد در طرح</th></tr>{btn_tbl}</table>
<h3 class="sub">۵٫۲ چیپ‌ها و بج‌ها</h3>
{chips}
<table class="g"><tr><th>نام</th><th>ابعاد</th><th>رنگ‌ها</th><th>کاربرد</th></tr>{chip_tbl}</table>
<h3 class="sub">۵٫۳ تقویم</h3>
{cells}
<h3 class="sub">۵٫۴ حلقهٔ فاز، نوارها، کاشی‌ها و کارت‌ها</h3>
{misc}
<div class="note teal"><b>مشخصات حلقهٔ فاز:</b> ۲۸ بخش، ضخامت ۱۱–۱۵px، فاصلهٔ «۰٫۵۵ روز» بین تکه‌ها، شروع از بالا (۱۲ ساعت) و گردش ساعتگرد، نشانگر روز به‌صورت دایرهٔ طلایی تو‌خالی. قطرهای استفاده‌شده: ۶۴ (مینی)، ۷۴ (تحلیل)، ۹۲، ۱۳۲، ۱۴۰ (خانه)، ۱۵۸ (چرخهٔ من)، ۱۸۶ (آنبوردینگ)، ۲۰۰ (اسپلش ۳۰۰).</div>
<h3 class="sub">۵٫۵ تب‌بار</h3>
{tabdemo}

<h2 class="sec">۶. فهرست کامل دکمه‌ها و کارهای هر صفحه</h2>
<p class="lead">هر دکمه/عنصر تعاملی در ۱۳ صفحه، با استایل و عملکردش:</p>
{action_html}

<h2 class="sec">۷. آیکون‌ها (۴۵ آیکون خطی)</h2>
<p class="lead">همه SVG درون‌خطی، viewBox ۲۴، ضخامت خط ۱٫۷–۱٫۸ (در دکمهٔ اصلی ۲٫۱–۲٫۴)، سر خط گرد، بدون پرکردن. فایل: <code>mockups/components.py</code></p>
<div class="icon-grid">{icons_grid}</div>

<h2 class="sec">۸. توکن‌های آمادهٔ توسعه‌دهنده</h2>
<h3 class="sub">۸٫۱ متغیرهای CSS (قابل کپی)</h3>
<div class="codeblock">{TOKENS_CSS}</div>
<h3 class="sub">۸٫۲ توکن‌های JSON (برای Flutter / React Native / Figma)</h3>
<div class="codeblock">{TOKENS_JSON}</div>
<div class="note">نگاشت سریع: در Flutter پکیج <code>shamsi_date</code> و در React Native پکیج <code>jalaali-js</code> برای تقویم؛ منطق فازها را در ماژول مشترک <code>cycle-engine</code> پیاده و unit-test کن (پارامترها در بخش ۹).</div>

<h2 class="sec">۹. موتور محاسبهٔ چرخه (اعداد پایه)</h2>
<table class="g">
  <tr><th>پارامتر</th><th>مقدار پیش‌فرض</th><th>فرمول / توضیح</th></tr>
  <tr><td>طول چرخه</td><td class="c">۲۸ روز</td><td>از میانگین ۳–۶ چرخهٔ آخر کاربر به‌روزرسانی می‌شود (بازهٔ نرمال ۲۱–۳۵)</td></tr>
  <tr><td>طول قاعدگی</td><td class="c">۵ روز</td><td>قابل تنظیم ۲–۷</td></tr>
  <tr><td>فاز لوتئال</td><td class="c">۱۴ روز</td><td>تقریباً ثابت؛ مبنای محاسبهٔ تخمک‌گذاری</td></tr>
  <tr><td>روز تخمک‌گذاری</td><td class="c">روز ۱۴</td><td><code>cycleLen − lutealLen</code></td></tr>
  <tr><td>پنجرهٔ باروری</td><td class="c">روز ۹ تا ۱۵</td><td>۵ روز پیش از تخمک‌گذاری تا ۱ روز بعد (بقای اسپرم ~۵ روز)</td></tr>
  <tr><td>پنجرهٔ PMS</td><td class="c">۷ روز آخر (روز ۲۲+)</td><td><code>cycleLen − 7 + 1</code> — با داده کاربر شخصی‌سازی می‌شود</td></tr>
</table>
<div class="note"><b>دادهٔ مبنای طرح:</b> آخرین قاعدگی ۷ شهریور ۱۴۰۵ • چرخهٔ ۲۸ روزه • امروز ۲۷ شهریور (روز ۲۱، فاز لوتئال) • قاعدگی بعدی ۴ مهر (۸ روز دیگر) • پنجرهٔ باروری ۱۵ تا ۲۱ شهریور • تخمک‌گذاری ۲۰ شهریور • PMS از ۲۸ شهریور تا ۳ مهر. تبدیل‌ها با موتور <code>jalali.py</code> و تست‌شده با <code>datetime</code> انجام شده است.</div>

<h2 class="sec">۱۰. همهٔ کارهایی که انجام شد</h2>
{did_html}
<h3 class="sub">۱۰٫۱ دستورهای بازتولید</h3>
<div class="codeblock">{REPRO}</div>
<h3 class="sub">۱۰٫۲ نقشهٔ فایل‌ها</h3>
<table class="g">
  <tr><th>مسیر</th><th>محتوا</th></tr>
  <tr><td class="c">screens/moonbanu-ui-design.html</td><td>گالری ارائهٔ ۱۳ صفحه (خودکفا و آفلاین)</td></tr>
  <tr><td class="c">screens/moonbanu-blueprint.html</td><td>نقشهٔ راه محصول (فنی، دیتابیس، نقشهٔ راه ساخت)</td></tr>
  <tr><td class="c">screens/moonbanu-style-guide.html</td><td><b>همین فایل</b> — راهنمای کامل طراحی و تحویل</td></tr>
  <tr><td class="c">screenshots/00-overview.png</td><td>تصویر کلی ۱۳ صفحه در یک نگاه</td></tr>
  <tr><td class="c">screenshots/01…13-*.png</td><td>اسکرین‌شات‌های نهایی (۳۹۰×۸۴۴ با مقیاس ۲x)</td></tr>
  <tr><td class="c">mockups/jalali.py</td><td>موتور تقویم شمسی + موتور فاز چرخه</td></tr>
  <tr><td class="c">mockups/style.css</td><td>سیستم طراحی Noir Bloom (همهٔ توکن‌ها و کامپوننت‌ها)</td></tr>
  <tr><td class="c">mockups/components.py</td><td>۴۵ آیکون SVG + کامپوننت‌های مشترک + لوگو + حلقه + QR</td></tr>
  <tr><td class="c">mockups/screens_woman.py</td><td>سورس ۹ صفحهٔ بخش بانوان</td></tr>
  <tr><td class="c">mockups/screens_partner.py</td><td>سورس ۴ صفحهٔ بخش همسر</td></tr>
  <tr><td class="c">mockups/build.py</td><td>رندر خودکار PNG + گزارش سرریز محتوا</td></tr>
  <tr><td class="c">mockups/build_presentation.py</td><td>تصویر کلی + گالری ارائه</td></tr>
  <tr><td class="c">mockups/build_blueprint.py</td><td>نقشهٔ راه محصول</td></tr>
  <tr><td class="c">mockups/build_styleguide.py</td><td>سازندهٔ همین فایل</td></tr>
  <tr><td class="c">README.md</td><td>خلاصهٔ پروژه و دستورها</td></tr>
</table>

<h2 class="sec">۱۱. دو قاعده‌ای که نباید نقض شود</h2>
<div class="note rose"><b>حریم خصوصی:</b> نشانه‌ها، خلق، یادداشت‌های خصوصی و سلامت جنسی هرگز برای همسر نمایش داده نمی‌شود؛ پنجرهٔ باروری و تخمک‌گذاری به‌صورت پیش‌فرض پنهان است؛ دسترسی همسر یک‌طرفه و بی‌اطلاع او قابل لغو است.</div>
<div class="note"><b>سلامت:</b> در همهٔ صفحه‌ها این جمله لازم است: «این اپ جایگزین تشخیص یا درمان پزشکی نیست.» محتوای سلامت باید توسط متخصص زنان بازبینی شود و روش تقویمی به‌تنهایی برای پیشگیری توصیه نشود.</div>

<footer class="g">ماه‌بانو • راهنمای طراحی و تحویل نسخهٔ ۱٫۰ — تاریخ: ۲۷ شهریور ۱۴۰۵<br>
ساخته‌شده با موتور <code>jalali.py</code>، سیستم طراحی Noir Bloom و فونت وزیرمتن</footer>
</div></body></html>'''
    OUT.write_text(html, encoding="utf-8")
    print("style guide:", OUT, f"{OUT.stat().st_size//1024} KB")


if __name__ == "__main__":
    build()
