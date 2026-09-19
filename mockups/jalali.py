"""Jalali (Solar Hijri) <-> Gregorian conversion + cycle phase engine (mockup data layer)."""

JALALI_MONTHS = ["فروردین", "اردیبهشت", "خرداد", "تیر", "مرداد", "شهریور",
                 "مهر", "آبان", "آذر", "دی", "بهمن", "اسفند"]
WEEKDAYS_FA = ["شنبه", "یک‌شنبه", "دوشنبه", "سه‌شنبه", "چهارشنبه", "پنج‌شنبه", "جمعه"]
FAQ = ["ش", "ی", "د", "س", "چ", "پ", "ج"]


def greg_jdn(gy, gm, gd):
    a = (14 - gm) // 12
    y = gy + 4800 - a
    m = gm + 12 * a - 3
    return gd + (153 * m + 2) // 5 + 365 * y + y // 4 - y // 100 + y // 400 - 32045


def jdn_greg(j):
    a = j + 32044
    b = (4 * a + 3) // 146097
    c = a - (146097 * b) // 4
    d = (4 * c + 3) // 1461
    e = c - (1461 * d) // 4
    m = (5 * e + 2) // 153
    gd = e - (153 * m + 2) // 5 + 1
    gm = m + 3 - 12 * (m // 10)
    return 100 * b + d - 4800 + m // 10, gm, gd


def jal_jdn(jy, jm, jd):
    """Julian Day Number of a Jalali date (epoch 1/1/1 = JDN 1948320)."""
    y = jy - 1
    days = 1948320 + 365 * y + (y // 33) * 8 + ((y % 33) + 3) // 4
    days += (jm - 1) * 31 - (jm // 7) * (jm - 7) + jd - 1
    return days


def jdn_jal(j):
    lo, hi = 1000, 1700
    while lo < hi:
        mid = (lo + hi + 1) // 2
        if jal_jdn(mid, 1, 1) <= j:
            lo = mid
        else:
            hi = mid - 1
    jy = lo
    jm = 1
    while jm < 12 and jal_jdn(jy, jm + 1, 1) <= j:
        jm += 1
    return jy, jm, j - jal_jdn(jy, jm, 1) + 1


def g2j(gy, gm, gd):
    return jdn_jal(greg_jdn(gy, gm, gd))


def j2g(jy, jm, jd):
    return jdn_greg(jal_jdn(jy, jm, jd))


def jdn(jy, jm, jd):
    return jal_jdn(jy, jm, jd)


def add_days(jy, jm, jd, n):
    return jdn_jal(jal_jdn(jy, jm, jd) + n)


def weekday(jy, jm, jd):
    """0 = Saturday ... 6 = Friday (Persian week order)."""
    gy, gm, gd = j2g(jy, jm, jd)
    import datetime
    return (datetime.date(gy, gm, gd).weekday() + 2) % 7


def month_days(jy, jm):
    return [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29][jm - 1]


def fa(n):
    """Persian digits."""
    return str(n).translate(str.maketrans("0123456789", "۰۱۲۳۴۵۶۷۸۹"))


def fa_num(n):
    return f"{n:,}".translate(str.maketrans("0123456789,", "۰۱۲۳۴۵۶۷۸۹٬"))


# ---------------------------------------------------------------- cycle engine
class Cycle:
    """Deterministic phase engine following standard cycle physiology."""

    def __init__(self, lmp=(1405, 6, 7), cycle_len=28, period_len=5,
                 luteal=14, pms_days=7, today=(1405, 6, 27), variability=2):
        self.lmp = lmp
        self.cycle_len = cycle_len
        self.period_len = period_len
        self.luteal = luteal
        self.pms_days = pms_days
        self.today = today
        self.variability = variability
        self.ovulation_day = cycle_len - luteal           # typically day 14
        self.fertile_start = max(1, self.ovulation_day - 5)
        self.fertile_end = self.ovulation_day + 1
        self.pms_start = cycle_len - pms_days + 1         # day 22

    def day_index(self, jy, jm, jd):
        return (jdn(jy, jm, jd) - jdn(*self.lmp)) % self.cycle_len + 1

    def phase(self, jy, jm, jd):
        d = self.day_index(jy, jm, jd)
        if d <= self.period_len:
            return "period"
        if self.fertile_start <= d <= self.fertile_end:
            return "ovulation" if d == self.ovulation_day else "fertile"
        if d >= self.pms_start:
            return "pms"
        if d < self.ovulation_day:
            return "follicular"
        return "luteal"

    def class_of(self, jy, jm, jd):
        """CSS class used in the calendar/mockups."""
        return {"period": "period", "fertile": "fertile", "ovulation": "ovul",
                "pms": "pms", "follicular": "norm", "luteal": "norm"}[self.phase(jy, jm, jd)]

    def next_period(self, jy=None, jm=None, jd=None):
        d = self.day_index(*(jy, jm, jd) if jy else self.today)
        remain = self.cycle_len - d + 1
        return add_days(*(jy, jm, jd) if jy else self.today, remain), remain

    def last_period_start(self):
        for i in range(0, self.cycle_len * 2):
            y, m, d = add_days(*self.today, -i)
            if self.day_index(y, m, d) == 1:
                return y, m, d
        return self.lmp

    def pms_range(self):
        back = self.day_index(*self.today) - self.pms_start
        start = add_days(*self.today, -back)
        (y2, m2, d2), _ = self.next_period()
        end = add_days(y2, m2, d2, -1)
        return start, end

    def fertile_range(self):
        back = self.day_index(*self.today) - self.fertile_start
        start = add_days(*self.today, -back)
        return start, add_days(*start, self.fertile_end - self.fertile_start)

    def period_range(self):
        y, m, d = self.last_period_start()
        return (y, m, d), add_days(y, m, d, self.period_len - 1)


PHASES = {
    "period":     {"fa": "قاعدگی", "color": "#E23E63", "soft": "#FDE7EC",
                   "note": "ریزش آندومتر و شروع چرخهٔ جدید؛ افت استروژن و پروژسترون"},
    "follicular": {"fa": "فولیکولی", "color": "#3E86E8", "soft": "#E4EEFC",
                   "note": "بالا رفتن استروژن؛ انرژی، تمرکز و خلق در بهترین حالت"},
    "fertile":    {"fa": "پنجرهٔ باروری", "color": "#12A594", "soft": "#DEF5F1",
                   "note": "روزهای پراحتمالِ بارداری؛ مراقبت پیشگیری جدی‌تر است"},
    "ovulation":  {"fa": "تخمک‌گذاری", "color": "#6C4CE0", "soft": "#E9E3FB",
                   "note": "آزادسازی تخمک؛ بالاترین احتمال باروری در چرخه"},
    "luteal":     {"fa": "لوتئال", "color": "#B07C2A", "soft": "#F8EEDC",
                   "note": "افزایش پروژسترون؛ بدن در حال آماده‌سازی مرحلهٔ بعد"},
    "pms":        {"fa": "پیش از قاعدگی (PMS)", "color": "#E8912B", "soft": "#FDF0DC",
                   "note": "نوسان هورمونی؛ نوسان خلق، سردرد، نفخ، حساسیت سینه و خستگی"},
    "normal":     {"fa": "روزهای عادی", "color": "#9AA6BF", "soft": "#EEF1F7",
                   "note": "روزهای بدون نشانهٔ ویژه؛ حالت پایه"},
}
