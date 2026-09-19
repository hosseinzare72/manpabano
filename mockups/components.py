# -*- coding: utf-8 -*-
"""Shared UI components for ماه‌بانو mockups."""

from jalali import fa, PHASES

# ------------------------------------------------------------------ icon set
ICONS = {
 "home":   '<path d="M3 10.8 12 3.4l9 7.4V20a1 1 0 0 1-1 1h-5.2v-6.1H9.2V21H4a1 1 0 0 1-1-1z"/>',
 "cal":    '<rect x="3" y="4.6" width="18" height="16.4" rx="3"/><path d="M3 9.4h18M8 3v3.2M16 3v3.2"/>',
 "plus":   '<path d="M12 5v14M5 12h14"/>',
 "chart":  '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
 "user":   '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20.5c.9-3.9 4-6 7.5-6s6.6 2.1 7.5 6"/>',
 "heart":  '<path d="M12 20.3S3.6 15.2 3.6 9.4A4.6 4.6 0 0 1 12 6.7a4.6 4.6 0 0 1 8.4 2.7c0 5.8-8.4 10.9-8.4 10.9z"/>',
 "bell":   '<path d="M18 15.6V11a6 6 0 1 0-12 0v4.6L4.4 18h15.2z"/><path d="M10 21h4"/>',
 "shield": '<path d="M12 3.2 5 6v5.8c0 4.3 3 7.6 7 9 4-1.4 7-4.7 7-9V6z"/><path d="m9.2 12.2 2 2 3.6-4"/>',
 "drop":   '<path d="M12 3.4s5.6 5.6 5.6 9.6A5.6 5.6 0 0 1 12 20.6a5.6 5.6 0 0 1-5.6-5.6c0-4 5.6-9.6 5.6-9.6z"/>',
 "sparkle":'<path d="M12 3.2l1.9 5.2 5.2 1.9-5.2 1.9L12 17.4l-1.9-5.2L4.9 10.3l5.2-1.9z"/><path d="M18.4 16.2l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/>',
 "moon":   '<path d="M20 14.4A8.4 8.4 0 0 1 9.6 4a8.6 8.6 0 1 0 10.4 10.4z"/>',
 "flower": '<circle cx="12" cy="12" r="2.6"/><path d="M12 9.4c0-3 .7-4.6 0-6.6-.7 2-2.6 3.4-2.6 6.6M12 14.6c0 3-.7 4.6 0 6.6.7-2 2.6-3.4 2.6-6.6M9.4 12c-3 0-4.6-.7-6.6 0 2 .7 3.4 2.6 6.6 2.6M14.6 12c3 0 4.6.7 6.6 0-2-.7-3.4-2.6-6.6-2.6"/>',
 "book":   '<path d="M4 5.2A2.2 2.2 0 0 1 6.2 3H19v15.6H6.2A2.2 2.2 0 0 0 4 20.8z"/><path d="M19 18.6v2.6H6.2A2.2 2.2 0 0 1 4 19"/>',
 "chat":   '<path d="M20.5 12c0 4.1-3.8 7.4-8.5 7.4-1 0-2-.2-2.9-.5L4 20.5l1.4-3.6A7 7 0 0 1 3.5 12C3.5 7.9 7.3 4.6 12 4.6s8.5 3.3 8.5 7.4z"/>',
 "link":   '<path d="M10.4 13.6a4.4 4.4 0 0 0 6.2 0l2.6-2.6a4.4 4.4 0 0 0-6.2-6.2l-.9.9"/><path d="M13.6 10.4a4.4 4.4 0 0 0-6.2 0l-2.6 2.6a4.4 4.4 0 0 0 6.2 6.2l.9-.9"/>',
 "lock":   '<rect x="4.6" y="10" width="14.8" height="10.6" rx="3"/><path d="M8.2 10V7.6a3.8 3.8 0 0 1 7.6 0V10"/>',
 "check":  '<path d="m5 12.6 4.4 4.4L19 7.4"/>',
 "clock":  '<circle cx="12" cy="12" r="8.6"/><path d="M12 7.4V12l3.2 2"/>',
 "coffee": '<path d="M4.5 8h12v5.6a5 5 0 0 1-5 5h-2a5 5 0 0 1-5-5z"/><path d="M16.5 9.4h1.6a2.6 2.6 0 0 1 0 5.2h-1.6"/><path d="M5 20.6h11"/>',
 "bath":   '<path d="M4 12.6h16v2.8a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"/><path d="M7 12.6V6.8A2.8 2.8 0 0 1 12.6 6"/><path d="M6.5 19.4 6 21M17.5 19.4l.5 1.6"/>',
 "bed":    '<path d="M3.5 18.6v-12M3.5 12.4h17v6.2M20.5 12.4v-2.6a2 2 0 0 0-2-2h-6v4.6"/><circle cx="7.4" cy="9.4" r="1.9"/>',
 "activity":'<path d="M3 12.4h4l2.4-6 3.4 12 2.4-6h5.8"/>',
 "alert":  '<path d="M12 4.4 3.4 19.4h17.2z"/><path d="M12 10v4.2M12 17h.01"/>',
 "share":  '<path d="M12 16.4V4.6M8 8.4 12 4.4l4 4"/><path d="M5 14v4.4a1.6 1.6 0 0 0 1.6 1.6h10.8A1.6 1.6 0 0 0 19 18.4V14"/>',
 "search": '<circle cx="11" cy="11" r="6.6"/><path d="m16 16 4.4 4.4"/>',
 "chev":   '<path d="m14.5 7.5-5 4.5 5 4.5"/>',
 "chevL":  '<path d="m9.5 7.5 5 4.5-5 4.5"/>',
 "pill":   '<rect x="3.4" y="8.6" width="17.2" height="6.8" rx="3.4" transform="rotate(-45 12 12)"/><path d="m9.4 9.4 5.2 5.2"/>',
 "walk":   '<circle cx="13.4" cy="4.8" r="1.9"/><path d="M13 9.6 10.4 13l1.8 2.4.6 5.6M13 9.6l2.6 2 2.8.6M10.4 13l-3 1.6L6 19.4"/>',
 "apple":  '<path d="M12 8.4c-1.6-2-5-1.6-6.2.6-1.4 2.6.2 7.6 2.6 10.2 1 1.1 2.4 1 3.6.2 1.2.8 2.6.9 3.6-.2 2.4-2.6 4-7.6 2.6-10.2-1.2-2.2-4.6-2.6-6.2-.6z"/><path d="M12 8.4c.4-1.6 1.6-2.6 3.2-2.8"/>',
 "scale":  '<path d="M12 5.4v14.2M6 19.6h12"/><path d="M4 10.4h6.6M13.4 10.4H20"/><path d="M4 10.4a3.3 3.3 0 0 0 6.6 0zM13.4 10.4a3.3 3.3 0 0 0 6.6 0z"/><circle cx="12" cy="5.4" r="1.4"/>',
 "settings":'<circle cx="12" cy="12" r="3"/><path d="M12 3.4v2.2M12 18.4v2.2M4.8 7.8l1.9 1.1M17.3 15.1l1.9 1.1M4.8 16.2l1.9-1.1M17.3 8.9l1.9-1.1"/>',
 "target": '<circle cx="12" cy="12" r="8.4"/><circle cx="12" cy="12" r="4.4"/><circle cx="12" cy="12" r=".8"/>',
 "phone":  '<rect x="6.4" y="2.6" width="11.2" height="18.8" rx="3"/><path d="M10.4 5.4h3.2"/>',
 "star":   '<path d="m12 3.8 2.6 5.4 5.9.8-4.3 4.1 1.1 5.9L12 17.2 6.7 20l1.1-5.9L3.5 10l5.9-.8z"/>',
 "filter": '<path d="M4 6h16M7 12h10M10 18h4"/>',
 "send":   '<path d="M20.4 4.2 3.8 10.4l6 2.4 2.4 6z"/><path d="M20.4 4.2 9.8 12.8"/>',
 "time":   '<path d="M12 3.6a8.4 8.4 0 1 0 0 16.8 8.4 8.4 0 0 0 0-16.8z"/><path d="M12 8v4.4l2.8 1.8"/>',
 "sleep":  '<path d="M20.4 14.6A7.6 7.6 0 0 1 9.4 3.6a8 8 0 1 0 11 11z"/><path d="M14.6 5.6h4l-4 3.6h4"/>',
 "clip":   '<rect x="5.4" y="3.4" width="13.2" height="17.2" rx="2.6"/><path d="M9 7.6h6M9 11.6h6M9 15.6h3.5"/>',
 "gift":   '<rect x="3.6" y="9" width="16.8" height="11.4" rx="2.4"/><path d="M3.6 13.4h16.8M12 9V20.4"/><path d="M12 9S10.6 4 8.2 4a2 2 0 0 0 0 5zM12 9s1.4-5 3.8-5a2 2 0 0 1 0 5z"/>',
 "smile":  '<circle cx="12" cy="12" r="8.6"/><path d="M8.6 14.2a4.4 4.4 0 0 0 6.8 0"/><path d="M9.4 9.6h.01M14.6 9.6h.01"/>',
 "wifi":   '<path d="M5 12.6a10 10 0 0 1 14 0"/><path d="M7.8 15.4a6 6 0 0 1 8.4 0"/><path d="M10.6 18.2a2 2 0 0 1 2.8 0"/>',
 "signal": '<path d="M3 18.4v-3.2M8 18.4v-6.4M13 18.4V8.4M18 18.4V4.6"/>',
 "battery":'<rect x="2.6" y="8" width="16.4" height="8.8" rx="2.6"/><path d="M21 11.4v2.2"/><rect x="4.6" y="10" width="12.4" height="4.8" rx="1.4" fill="currentColor" stroke="none"/>',
}


