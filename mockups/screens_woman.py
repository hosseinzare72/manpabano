# -*- coding: utf-8 -*-
"""Screens 01-09 — ماه‌بانو (بانوان)."""
from jalali import Cycle, fa, JALALI_MONTHS, WEEKDAYS_FA, FAQ, PHASES, month_days, weekday, add_days
from components import icon, statusbar, tabbar, homebar, header, section, legend, logo, ring_svg, qr_svg, water

C = Cycle()  # today = 27 Shahrivar 1405, LMP = 7 Shahrivar, 28-day cycle


def page(body, cls="", title="", partner=False):
    return f'''<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">
<title>{title}</title><link rel="stylesheet" href="../style.css"></head>
<body><div class="stage"><div class="phone {'partner' if partner else ''} {cls}">
<div class="aurora a1"></div><div class="aurora a2"></div><div class="aurora a3"></div><div class="grain"></div>
{statusbar()}{body}{homebar()}</div></div></body></html>'''


# ---------------------------------------------------------------- 01 splash
def s01_splash():
    body = f'''<div class="content col center" style="justify-content:center;gap:0">
  <div style="position:absolute;top:96px;left:50%;transform:translateX(-50%);opacity:.5">{ring_svg(C, 300, 6, 21)}</div>
  <div style="margin-top:40px">{logo(140)}</div>
  <div class="col center" style="margin-top:18px;gap:8px">
    <div style="font-size:38px;font-weight:800;letter-spacing:1px" class="gold-text">ماه‌بانو</div>
    <div class="divider" style="width:150px;margin:2px 0"></div>
    <div class="body" style="color:#C6BCD6;font-size:12.5px">همراه هوشمند چرخهٔ ماهانهٔ بانوان</div>
  </div>
  <div class="row gap8" style="margin-top:26px">
    <div class="chip gold">{icon('sparkle',14)} دقت پیش‌بینی ۹۶٪</div>
    <div class="chip">{icon('lock',14)} حریم خصوصی کامل</div>
  </div>
  <div class="col center" style="position:absolute;bottom:74px;gap:14px">
    <div class="row gap8" style="opacity:.9">
      <div class="dot p"></div><div class="dot f"></div><div class="dot o"></div><div class="dot m"></div>
    </div>
    <div class="tiny" style="letter-spacing:.3px">نسخهٔ ۱٫۰ — ساخته‌شده با 🤍 برای بانوان</div>
  </div>
</div>'''
    return page(body, title="۰۱ — اسپلش")


# ---------------------------------------------------------------- 02 onboarding
def s02_onboarding():
    feats = [
        ("cal", "p", "پیش‌بینی دقیق و تاریخ‌به‌تاریخ",
         "قاعدگی، پنجرهٔ باروری، تخمک‌گذاری، PMS و روزهای عادی — همه روی تقویم شمسی، از قبل مشخص."),
        ("activity", "f", "ثبت نشانه‌ها و تحلیل هوشمند",
         "خلق، درد، خونریزی، خواب و انرژی را ثبت کن؛ الگوی شخصی بدنت را کشف کن."),
        ("heart", "m", "اطلاع‌رسانی محترمانه به همسر",
         "همسرت فقط زمان‌های حساس را می‌داند و می‌داند چه‌کار کند — نه بیشتر، نه کمتر."),
    ]
    rows = "".join(f'''<div class="lrow" style="gap:12px">
      <div class="ic {c}">{icon(i,19)}</div>
      <div class="col grow" style="gap:3px"><div class="h3">{t}</div>
      <div class="body" style="font-size:11px;line-height:1.9;color:var(--muted2)">{s}</div></div></div>''' for i, c, t, s in feats)
    body = f'''<div class="content"><div class="scroll col" style="gap:12px;padding-top:4px">
  <div class="row between"><div class="small">رد کردن</div><div class="chip gold">۱ از ۳</div></div>
  <div class="col center" style="margin-top:2px">
    <div style="position:relative;display:flex;align-items:center;justify-content:center;width:186px;height:186px">
      <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center">{ring_svg(C, 182, 10, 21)}</div>
      <div class="col center" style="gap:2px">{logo(70)}
        <div class="tiny" style="margin-top:2px">چرخهٔ تو، در یک نگاه</div></div>
    </div>
  </div>
  <div class="col" style="gap:8px;text-align:center;margin-top:2px">
    <div class="h1" style="font-size:18.5px">چرخهٔ بدنت را بشناس، با اطمینان زندگی کن</div>
    <div class="body" style="font-size:11.5px">ماه‌بانو تاریخ‌های مهم ماه را از قبل نشانت می‌دهد تا برای کار، ورزش، سفر و رابطه آماده باشی.</div>
  </div>
  <div class="col" style="gap:9px">{rows}</div>
  <div class="row gap6 center" style="margin-top:2px">
    <div style="width:22px;height:5px;border-radius:3px;background:linear-gradient(90deg,#FDEFC4,#D9B45B)"></div>
    <div style="width:6px;height:5px;border-radius:3px;background:rgba(255,255,255,.2)"></div>
    <div style="width:6px;height:5px;border-radius:3px;background:rgba(255,255,255,.2)"></div>
  </div>
  <div class="btn gold wide" style="margin-top:2px">شروع کنیم {icon('chevL',18,'','#3A2A06',2.2)}</div>
</div></div>'''
    return page(body, title="۰۲ — خوش‌آمد")


