#!/usr/bin/env bash
# دانلود و نام‌گذاری فونت Vazirmatn برای ماه‌بانو.
set -euo pipefail
VER="${1:-33.003}"
TMP="$(mktemp -d)"
URL="https://github.com/rastikerdar/vazirmatn/releases/download/v${VER}/vazirmatn-v${VER}.zip"
echo "دریافت $URL"
curl -fL "$URL" -o "$TMP/v.zip"
unzip -q "$TMP/v.zip" -d "$TMP"
SRC="$(find "$TMP" -type d -name webfonts | head -n1)"
[ -n "$SRC" ] || { echo "پوشه webfonts پیدا نشد"; exit 1; }
declare -A MAP=( [Regular]=400 [Medium]=500 [SemiBold]=600 [Bold]=700 [ExtraBold]=800 )
for name in "${!MAP[@]}"; do
  cp "$SRC/Vazirmatn-$name.woff2" "$(dirname "$0")/Vazirmatn-${MAP[$name]}.woff2"
  echo "✓ Vazirmatn-${MAP[$name]}.woff2"
done
find "$TMP" -iname 'OFL.txt' -o -iname 'LICENSE*' | head -n1 | xargs -I{} cp {} "$(dirname "$0")/LICENSE-Vazirmatn.txt" || true
rm -rf "$TMP"
echo "تمام شد."