def icon(name, size=20, cls="", color="currentColor", sw=1.8):
    return (f'<svg class="{cls}" viewBox="0 0 24 24" width="{size}" height="{size}" fill="none" '
            f'stroke="{color}" stroke-width="{sw}" stroke-linecap="round" stroke-linejoin="round">{ICONS[name]}</svg>')


# ------------------------------------------------------------------ chrome
def statusbar(time="۹:۴۱"):
    return f'''<div class="statusbar"><div class="row gap6 sb-icons">
      {icon('signal',15)} {icon('wifi',15)} {icon('battery',22)}</div>
      <div class="fa-num">{time}</div></div><div class="island"></div>'''


def tabbar(active="home", partner=False):
    if partner:
        items = [("home", "خانه"), ("cal", "تقویم"), ("chat", "پیام‌ها"), ("book", "راهنما")]
    else:
        items = [("home", "خانه"), ("cal", "تقویم"), ("chart", "تحلیل"), ("user", "من")]
    out = []
    for i, (k, label) in enumerate(items):
        if (not partner) and i == 2:
            out.append('<div class="col center" style="flex:1"><div class="fab">' + icon('plus', 26) + '</div></div>')
        out.append(f'<div class="tab {"active" if active == k else ""}">{icon(k, 21)}<span>{label}</span></div>')
    return '<div class="tabbar">' + "".join(out) + '</div>'


