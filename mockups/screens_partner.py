# -*- coding: utf-8 -*-
"""Screens 10-13 — بخش همسر / حالت همراه."""
from jalali import Cycle, fa, add_days, weekday, FAQ
from components import (icon, statusbar, tabbar, homebar, header, section, logo,
                        ring_svg, qr_svg, water, legend)
from screens_woman import page

C = Cycle()


# ---------------------------------------------------------------- 10 link partner
def s10_link():
    toggles = [("اطلاع دو روز قبل از شروع PMS", "پیش‌هشدار برای آماده‌سازی", True),
               ("خبر شروع و پایان قاعدگی", "روز اول + روز پایانی", True),
               ("پیشنهادهای حمایت روزانه", "سه کار کوچک و مؤثر", True),
               ("پنجرهٔ باروری و نشانه‌های من", "همیشه خصوصی می‌ماند", False)]
    tg = "".join(f'''<div class="lrow" style="padding:10px 12px">
      <div class="col grow" style="gap:2px"><div class="h3" style="font-size:12px">{t}</div>
      <div class="tiny">{s}</div></div>
      <div class="toggle {'on' if v else ''}"><i></i></div></div>''' for t, s, v in toggles)
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="row gap10"><div class="ic n" style="width:34px;height:34px;border-radius:12px">{icon('chev',17,'','#E6DDF2')}</div>
      <div class="col" style="gap:1px"><div class="h2">همراهی همسر</div><div class="tiny">دسترسی محدود و محترمانه</div></div></div>
    <div class="chip gold">{icon('lock',13)} کنترل کامل با تو</div>
  </div>
  <div class="glass gold-line pad col" style="gap:12px">
    <div class="row gap12" style="align-items:center">
      <div class="qr" style="width:104px;height:104px;padding:7px">{qr_svg(size=90)}</div>
      <div class="col grow" style="gap:6px">
        <div class="h2" style="font-size:14px">آرش را به چرخه‌ات وصل کن</div>
        <div class="tiny" style="line-height:1.85">او فقط زمان‌های حساس و راهِ حمایت را می‌بیند؛ نشانه‌ها، یادداشت‌ها و سلامت جنسی تو هرگز به اشتراک گذاشته نمی‌شود.</div>
      </div>
    </div>
    <div class="row gap8">
      <div class="chip gold grow" style="justify-content:center;font-size:10.5px">{icon('link',13)} mahbanu.app/i/NEGAR-7F3A</div>
      <div class="btn xs ghost">کپی</div>
    </div>
    <div class="row gap8">
      <div class="btn gold sm grow">{icon('share',16,'','#2A1D04',2.1)} ارسال دعوت‌نامه</div>
      <div class="btn ghost sm" style="width:96px">{icon('phone',16)} پیامک</div>
    </div>
    <div class="tiny" style="text-align:center">لینک دعوت تا ۷۲ ساعت اعتبار دارد و هر زمان می‌توانی لغوش کنی.</div>
  </div>
  {section("چه چیزی به اشتراک گذاشته شود؟", "۴ مورد")}
  <div class="col" style="gap:8px">{tg}</div>