# ---------------------------------------------------------------- 03 home / today
def s03_home():
    stats = [("پیش‌بینی شروع", "۴ مهر", "pms"), ("PMS", "فردا، ۲۸ شهریور", "pms"), ("دقت مدل", "۹۶٪", "gold")]
    chips = "".join(f'''<div class="chip {cl}" style="height:26px;font-size:10.5px;padding:0 9px">{t}: <b style="font-weight:700">{v}</b></div>''' for t, v, cl in
                    [("فاز فعلی", "لوتئال", ""), ("روز چرخه", "۲۱ از ۲۸", ""), ("پنجرهٔ باروری", "بسته شد", "fertile")])
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  {header("سلام، نگار جان", "جمعه ۲۷ شهریور ۱۴۰۵", bell=True)}
  <div class="glass gold-line pad-lg col" style="gap:13px">
    <div class="row between">
      <div class="col" style="gap:4px">
        <div class="h2">فردا وارد فاز PMS می‌شوی</div>
        <div class="body" style="font-size:11px">۷ روز حساس پیشِ رو؛ آرام‌تر، ملایم‌تر، با برنامه.</div>
      </div>
      <div class="chip pms">{icon('moon',14)} PMS</div>
    </div>
    <div class="row gap14" style="align-items:center">
      <div class="ring-wrap">{ring_svg(C, 140, 11, 21)}
        <div class="ring-center" style="gap:0"><div style="font-size:11px;color:var(--muted)">تا قاعدگی</div>
        <div class="hero-num gold-text fa-num" style="font-size:38px">۸</div>
        <div class="tiny">روز • ۴ مهر</div></div></div>
      <div class="col grow" style="gap:9px">
        <div class="row between"><div class="small">احتمال بارداری</div><div class="small" style="color:#71E0D1">کم</div></div>
        <div class="bar-track"><i class="bar-fill" style="width:14%;background:linear-gradient(90deg,#0F9C8D,#2ED3BC)"></i></div>
        <div class="row between"><div class="small">انرژی پیش‌بینی‌شده</div><div class="small">۶۲٪</div></div>
        <div class="bar-track"><i class="bar-fill" style="width:62%;background:linear-gradient(90deg,#8B6BF5,#C9B7FF)"></i></div>
        <div class="row between"><div class="small">کیفیت خواب</div><div class="small" style="color:#FFC271">نیاز به توجه</div></div>
        <div class="bar-track"><i class="bar-fill" style="width:48%;background:linear-gradient(90deg,#D98A22,#FFC271)"></i></div>
      </div>
    </div>
    <div class="row gap8" style="flex-wrap:wrap">{chips}</div>
    <div class="row gap8">
      <div class="btn gold grow">{icon('plus',17,'','#2A1D04',2.2)} ثبت نشانهٔ امروز</div>
      <div class="btn ghost" style="width:118px">راهنمای امروز</div>
    </div>
  </div>
  {section("برنامهٔ امروز", "بر اساس فاز لوتئال")}
  <div class="row gap8" style="align-items:stretch">
    <div class="glass soft col center grow" style="padding:11px 8px;gap:7px">
      <div class="ic m" style="width:32px;height:32px;border-radius:11px">{icon('pill',16)}</div>
      <div class="tiny" style="font-size:9.5px;text-align:center;line-height:1.6;color:#D8D1E2">منیزیم و ویتامین B۶<br>برای خواب و گرفتگی</div></div>
    <div class="glass soft col center grow" style="padding:11px 8px;gap:7px">
      <div class="ic f" style="width:32px;height:32px;border-radius:11px">{icon('walk',16)}</div>
      <div class="tiny" style="font-size:9.5px;text-align:center;line-height:1.6;color:#D8D1E2">۳۰ دقیقه پیاده‌روی<br>ضد نفخ و بدخلقی</div></div>
    <div class="glass soft col center grow" style="padding:11px 8px;gap:7px">
      <div class="ic b" style="width:32px;height:32px;border-radius:11px">{icon('coffee',16)}</div>
      <div class="tiny" style="font-size:9.5px;text-align:center;line-height:1.6;color:#D8D1E2">کافئین بعد از ۴ عصر<br>ممنوع</div></div>
  </div>
  <div class="glass gold-line pad row gap12" style="background:linear-gradient(120deg,rgba(217,180,91,.16),rgba(234,63,99,.10))">
    <div class="ic g">{icon('heart',19)}</div>
    <div class="col grow" style="gap:3px"><div class="h3" style="font-size:12.5px">پیام حمایت برای آرش آماده است</div>
      <div class="tiny">«از فردا ۷ روز حساس داریم؛ کنارم باش»</div></div>
    <div class="btn xs ghost" style="border-color:rgba(217,180,91,.5);color:#FAEFC8">ارسال</div>
  </div>
