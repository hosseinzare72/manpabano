# -*- coding: utf-8 -*-
"""Contact sheet (PNG) + self-contained HTML gallery."""
import base64, io, pathlib
from PIL import Image

SHOTS = pathlib.Path("/home/user/screenshots")
OUT = pathlib.Path("/home/user/screens")
OUT.mkdir(exist_ok=True)

ORDER = [
    ("01-splash", "اسپلش / برندینگ", "ورود با نشان ماه و گل؛ حس لوکس و آرام از اولین ثانیه.", "برندینگ"),
    ("02-onboarding", "خوش‌آمدگویی و معرفی", "سه وعدهٔ ارزش: پیش‌بینی دقیق، ثبت نشانه‌ها، اطلاع‌رسانی به همسر.", "آنبوردینگ"),
    ("03-today-home", "صفحهٔ امروز", "وضعیت لحظه‌ای: فاز فعلی، شمارش تا قاعدگی، احتمال بارداری، انرژی و راهنمای روز.", "اصلی"),
    ("04-calendar", "تقویم شمسی چرخه", "همهٔ روزها رنگی‌شده: قاعدگی، باروری، تخمک‌گذاری، PMS، لوتئال و روزهای عادی + پیش‌بینی ۳ چرخه.", "اصلی"),
    ("05-day-detail", "جزئیات یک روز", "احتمال تجربهٔ هر نشانه، توصیهٔ خوراک و حرکت، و هشدار پیشگیری.", "اصلی"),
    ("06-log-symptoms", "ثبت وضعیت روزانه", "حال، خونریزی، نشانه‌ها، شدت درد و یادداشت خصوصی — سوخت موتور پیش‌بینی.", "ثبت داده"),
    ("07-insights", "تحلیل و الگوی شخصی", "میانگین چرخه، دقت مدل، نمودار ۶ چرخه و کشف الگوهای بدن کاربر.", "هوشمند"),
    ("08-cycle-map", "نقشهٔ کامل چرخه", "تفکیک دقیق فازها با تاریخ شمسی و طول هر مرحله.", "هوشمند"),
    ("09-health-center", "مرکز سلامت و آموزش", "آموزش قاعدگی، پیشگیری، تغذیه، سلامت روان، علائم هشدار و پرسش از متخصص.", "آموزش"),
    ("10-partner-link", "اتصال همسر (اختیاری)", "دعوت با QR؛ کنترل کامل حریم خصوصی در دست کاربر.", "همسر"),
    ("11-partner-home", "خانهٔ همسر", "خلاصهٔ ۷ روز آینده + کارهای کوچک حمایتی، بدون نمایش نشانه‌های خصوصی.", "همسر"),
    ("12-partner-alert", "پیام یادآور مهربانی", "هشدار دو روز قبل از PMS با متن آماده و راهنمای عملی حمایت.", "همسر"),
    ("13-partner-calm", "حالت آرامش", "چک‌لیست ۵ کار مؤثر، جمله‌هایی که باید گفت و جمله‌هایی که نباید.", "همسر"),
]


def contact_sheet():
    cols, pad, w = 5, 26, 300
    ims = []
    for name, *_ in ORDER:
        im = Image.open(SHOTS / f"{name}.png").convert("RGB")
        h = int(im.height * w / im.width)
        ims.append(im.resize((w, h), Image.LANCZOS))
    rh = max(i.height for i in ims) + pad
    rows = (len(ims) + cols - 1) // cols
    W = cols * w + (cols + 1) * pad
    H = rows * rh + (rows + 1) * pad
    sheet = Image.new("RGB", (W, H), (7, 4, 12))
    for i, im in enumerate(ims):
        r, c = divmod(i, cols)
        x = pad + c * (w + pad)
        y = pad + r * rh
        sheet.paste(im, (x, y))
    sheet.save(SHOTS / "00-overview.png", quality=95)
    print("contact sheet", sheet.size)