</div></div>
{homebar()}'''
    return page(body, title="۱۰ — اتصال همسر", partner=True)


# ---------------------------------------------------------------- 11 partner home
def s11_partner_home():
    days = []
    C_ = C
    for i in range(-1, 7):
        y, m, d = add_days(1405, 6, 27, i)
        ph = C_.phase(y, m, d)
        col = {"period": "#EA3F63", "pms": "#EE9A2E", "fertile": "#12A594", "ovulation": "#8B6BF5"}.get(ph, "rgba(255,255,255,.14)")
        wd = FAQ[weekday(y, m, d)]
        is_today = i == 0
        days.append(f'''<div class="col center" style="gap:6px;flex:1">
          <div class="tiny" style="font-size:9px">{wd}</div>
          <div class="col center" style="width:36px;height:52px;border-radius:13px;gap:4px;justify-content:center;
            background:{col}33;border:1px solid {col}{'FF' if is_today else '66'}">{'<div class="tiny" style="font-size:8px;color:#FFF">امروز</div>' if is_today else ''}
            <div class="fa-num" style="font-size:12.5px;font-weight:700">{fa(d)}</div></div></div>''')
    strip = "".join(days)
    tasks = [("gift", "یک تکه شکلات تلخ بگیر", "منیزیم و منشأ شادی — کوچک اما دقیق"),
             ("bath", "یک ساعت آرامش و سکوت بده", "صدای کم، نور ملایم، بدون سؤال")]
    trows = "".join(f'''<div class="lrow" style="padding:11px 12px"><div class="ic g">{icon(i,18)}</div>
      <div class="col grow" style="gap:2px"><div class="h3" style="font-size:12.5px">{t}</div><div class="tiny">{s}</div></div>
      <div class="toggle on"><i></i></div></div>''' for i, t, s in tasks)
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="row gap10"><div class="avatar ring">آ</div>
      <div class="col" style="gap:1px"><div style="font-size:15px;font-weight:700">سلام آرش</div>
      <div class="small">جمعه ۲۷ شهریور • همراهِ ماه</div></div></div>
    <div style="position:relative">{icon('bell',22,'','#E6DDF2')}<span class="badge" style="position:absolute;top:-5px;left:-4px">۲</span></div>
  </div>
  <div class="glass gold-line pad col" style="gap:12px;background:linear-gradient(135deg,rgba(238,154,46,.20),rgba(255,255,255,.03))">
    <div class="row between">
      <div class="row gap9"><div class="ic m" style="width:34px;height:34px;border-radius:12px">{icon('moon',17)}</div>
        <div class="col" style="gap:2px"><div class="h3" style="font-size:13px">نگار از فردا وارد PMS می‌شود</div>
        <div class="tiny">۲۸ شهریور تا ۳ مهر • ۶ روز</div></div></div>
    </div>
    <div class="row gap8" style="flex-wrap:wrap">
      <div class="chip dark">{icon('heart',13)} نیاز به آرامش و درک بیشتر</div>
      <div class="chip dark">{icon('clock',13)} انرژی پایین‌تر از معمول</div>
    </div>
    <div class="btn gold sm wide">آمدن این روزها را یادآوری کن</div>
  </div>
  {section("۷ روز آیندهٔ نگار", "خصوصی: نشانه‌ها نمایش داده نمی‌شود")}
  <div class="glass soft pad" style="padding:13px 10px">
    <div class="row" style="gap:4px;align-items:flex-start">{strip}</div>
    <div class="divider" style="margin:11px 0 9px"></div>
    {legend([("#EE9A2E", "PMS"), ("#EA3F63", "قاعدگی"), ("rgba(255,255,255,.2)", "روز عادی")])}
  </div>
  <div class="glass soft pad row between" style="padding:12px 14px">
    <div class="col" style="gap:2px"><div class="tiny">شروع قاعدگی بعدی</div>
      <div class="h3" style="font-size:12.5px">۴ مهر ۱۴۰۵</div></div>
    <div class="chip gold">{icon('time',13)} ۸ روز دیگر</div>
  </div>
  {section("کارهای کوچک امروز", "پیشنهاد هوشمند")}
  <div class="col" style="gap:8px">{trows}</div>
</div></div>
{tabbar("home", partner=True)}'''
    return page(body, title="۱۱ — خانهٔ همسر", partner=True)


# ---------------------------------------------------------------- 12 partner alert
def s12_alert():
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="col" style="gap:1px"><div class="h1" style="font-size:19px">یادآور مهربانی</div>
      <div class="small">جمعه ۲۷ شهریور • ساعت ۹:۴۱</div></div>
    <div class="chip pms">{icon('moon',13)} PMS از فردا</div>
  </div>
  <div class="glass gold-line pad col" style="gap:12px;background:linear-gradient(135deg,rgba(217,180,91,.16),rgba(234,63,99,.10))">
    <div class="row gap9"><div class="ic g" style="width:32px;height:32px;border-radius:11px">{icon('chat',16)}</div>
      <div class="col"><div class="h3" style="font-size:12.5px">پیام ماه‌بانو</div><div class="tiny">با اجازهٔ نگار • بدون نمایش جزئیات خصوصی</div></div></div>
    <div class="col" style="gap:8px">
      <div class="body" style="font-size:12.5px;line-height:2;color:#F2ECF8">
        «آرش عزیز، نگار از <b>فردا ۲۸ شهریور</b> تا <b>۳ مهر</b> در فاز پیش از قاعدگی است.
        ممکن است خلقش نوسان داشته باشد، زودتر خسته شود و به آرامش بیشتری نیاز داشته باشد.
        لازم نیست چیزی را حل کنی — همین که کنارش باشی و حمایتش کنی، کافی است. 🤍»</div>
      <div class="row gap8" style="flex-wrap:wrap">
        <div class="chip dark">{icon('time',13)} ۶ روز</div>
        <div class="chip dark">{icon('heart',13)} نیاز اصلی: آرامش</div>
        <div class="chip dark">{icon('bed',13)} خواب بیشتر</div>
      </div>
    </div>
    <div class="divider" style="margin:2px 0"></div>
    <div class="row gap8">
      <div class="btn gold sm grow">{icon('check',16,'','#2A1D04',2.2)} فهمیدم، حواسم هست</div>
      <div class="btn ghost sm" style="width:112px">{icon('time',15)} یادآوری فردا</div>
    </div>
  </div>
  {section("همین امروز، این سه کار را انجام بده")}
  <div class="row gap10">
    <div class="glass soft pad col grow" style="gap:8px">
      <div class="ic m" style="width:32px;height:32px;border-radius:11px">{icon('coffee',16)}</div>
      <div class="h3" style="font-size:12px">آرام باش</div>
      <div class="tiny" style="line-height:1.8">صدای خانه را کم کن؛ بدون بازجویی بپرس: «چیزی لازم داری؟»</div></div>
    <div class="glass soft pad col grow" style="gap:8px">
      <div class="ic f" style="width:32px;height:32px;border-radius:11px">{icon('gift',16)}</div>
      <div class="h3" style="font-size:12px">کمک کن</div>
      <div class="tiny" style="line-height:1.8">کارهای خانه را بدون یادآوری انجام بده؛ شامِ سبک و گرم.</div></div>
  </div>
  <div class="glass soft pad col" style="gap:9px">
    <div class="row gap8"><div class="ic o" style="width:30px;height:30px;border-radius:10px">{icon('activity',16)}</div>
      <div class="h3" style="font-size:12.5px">حواست باشد، ولی تبدیل به مراقب نشو</div></div>
    <div class="col" style="gap:6px">
      <div class="row gap8"><div class="dot" style="background:#71E0D1;margin-top:6px"></div><div class="tiny" style="line-height:1.8">بپرس کِی بهتره حرف بزنیم، نه اینکه «چرا این‌طوری شدی؟»</div></div>
      <div class="row gap8"><div class="dot" style="background:#71E0D1;margin-top:6px"></div><div class="tiny" style="line-height:1.8">پیشنهاد پیاده‌روی مشترک بده؛ حرکت، نشانه‌های PMS را کم می‌کند.</div></div>
    </div>
  </div>
  <div class="btn ghost wide">خواندن راهنمای کامل «چطور کنارش باشم؟»</div>