</div></div>
{tabbar("home")}'''
    return page(body, title="۰۳ — امروز")


# ---------------------------------------------------------------- 04 calendar
def s04_calendar():
    jy, jm = 1405, 6
    cells = []
    first_wd = weekday(jy, jm, 1)
    for _ in range(first_wd):
        cells.append('<div class="cell dim"></div>')
    mdays = month_days(jy, jm)
    for d in range(1, mdays + 1):
        ph = C.phase(jy, jm, d)
        cl = {"period": "period", "fertile": "fertile", "ovulation": "ovul", "pms": "pms",
              "follicular": "norm", "luteal": "norm"}[ph]
        extra = " today" if d == 27 else ""
        if ph in ("ovulation",):
            extra += " ovul"
        bar = f'<div class="bar"></div>' if cl in ("period", "fertile", "ovul", "pms") else '<div class="bar" style="opacity:.25"></div>'
        rose = '<div class="tiny" style="font-size:8.5px;color:#9C93AE">رزرو</div>' if d in (28, 29) else ''
        cells.append(f'<div class="cell {cl}{extra}"><div class="fa-num">{fa(d)}</div>{bar}</div>')
    grid = "".join(cells)
    head = "".join(f'<span>{w}</span>' for w in FAQ)
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="col" style="gap:2px"><div class="h1" style="font-size:19px">تقویم چرخه</div>
    <div class="small">پیش‌بینی‌شده تا ۳ چرخهٔ آینده</div></div>
    <div class="row gap8">{icon('filter',20,'','#C9C2D6')}{icon('search',20,'','#C9C2D6')}</div>
  </div>
  <div class="glass pad" style="padding:14px 12px">
    <div class="row between" style="margin-bottom:12px">
      <div class="ic n" style="width:32px;height:32px;border-radius:11px">{icon('chevL',17,'','#E6DDF2')}</div>
      <div class="col center" style="gap:1px"><div class="h2" style="font-size:15px">شهریور ۱۴۰۵</div>
      <div class="tiny">۲۸ روز چرخه • منظم</div></div>
      <div class="ic n" style="width:32px;height:32px;border-radius:11px">{icon('chev',17,'','#E6DDF2')}</div>
    </div>
    <div class="cal-head">{head}</div>
    <div class="cal-grid">{grid}</div>
  </div>
  <div class="glass soft pad row between" style="padding:12px 14px">
    <div class="col" style="gap:2px"><div class="tiny">چرخهٔ بعدی</div>
      <div class="h3" style="font-size:12.5px">قاعدگی: ۴ تا ۸ مهر ۱۴۰۵</div></div>
    <div class="chip p">۸ روز دیگر</div>
  </div>
  {legend([("#EA3F63", "قاعدگی"), ("#12A594", "پنجرهٔ باروری"), ("#8B6BF5", "تخمک‌گذاری"),
           ("#EE9A2E", "PMS"), ("#B07C2A", "لوتئال"), ("#3E86E8", "فولیکولی"), ("#D9B45B", "امروز")])}
  <div class="glass soft pad row gap10" style="padding:11px 13px">
    <div class="ic g" style="width:32px;height:32px;border-radius:11px">{icon('sparkle',17)}</div>
    <div class="col grow"><div class="tiny" style="line-height:1.8">پیش‌بینی هوشمند: با ۶ چرخهٔ ثبت‌شده، دقت مدل برای ۳ ماه آینده <b style="color:#FAEFC8">۹۶٪</b> است.</div></div>
  </div>
</div></div>
{tabbar("cal")}'''
    return page(body, title="۰۴ — تقویم")