def gallery():
    cap = {"برندینگ": "#D9B45B", "آنبوردینگ": "#8B6BF5", "اصلی": "#EA3F63",
           "ثبت داده": "#12A594", "هوشمند": "#3E86E8", "آموزش": "#EE9A2E", "همسر": "#7BA7E8"}
    cards = []
    for i, (name, title, desc, tag) in enumerate(ORDER):
        im = Image.open(SHOTS / f"{name}.png").convert("RGB")
        im = im.resize((468, int(im.height * 468 / im.width)), Image.LANCZOS)
        buf = io.BytesIO()
        im.save(buf, "JPEG", quality=84, optimize=True)
        b64 = base64.b64encode(buf.getvalue()).decode()
        num = "۰۱۲۳۴۵۶۷۸۹"[int(name[:2]) // 10] + "۰۱۲۳۴۵۶۷۸۹"[int(name[:2]) % 10]
        cards.append(f'''
    <article class="card">
      <div class="shot"><img src="data:image/jpeg;base64,{b64}" alt="{title}"></div>
      <div class="meta">
        <div class="row"><span class="num">{num}</span><span class="tag" style="--c:{cap[tag]}">{tag}</span></div>
        <h3>{title}</h3><p>{desc}</p>
      </div>
    </article>''')
    html = f'''<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">
<title>ماه‌بانو — طرح رابط کاربری</title><style>
@font-face{{font-family:V;src:url('data:font/woff2;base64,{base64.b64encode((pathlib.Path("/home/user/mockups/fonts/Vazirmatn-Regular.woff2")).read_bytes()).decode()}');font-weight:400}}
@font-face{{font-family:V;src:url('data:font/woff2;base64,{base64.b64encode((pathlib.Path("/home/user/mockups/fonts/Vazirmatn-Bold.woff2")).read_bytes()).decode()}');font-weight:700}}
*{{box-sizing:border-box;margin:0;padding:0}}
body{{font-family:V,system-ui,sans-serif;background:radial-gradient(120% 60% at 80% -10%,#2A1533 0%,#0A0611 55%,#07040C 100%);
color:#F6F1F8;padding:44px 26px 70px}}
header{{max-width:1500px;margin:0 auto 34px;display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap}}
h1{{font-size:34px;letter-spacing:.5px;background:linear-gradient(135deg,#FAEFC8,#D9B45B 55%,#9C7228);-webkit-background-clip:text;background-clip:text;color:transparent}}
.sub{{color:#ABA0BC;font-size:13.5px;line-height:2.1;max-width:640px;margin-top:8px}}
.pills{{display:flex;gap:8px;flex-wrap:wrap}}
.pill{{border:1px solid rgba(255,255,255,.13);background:rgba(255,255,255,.05);border-radius:999px;padding:7px 14px;font-size:12px;color:#E6DDF2}}
.grid{{max-width:1500px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(268px,1fr));gap:22px}}
.card{{background:linear-gradient(180deg,rgba(255,255,255,.075),rgba(255,255,255,.022));border:1px solid rgba(255,255,255,.10);
border-radius:26px;padding:14px;box-shadow:0 20px 46px rgba(0,0,0,.45)}}
.shot{{border-radius:18px;overflow:hidden;box-shadow:0 14px 30px rgba(0,0,0,.5)}}
.shot img{{width:100%;display:block}}
.meta{{padding:13px 6px 4px}}
.row{{display:flex;align-items:center;gap:8px;margin-bottom:8px}}
.num{{font-size:12px;font-weight:700;color:#7C7191}}
.tag{{font-size:10.5px;font-weight:700;color:var(--c);border:1px solid color-mix(in srgb,var(--c) 45%,transparent);
background:color-mix(in srgb,var(--c) 14%,transparent);border-radius:999px;padding:3px 10px}}
h3{{font-size:15px;margin-bottom:6px}}
p{{font-size:11.8px;line-height:2;color:#ABA0BC}}
footer{{max-width:1500px;margin:44px auto 0;color:#7C7191;font-size:12px;text-align:center;line-height:2.2}}
</style></head><body>
<header>
  <div><h1>ماه‌بانو — طرح رابط کاربری</h1>
  <div class="sub">۱۳ صفحهٔ نهایی برای اپلیکیشن چرخهٔ ماهانهٔ بانوان با حالت اختصاصی همسر. تقویم شمسی، فاز‌بندی پزشکی، پیش‌بینی هوشمند و آموزش سلامت — با زبان بصری تاریک و طلایی (Noir Bloom).</div></div>
  <div class="pills"><span class="pill">۱۳ صفحه</span><span class="pill">فارسی / RTL</span>
  <span class="pill">۳۹۰×۸۴۴ @2x</span><span class="pill">فونت وزیرمتن</span></div>
</header>
<main class="grid">{''.join(cards)}</main>
<footer>طرح مفهومی UI — همهٔ تاریخ‌ها بر پایهٔ تقویم شمسی محاسبه شده‌اند (امروز: ۲۷ شهریور ۱۴۰۵).</footer>
</body></html>'''
    (OUT / "moonbanu-ui-design.html").write_text(html, encoding="utf-8")
    print("gallery written", len(html) // 1024, "KB")


if __name__ == "__main__":
    contact_sheet()
    gallery()
