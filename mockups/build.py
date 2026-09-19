# -*- coding: utf-8 -*-
"""Render every screen to PNG + report layout overflow."""
import os, pathlib, importlib
from playwright.sync_api import sync_playwright

import screens_woman as W
import screens_partner as P

ROOT = pathlib.Path("/home/user/mockups")
OUT = pathlib.Path("/home/user/screenshots")
OUT.mkdir(exist_ok=True)
(ROOT / "screens").mkdir(exist_ok=True)

SCREENS = [
    ("01-splash", W.s01_splash),
    ("02-onboarding", W.s02_onboarding),
    ("03-today-home", W.s03_home),
    ("04-calendar", W.s04_calendar),
    ("05-day-detail", W.s05_day),
    ("06-log-symptoms", W.s06_log),
    ("07-insights", W.s07_insights),
    ("08-cycle-map", W.s08_cycle),
    ("09-health-center", W.s09_health),
    ("10-partner-link", P.s10_link),
    ("11-partner-home", P.s11_partner_home),
    ("12-partner-alert", P.s12_alert),
    ("13-partner-calm", P.s13_calm),
]


def run(only=None):
    with sync_playwright() as pw:
        b = pw.chromium.launch()
        pg = b.new_page(viewport={"width": 390, "height": 844}, device_scale_factor=2)
        for name, fn in SCREENS:
            if only and name not in only:
                continue
            html = fn()
            f = ROOT / "screens" / f"{name}.html"
            f.write_text(html, encoding="utf-8")
            pg.goto(f.as_uri())
            pg.wait_for_timeout(350)
            info = pg.evaluate("""() => {
                const s = document.querySelector('.scroll');
                const r = {content: null, view: null};
                if (s) { r.content = s.scrollHeight; r.view = s.clientHeight; }
                const tb = document.querySelector('.tabbar');
                r.tabbar = tb ? tb.getBoundingClientRect().top : null;
                r.bodyH = document.body.scrollHeight;
                return r;
            }""")
            over = (info["content"] - info["view"]) if info["content"] else 0
            pg.screenshot(path=str(OUT / f"{name}.png"))
            print(f"{name:18s} content={info['content']} view={info['view']} overflow={over:+.0f}px")
        b.close()


if __name__ == "__main__":
    import sys
    only = sys.argv[1:] or None
    run(only)