# ---------------------------------------------------------------- 05 day detail
def s05_day():
    y, m, d = 1405, 6, 28
    probs = [("نوسان خلق و بی‌قراری", 78, "#EE9A2E"), ("خستگی زودتر از همیشه", 72, "#D98A22"),
             ("نفخ و سنگینی شکم", 64, "#B07C2A")]
    rows = "".join(f'''<div class="col" style="gap:6px">
      <div class="row between"><div class="small" style="color:#E4DDEB">{t}</div><div class="small fa-num">{fa(p)}٪</div></div>
      <div class="bar-track" style="height:6px"><i class="bar-fill" style="width:{p}%;background:linear-gradient(90deg,{c}88,{c})"></i></div>
    </div>''' for t, p, c in probs)
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="row gap10"><div class="ic n" style="width:34px;height:34px;border-radius:12px">{icon('chev',17,'','#E6DDF2')}</div>
      <div class="col" style="gap:1px"><div class="h2">۲۸ شهریور ۱۴۰۵</div><div class="tiny">شنبه • روز ۲۲ چرخه</div></div></div>
    <div class="chip pms">{icon('moon',14)} روز ۱ از ۷ PMS</div>
  </div>
  <div class="glass gold-line pad" style="background:linear-gradient(135deg,rgba(238,154,46,.20),rgba(255,255,255,.03))">
    <div class="row between" style="align-items:flex-start">
      <div class="col" style="gap:5px"><div class="h2" style="font-size:17px">فاز پیش از قاعدگی</div>
      <div class="small" style="line-height:1.8">پروژسترون بالا می‌رود و استروژن افت می‌کند؛ همین نوسان نشانه‌های PMS را می‌سازد.</div></div>
      <div class="dot m" style="margin-top:6px"></div>
    </div>
    <div class="divider" style="margin:11px 0"></div>
    <div class="row gap8" style="flex-wrap:wrap">
      <div class="chip dark">{icon('time',13)} تا قاعدگی: ۶ روز</div>
      <div class="chip dark">{icon('drop',13)} خونریزی: ندارد</div>
      <div class="chip dark">{icon('heart',13)} احتمال بارداری: کم</div>
    </div>
  </div>
  {section("احتمال تجربهٔ نشانه‌ها", "بر پایهٔ الگوی شخصی تو")}
  <div class="glass soft pad col" style="gap:11px">{rows}</div>
  {section("مراقبت امروز")}
  <div class="col" style="gap:8px">
    <div class="lrow"><div class="ic m">{icon('apple',18)}</div><div class="col grow"><div class="h3" style="font-size:12.5px">۳ وعدهٔ کوچک + میان‌وعدهٔ مغزی</div>
      <div class="tiny">قند خون پایدار، خلق پایدارتر</div></div>{icon('chev',17,'','#7C7191')}</div>
    <div class="lrow"><div class="ic f">{icon('activity',18)}</div><div class="col grow"><div class="h3" style="font-size:12.5px">۲۰ دقیقه حرکات کششی و پیاده‌روی</div>
      <div class="tiny">به‌جای تمرین سنگین امروز</div></div>{icon('chev',17,'','#7C7191')}</div>
    <div class="lrow"><div class="ic p">{icon('shield',18)}</div><div class="col grow"><div class="h3" style="font-size:12.5px">مراقبت پیشگیری</div>
      <div class="tiny">روزهای پرخطر نزدیک است؛ روش پیشگیری باید مطمئن باشد</div></div>{icon('chev',17,'','#7C7191')}</div>
  </div>