</div></div>
{tabbar("chat", partner=True)}'''
    return page(body, title="۱۲ — پیام حمایت", partner=True)


# ---------------------------------------------------------------- 13 calm guide
def s13_calm():
    say = ["«می‌فهمم حالت خوب نیست؛ من هستم.»", "«استراحت کن، کارها را من انجام می‌دهم.»",
           "«دوست داری تنها باشی یا کنارت باشم؟»"]
    dont = ["«چرا این‌قدر حساس شدی؟»", "«باز هم پریودت شد؟»", "«بی‌خودی بهانه می‌گیری.»"]
    good = "".join(f'''<div class="row gap8" style="align-items:flex-start"><div class="ic f" style="width:24px;height:24px;border-radius:8px">{icon('check',14)}</div>
      <div class="tiny" style="line-height:1.9;color:#CFF3ED">{t}</div></div>''' for t in say)
    bad = "".join(f'''<div class="row gap8" style="align-items:flex-start"><div class="ic p" style="width:24px;height:24px;border-radius:8px">{icon('alert',13)}</div>
      <div class="tiny" style="line-height:1.9;color:#FFD8E1">{t}</div></div>''' for t in dont)
    body = f'''<div class="content"><div class="scroll col" style="gap:9px">
  <div class="row between" style="margin-top:6px">
    <div class="col" style="gap:1px"><div class="h1" style="font-size:19px">حالت آرامش</div>
      <div class="small">راهنمای ۷ روز پیش از قاعدگی</div></div>
    <div class="chip gold">{icon('heart',13)} نگار</div>
  </div>
  <div class="glass gold-line pad col" style="gap:11px">
    <div class="row between"><div class="h3" style="font-size:13px">۵ کار مؤثر امشب</div><div class="tiny">۸۰٪ همسران نتیجه گرفته‌اند</div></div>
    <div class="row" style="gap:8px">
      {''.join(f'<div class="col center grow" style="gap:6px"><div class="ic {c}" style="width:34px;height:34px;border-radius:12px">{icon(i,16)}</div><div class="tiny" style="font-size:9px;text-align:center;line-height:1.5">{t}</div></div>' for i, c, t in [("coffee","m","چای گرم"),("bath","f","حمام گرم"),("activity","o","ماساژ شانه"),("apple","p","غذای سبک"),("bed","g","خواب زودتر")])}
    </div>
    <div class="divider" style="margin:2px 0"></div>
    <div class="row between"><div class="tiny">امشب را تیک بزن</div><div class="chip dark">{icon('check',13)} ۲ از ۵ انجام شد</div></div>
  </div>
  <div class="glass soft pad col" style="gap:10px">
    <div class="h3" style="font-size:12.5px;color:#8FE6D8">چه بگویی که حالش بهتر شود</div>
    <div class="col" style="gap:7px">{good}</div>
  </div>
  <div class="glass soft pad col" style="gap:10px">
    <div class="h3" style="font-size:12.5px;color:#FF9FB4">چه چیزهایی نگو</div>
    <div class="col" style="gap:7px">{bad}</div>
  </div>
  <div class="glass soft pad col" style="gap:10px">
    <div class="row between"><div class="h3" style="font-size:12.5px">کمک عملی این هفته</div>
      <div class="tiny">اختیاری</div></div>
    <div class="row gap8" style="flex-wrap:wrap">
      <div class="chip">{icon('gift',13)} خرید داروخانه</div>
      <div class="chip">{icon('apple',13)} تهیهٔ میوه</div>
      <div class="chip">{icon('walk',13)} پیاده‌روی کوتاه</div>
      <div class="chip">{icon('clock',13)} نیم ساعت در سکوت</div>
    </div>
  </div>
</div></div>
{tabbar("book", partner=True)}'''
    return page(body, title="۱۳ — حالت آرامش", partner=True)