def homebar():
    return '<div class="homebar"></div>'


def header(title, sub=None, avatar="ن", bell=False, left_icon=None):
    h = f'''<div class="row between" style="margin-top:6px;margin-bottom:12px">
      <div class="row gap10">
        <div class="avatar ring">{avatar}</div>
        <div class="col"><div style="font-size:15px;font-weight:700">{title}</div>
        <div class="small">{sub or ""}</div></div></div>'''
    if bell:
        h += f'''<div style="position:relative">{icon('bell',22,'',"#E6DDF2")}<span class="badge" style="position:absolute;top:-5px;left:-4px">۳</span></div>'''
    elif left_icon:
        h += icon(left_icon, 22, '', "#E6DDF2")
    return h + "</div>"


def section(title, extra=None):
    e = f'<div class="tiny" style="color:var(--gold2);font-weight:600">{extra}</div>' if extra else ''
    return f'<div class="row between" style="margin:10px 2px 7px"><div class="h3">{title}</div>{e}</div>'


def legend(items):
    out = []
    for color, label in items:
        out.append(f'<div class="lg"><i style="background:{color}"></i>{label}</div>')
    return '<div class="legend">' + "".join(out) + '</div>'


# ------------------------------------------------------------------ graphics
def logo(size=110, rm=None):
    """Gold lotus-moon emblem."""
    return f'''<svg width="{size}" height="{size}" viewBox="0 0 120 120" fill="none">
  <defs>
    <linearGradient id="g1" x1="18" y1="12" x2="102" y2="110" gradientUnits="userSpaceOnUse">
      <stop stop-color="#FDF3D2"/><stop offset=".42" stop-color="#E3C273"/><stop offset="1" stop-color="#96701F"/></linearGradient>
    <linearGradient id="g2" x1="30" y1="24" x2="90" y2="96" gradientUnits="userSpaceOnUse">
      <stop stop-color="#FFD9E2" stop-opacity=".95"/><stop offset="1" stop-color="#8B6BF5" stop-opacity=".55"/></linearGradient>
  </defs>
  <circle cx="60" cy="60" r="52" stroke="url(#g1)" stroke-width="1.2" opacity=".55"/>
  <circle cx="60" cy="60" r="44" stroke="url(#g1)" stroke-width=".8" opacity=".3" stroke-dasharray="2 6"/>
  <path d="M60 22c10 12 12 26 0 38-12-12-10-26 0-38z" fill="url(#g1)" opacity=".95"/>
  <path d="M60 78c9 8 11 16 0 20-11-4-9-12 0-20z" fill="url(#g1)" opacity=".8"/>
  <path d="M34 40c12 2 20 10 22 22-12 2-20-6-22-22z" fill="url(#g2)" opacity=".9"/>
  <path d="M86 40c-12 2-20 10-22 22 12 2 20-6 22-22z" fill="url(#g2)" opacity=".9"/>
  <circle cx="60" cy="60" r="9.5" fill="#0C0713" stroke="url(#g1)" stroke-width="1.6"/>
  <circle cx="60" cy="60" r="3.4" fill="url(#g1)"/>
  <path d="M60 6.5v5M60 108.5v5M6.5 60h5M108.5 60h5" stroke="url(#g1)" stroke-width="1.2" opacity=".7"/>
</svg>'''