</div></div>
{homebar()}'''
    return page(body, title="۰۵ — جزئیات روز")


# ---------------------------------------------------------------- 06 log
def s06_log():
    moods = [("😄", 0), ("🙂", 1), ("😐", 0), ("😔", 1), ("😢", 0)]
    mood_html = "".join(f'<div class="emoj {"on" if s else ""}">{e}</div>' for e, s in moods)
    flows = ["بدون خونریزی", "لکه‌بینی", "سبک", "متوسط", "سنگین"]
    flow_html = "".join(f'<div class="chip {"p" if i == 0 else ""}" style="flex:1;justify-content:center;padding:0 4px;font-size:10.5px">{t}</div>' for i, t in enumerate(flows))
    syms = [("drop", "گرفتگی عضلات", 0), ("alert", "سردرد", 1), ("activity", "کمردرد", 0), ("moon", "خستگی", 1),
            ("heart", "حساسیت سینه", 1), ("bed", "بی‌خوابی", 0), ("apple", "نفخ", 1), ("smile", "تهوع", 0)]
    sym_html = "".join(f'<div class="sym {"on" if s else ""}">{icon(i,17)}{t}</div>' for i, t, s in syms)
    pain = "".join(f'<div style="flex:1;height:30px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:11.5px;font-weight:700;'
                   f'{"background:linear-gradient(160deg,rgba(234,63,99,.35),rgba(234,63,99,.14));border:1px solid rgba(234,63,99,.5);color:#FFDCE4" if i<=2 else "background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.07);color:#8C84A0"}'
                   f'">{fa(i)}</div>' for i in range(1, 6))
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="col" style="gap:1px"><div class="h1" style="font-size:19px">ثبت وضعیت امروز</div>
      <div class="small">جمعه ۲۷ شهریور • روز ۲۱</div></div>
    <div class="chip gold">{icon('sparkle',13)} ۶ روز پیاپی ثبت</div>
  </div>
  <div class="glass soft pad col" style="gap:10px">
    <div class="row between"><div class="h3" style="font-size:12.5px">حال امروزت چطوره؟</div><div class="tiny">الزامی نیست</div></div>
    <div class="row between">{mood_html}</div>
  </div>
  <div class="glass soft pad col" style="gap:10px">
    <div class="row between"><div class="h3" style="font-size:12.5px">خونریزی</div><div class="tiny">روز ۲۱ چرخه • بدون خونریزی</div></div>
    <div class="row gap8">{flow_html}</div>
  </div>
  <div class="glass soft pad col" style="gap:10px">
    <div class="row between"><div class="h3" style="font-size:12.5px">نشانه‌ها</div><div class="tiny">۴ مورد انتخاب شد</div></div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">{sym_html}</div>
  </div>
  <div class="glass soft pad col" style="gap:11px">
    <div class="row between"><div class="h3" style="font-size:12.5px">شدت درد</div><div class="chip pms" style="height:24px;font-size:10.5px">متوسط</div></div>
    <div class="row gap8">{pain}</div>
  </div>
  <div class="lrow" style="padding:12px 13px"><div class="ic g">{icon('clip',18)}</div>
    <div class="col grow"><div class="h3" style="font-size:12.5px">یادداشت خصوصی</div>
      <div class="tiny">«امروز تمرکزم کم بود و عصر سردرد گرفتم…»</div></div>{icon('chevL',17,'','#7C7191')}</div>
  <div class="pnote good" style="padding:9px 11px">{icon('lock',15,'','#71E0D1')}
    <div class="tiny" style="line-height:1.7;color:#BFEDE6">این یادداشت فقط برای تو ذخیره می‌شود و هرگز برای همسرت نمایش داده نمی‌شود.</div></div>
  <div class="btn gold wide">ذخیره در دفترچهٔ سلامت</div>
</div></div>
{homebar()}'''
    return page(body, title="۰۶ — ثبت")


# ---------------------------------------------------------------- 07 insights
def s07_insights():
    cycles = [("فروردین", 28), ("اردیبهشت", 29), ("خرداد", 28), ("تیر", 30), ("مرداد", 29), ("شهریور", 28)]
    bars = "".join(f'''<div class="col center" style="gap:6px;flex:1">
      <div class="col center" style="height:70px;justify-content:flex-end">
        <div style="width:13px;height:{(v-24)/7*52:.0f}px;border-radius:7px;background:linear-gradient(180deg,#FDEFC4,#C79A3E);opacity:{.55+i*.075}"></div></div>
      <div class="tiny fa-num">{fa(v)}</div><div class="tiny" style="font-size:8.5px">{n}</div></div>''' for i, (n, v) in enumerate(cycles))
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="col" style="gap:1px"><div class="h1" style="font-size:19px">تحلیل هوشمند</div>
      <div class="small">بر پایهٔ ۶ چرخهٔ ثبت‌شده</div></div>
    <div class="chip gold">{icon('sparkle',13)} نسخهٔ پیشرفته</div>
  </div>
  <div class="glass gold-line pad col" style="gap:12px">
    <div class="row between">
      <div class="col" style="gap:3px"><div class="tiny">میانگین طول چرخه</div>
        <div class="row gap8" style="align-items:baseline"><div class="kpi fa-num">۲۹</div><div class="small">روز</div></div></div>
      <div class="col center" style="gap:4px"><div class="ring-wrap">{ring_svg(C, 74, 8, 21)}
        <div class="ring-center"><div style="font-size:13px;font-weight:800" class="gold-text fa-num">۹۶٪</div></div></div>
        <div class="tiny">دقت پیش‌بینی</div></div>
    </div>
    <div class="row" style="align-items:flex-end;gap:6px;height:84px">{bars}</div>
    <div class="divider" style="margin:2px 0"></div>
    <div class="row between">
      <div class="col" style="gap:2px"><div class="tiny">نوسان چرخه</div><div class="h3" style="font-size:12.5px">±۱ روز • منظم</div></div>
      <div class="col" style="gap:2px"><div class="tiny">طول قاعدگی</div><div class="h3" style="font-size:12.5px">۵ روز</div></div>
      <div class="col" style="gap:2px"><div class="tiny">طول لوتئال</div><div class="h3" style="font-size:12.5px">۱۴ روز</div></div>
    </div>
  </div>
  {section("الگوی شخصی تو", "یادگیری ماشینی روی داده‌های خودت")}
  <div class="glass soft pad col" style="gap:11px">
    <div class="lrow" style="background:rgba(255,255,255,.03)"><div class="ic m">{icon('moon',18)}</div>
      <div class="col grow"><div class="h3" style="font-size:12.5px">PMS تو از روز ۲۲ شروع می‌شود</div>
      <div class="tiny">۳ روز زودتر از میانگین؛ پس هشدارها را جلو می‌کشیم</div></div></div>
    <div class="lrow" style="background:rgba(255,255,255,.03)"><div class="ic p">{icon('drop',18)}</div>
      <div class="col grow"><div class="h3" style="font-size:12.5px">غالب‌ترین نشانه‌ها: نفخ ۷۲٪، خستگی ۶۸٪</div>
      <div class="tiny">با کاهش نمک و خواب ۷ ساعت، ۴۱٪ کمتر شده‌ای</div></div></div>
  </div>
  <div class="glass gold-line pad col" style="gap:8px;background:linear-gradient(135deg,rgba(217,180,91,.14),rgba(255,255,255,.03))">
    <div class="row gap8"><div class="ic g" style="width:30px;height:30px;border-radius:10px">{icon('sparkle',16)}</div>
      <div class="h3" style="font-size:12.5px">پیش‌بینی این چرخه</div></div>
    <div class="body" style="font-size:11.5px;color:#EDE6F5">
      شروع قاعدگی بعدی با احتمال <b class="gold-text">۹۲٪</b> بین <b>۴ تا ۶ مهر</b> است.
      بهترین روزهای انرژی تو: <b>۹ تا ۱۳ مهر</b>.</div>
  </div>