def ring_svg(cycle, size=190, stroke=13, current_day=None):
    """28-day phase ring: period → follicular → fertile(+ovulation) → luteal → PMS."""
    import math
    r = 50 - stroke / 2 - 1
    c = 2 * math.pi * r
    segs = [
        (1, cycle.period_len, "#EA3F63", "قاعدگی"),
        (cycle.period_len + 1, cycle.fertile_start - 1, "#3E86E8", "فولیکولی"),
        (cycle.fertile_start, cycle.ovulation_day - 1, "#12A594", "باروری"),
        (cycle.ovulation_day, cycle.ovulation_day, "#8B6BF5", "تخمک‌گذاری"),
        (cycle.ovulation_day + 1, cycle.fertile_end, "#12A594", "باروری"),
        (cycle.fertile_end + 1, cycle.pms_start - 1, "#B07C2A", "لوتئال"),
        (cycle.pms_start, cycle.cycle_len, "#EE9A2E", "PMS"),
    ]
    out = [f'<svg width="{size}" height="{size}" viewBox="0 0 100 100" style="transform:rotate(-90deg)">']
    out.append(f'<circle cx="50" cy="50" r="{r}" stroke="rgba(255,255,255,.07)" stroke-width="{stroke}" fill="none"/>')
    for a, b, col, _ in segs:
        n = b - a + 1
        dash = (n / cycle.cycle_len) * c
        gap = (0.55 / cycle.cycle_len) * c if n > 1 else 0
        off = -(((a - 1) / cycle.cycle_len) * c + gap / 2)
        out.append(f'<circle cx="50" cy="50" r="{r}" stroke="{col}" stroke-width="{stroke}" fill="none" '
                   f'stroke-linecap="round" stroke-dasharray="{max(dash - gap, .5):.2f} {c - max(dash - gap, .5):.2f}" '
                   f'stroke-dashoffset="{off:.2f}" opacity=".92" '
                   f'style="filter:drop-shadow(0 0 4px {col}66)"/>')
    if current_day:
        ang = ((current_day - 0.5) / cycle.cycle_len) * 2 * math.pi
        x = 50 + r * math.cos(ang)
        y = 50 + r * math.sin(ang)
        out.append(f'<g transform="rotate(90 50 50)"><circle cx="{x:.2f}" cy="{y:.2f}" r="4.6" fill="#0B0713" '
                   f'stroke="#FAEFC8" stroke-width="2.2"/></g>')
    out.append('</svg>')
    return "".join(out)


def qr_svg(text="mahbanu.app/i/NEGAR-7F3A", size=132):
    import hashlib
    n = 25
    mod = hashlib.sha256(text.encode()).digest()
    def bit(i):
        return (mod[i % 32] >> (i % 8)) & 1
    cells = []
    k = 0
    for y in range(n):
        for x in range(n):
            finder = ((x < 7 and y < 7) or (x > n - 8 and y < 7) or (x < 7 and y > n - 8))
            if finder:
                continue
            k += 1
            if bit(k) and (x + y) % 7 != 0:
                cells.append(f'<rect x="{x}" y="{y}" width="1" height="1" rx=".25"/>')
    def finder(ox, oy):
        return (f'<rect x="{ox}" y="{oy}" width="7" height="7" rx="1.6" fill="none" stroke="#0B0713" stroke-width="1"/>'
                f'<rect x="{ox+2}" y="{oy+2}" width="3" height="3" rx=".8"/>')
    return (f'<svg viewBox="0 0 {n} {n}" width="{size}" height="{size}" fill="#0B0713" shape-rendering="crispEdges">'
            + "".join(cells) + finder(0, 0) + finder(n - 7, 0) + finder(0, n - 7) + '</svg>')


def water(text="ماه‌بانو • طرح مفهومی UI"):
    return f'<div class="watermark">{text}</div>'