</div></div>
{tabbar("chart")}'''
    return page(body, title="۰۷ — تحلیل")


# ---------------------------------------------------------------- 08 cycle stats
def s08_cycle():
    rows = [("#EA3F63", "قاعدگی", "۷ تا ۱۱ شهریور", "۵ روز", "شدت: متوسط"),
            ("#3E86E8", "فولیکولی", "۱۲ تا ۱۴ شهریور", "۳ روز", "انرژی در حال بازگشت"),
            ("#12A594", "پنجرهٔ باروری", "۱۵ تا ۲۱ شهریور", "۷ روز", "پُراحتمال‌ترین روزها"),
            ("#8B6BF5", "تخمک‌گذاری", "۲۰ شهریور", "۱ روز", "بالاترین احتمال بارداری"),
            ("#B07C2A", "لوتئال", "۲۲ تا ۲۷ شهریور", "۶ روز", "پروژسترون بالا"),
            ("#EE9A2E", "PMS", "۲۸ شهریور تا ۳ مهر", "۶ روز", "آماده‌سازی و مراقبت")]
    tiles = "".join(f'''<div class="glass soft col" style="padding:11px 12px;gap:5px">
      <div class="row between"><div class="row gap7" style="gap:7px"><div class="dot" style="background:{c};box-shadow:0 0 8px {c}88"></div>
      <div class="h3" style="font-size:12px">{t}</div></div><div class="tiny" style="font-size:9.5px">{n}</div></div>
      <div class="tiny" style="font-size:10px;line-height:1.7">{rng}</div>
      <div class="tiny" style="font-size:9px;line-height:1.6;color:#8E86A3">{d}</div></div>''' for c, t, rng, n, d in rows)
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="col" style="gap:1px"><div class="h1" style="font-size:19px">چرخهٔ من</div>
      <div class="small">شهریور ۱۴۰۵ • ۲۸ روزه</div></div>
    <div class="chip gold">{icon('moon',13)} فاز فعلی: لوتئال</div>
  </div>
  <div class="glass pad-lg col center" style="gap:10px">
    <div class="ring-wrap">{ring_svg(C, 158, 12, 21)}
      <div class="ring-center" style="gap:3px">
        <div class="tiny">روز چرخه</div>
        <div style="font-size:34px;font-weight:800" class="gold-text fa-num">۲۱</div>
        <div class="chip dark" style="height:24px;font-size:10px">لوتئال</div>
        <div class="tiny">۸ روز تا قاعدگی</div>
      </div></div>
    <div class="row gap12" style="width:100%;justify-content:center">
      <div class="col center" style="gap:2px"><div class="kpi fa-num" style="font-size:16px">۲۸</div><div class="tiny">طول چرخه</div></div>
      <div style="width:1px;height:28px;background:rgba(255,255,255,.10)"></div>
      <div class="col center" style="gap:2px"><div class="kpi fa-num" style="font-size:16px">۵</div><div class="tiny">روز قاعدگی</div></div>
      <div style="width:1px;height:28px;background:rgba(255,255,255,.10)"></div>
      <div class="col center" style="gap:2px"><div class="kpi fa-num" style="font-size:16px">۱۴</div><div class="tiny">روز لوتئال</div></div>
    </div>
  </div>
  {section("نقشهٔ کامل چرخه", "شمسی • زمان‌بندی دقیق")}
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">{tiles}</div>
  <div class="pnote warn" style="padding:9px 11px">{icon('alert',15,'','#FFC271')}
    <div class="tiny" style="line-height:1.75;color:#FFE3BE">تاریخ‌ها بر پایهٔ میانگین چرخهٔ تو تخمین زده شده‌اند؛ برای پیشگیری مطمئن، تنها به تقویم تکیه نکن.</div></div>
</div></div>
{tabbar("user")}'''
    return page(body, title="۰۸ — چرخه")


# ---------------------------------------------------------------- 09 health center
def s09_health():
    cats = [("drop", "p", "آموزش قاعدگی"), ("shield", "f", "پیشگیری و سلامت جنسی"),
            ("apple", "m", "تغذیه و ورزش"), ("smile", "o", "سلامت روان"),
            ("alert", "p", "علائم هشدار"), ("heart", "g", "بارداری و آمادگی")]
    cats_html = "".join(f'''<div class="glass soft col center" style="padding:12px 6px;gap:7px">
      <div class="ic {c}" style="width:34px;height:34px;border-radius:12px">{icon(i,17)}</div>
      <div class="tiny" style="font-size:10px;text-align:center;line-height:1.6;color:#D8D1E2">{t}</div></div>''' for i, c, t in cats)
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="col" style="gap:1px"><div class="h1" style="font-size:19px">مرکز سلامت</div>
      <div class="small">محتوای تأییدشده توسط متخصصان</div></div>
    <div class="ic n" style="width:34px;height:34px;border-radius:12px">{icon('search',18,'','#E6DDF2')}</div>
  </div>
  <div class="glass pad col" style="gap:10px;background:linear-gradient(135deg,rgba(139,107,245,.20),rgba(255,255,255,.03))">
    <div class="row between"><div class="chip gold">{icon('star',13)} ویژهٔ این هفته</div>
      <div class="chip dark">{icon('time',13)} ۸ دقیقه</div></div>
    <div class="col" style="gap:5px">
      <div class="h2" style="font-size:15px">پیشگیری مطمئن: مقایسهٔ ۹ روش</div>
      <div class="body" style="font-size:11px">کاندوم، قرص، آی‌یو‌دی، ایمپلنت، آمپول، NFP و… با درصد اثر، عوارض و مناسب‌بودن برای تو.</div></div>
    <div class="row gap8"><div class="btn gold sm grow">مطالعهٔ راهنما</div><div class="btn ghost sm" style="width:92px">ذخیره</div></div>
  </div>
  {section("دسته‌بندی آموزش‌ها")}
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:9px">{cats_html}</div>
  <div class="glass soft pad col" style="gap:9px">
    <div class="row gap8"><div class="ic p" style="width:30px;height:30px;border-radius:10px">{icon('alert',16)}</div>
      <div class="h3" style="font-size:12.5px;color:#FFB9C7">کِی باید به پزشک مراجعه کنی؟</div></div>
    <div class="col" style="gap:6px">
      <div class="row gap8"><div class="dot p" style="margin-top:6px"></div><div class="tiny" style="line-height:1.8">خونریزی بیش از ۷ روز یا لخته‌های بزرگ</div></div>
      <div class="row gap8"><div class="dot p" style="margin-top:6px"></div><div class="tiny" style="line-height:1.8">دردی که با مسکن معمولی آرام نمی‌شود</div></div>
    </div>
  </div>
  <div class="lrow"><div class="ic o">{icon('chat',18)}</div>
    <div class="col grow"><div class="h3" style="font-size:12.5px">پرسش از متخصص زنان</div>
      <div class="tiny">پاسخ محرمانه، میانگین ۴ ساعت</div></div>{icon('chev',17,'','#7C7191')}</div>
</div></div>
{tabbar("user")}'''
    return page(body, title="۰۹ — مرکز سلامت")
