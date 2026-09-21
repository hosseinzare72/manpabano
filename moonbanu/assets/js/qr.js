/*!
 * ماه‌بانو | MBQR 1.0 — تولیدکننده QR سبک و بدون وابستگی.
 * حالت بایت (UTF-8)، سطح تصحیح خطا M، نسخه‌های ۱ تا ۱۰، انتخاب خودکار ماسک.
 */
(function (root) {
	'use strict';

	/* --- جدول‌های نسخه برای سطح M: [کل کدوردها, EC هر بلوک, [تعداد۱,داده۱,تعداد۲,داده۲]] --- */
	var VER = {
		1: [26, 10, [1, 16, 0, 0]],
		2: [44, 16, [1, 28, 0, 0]],
		3: [70, 26, [1, 44, 0, 0]],
		4: [100, 18, [2, 32, 0, 0]],
		5: [134, 24, [2, 43, 0, 0]],
		6: [172, 16, [4, 27, 0, 0]],
		7: [196, 18, [4, 31, 0, 0]],
		8: [242, 22, [2, 38, 2, 39]],
		9: [292, 22, [3, 36, 2, 37]],
		10: [346, 26, [4, 43, 1, 44]]
	};

	var ALIGN = {
		1: [], 2: [6, 18], 3: [6, 22], 4: [6, 26], 5: [6, 30],
		6: [6, 34], 7: [6, 22, 38], 8: [6, 24, 42], 9: [6, 26, 46], 10: [6, 28, 50]
	};

	var VERSION_BITS = { 7: 0x07C94, 8: 0x085BC, 9: 0x09A99, 10: 0x0A4D3 };

	/* --- میدان گالوا --- */
	var EXP = new Array(512), LOG = new Array(256);
	(function () {
		var x = 1, i;
		for (i = 0; i < 255; i++) {
			EXP[i] = x;
			LOG[x] = i;
			x <<= 1;
			if (x & 0x100) { x ^= 0x11D; }
		}
		for (i = 255; i < 512; i++) { EXP[i] = EXP[i - 255]; }
	}());

	function gmul(a, b) {
		if (a === 0 || b === 0) { return 0; }
		return EXP[(LOG[a] + LOG[b]) % 255];
	}

	function rsGenerator(n) {
		var poly = [1], i, j, next;
		for (i = 0; i < n; i++) {
			next = poly.slice();
			next.push(0);
			for (j = 0; j < poly.length; j++) {
				next[j + 1] ^= gmul(poly[j], EXP[i]);
			}
			poly = next;
		}
		return poly;
	}

	function rsEncode(data, ecLen) {
		var gen = rsGenerator(ecLen);
		var res = new Array(ecLen).fill(0);
		var i, j, factor, shifted;
		for (i = 0; i < data.length; i++) {
			factor = data[i] ^ res[0];
			res.shift();
			res.push(0);
			if (factor !== 0) {
				for (j = 0; j < gen.length - 1; j++) {
					res[j] ^= gmul(gen[j + 1], factor);
				}
			}
		}
		return res;
	}

	/* --- UTF-8 --- */
	function toBytes(str) {
		var out = [], i, c;
		if (root.TextEncoder) {
			return Array.prototype.slice.call(new root.TextEncoder().encode(str));
		}
		for (i = 0; i < str.length; i++) {
			c = str.charCodeAt(i);
			if (c < 0x80) { out.push(c); }
			else if (c < 0x800) { out.push(0xC0 | (c >> 6), 0x80 | (c & 63)); }
			else { out.push(0xE0 | (c >> 12), 0x80 | ((c >> 6) & 63), 0x80 | (c & 63)); }
		}
		return out;
	}

	function dataCodewords(version) {
		var g = VER[version][2];
		return g[0] * g[1] + g[2] * g[3];
	}

	function pickVersion(len) {
		var v, header;
		for (v = 1; v <= 10; v++) {
			header = 4 + (v < 10 ? 8 : 16);
			if (dataCodewords(v) * 8 >= header + len * 8) { return v; }
		}
		return 0;
	}

	/* --- ساخت رشته بیت --- */
	function buildBits(bytes, version) {
		var bits = [], i, j, total = dataCodewords(version) * 8, pad = [0xEC, 0x11], k = 0;

		function push(value, length) {
			for (j = length - 1; j >= 0; j--) { bits.push((value >> j) & 1); }
		}

		push(4, 4);
		push(bytes.length, version < 10 ? 8 : 16);
		for (i = 0; i < bytes.length; i++) { push(bytes[i], 8); }

		for (i = 0; i < 4 && bits.length < total; i++) { bits.push(0); }
		while (bits.length % 8 !== 0) { bits.push(0); }
		while (bits.length < total) {
			push(pad[k % 2], 8);
			k++;
		}
		return bits;
	}

	function bitsToCodewords(bits) {
		var out = [], i, b;
		for (i = 0; i < bits.length; i += 8) {
			b = 0;
			for (var j = 0; j < 8; j++) { b = (b << 1) | bits[i + j]; }
			out.push(b);
		}
		return out;
	}

	/* --- بلوک‌بندی و درهم‌بافی --- */
	function interleave(codewords, version) {
		var info = VER[version], ecLen = info[1], g = info[2];
		var blocks = [], ec = [], pos = 0, i, j, count, size, max = 0;

		var groups = [[g[0], g[1]], [g[2], g[3]]];
		for (i = 0; i < groups.length; i++) {
			count = groups[i][0];
			size = groups[i][1];
			for (j = 0; j < count; j++) {
				var block = codewords.slice(pos, pos + size);
				pos += size;
				blocks.push(block);
				ec.push(rsEncode(block, ecLen));
				if (size > max) { max = size; }
			}
		}

		var out = [];
		for (i = 0; i < max; i++) {
			for (j = 0; j < blocks.length; j++) {
				if (i < blocks[j].length) { out.push(blocks[j][i]); }
			}
		}
		for (i = 0; i < ecLen; i++) {
			for (j = 0; j < ec.length; j++) { out.push(ec[j][i]); }
		}
		return out;
	}

	/* --- ماتریس --- */
	function makeMatrix(size) {
		var m = [], i, j, row;
		for (i = 0; i < size; i++) {
			row = [];
			for (j = 0; j < size; j++) { row.push(null); }
			m.push(row);
		}
		return m;
	}

	function placeFinder(m, r, c) {
		var i, j, size = m.length, dr, dc, v;
		for (i = -1; i <= 7; i++) {
			for (j = -1; j <= 7; j++) {
				dr = r + i;
				dc = c + j;
				if (dr < 0 || dc < 0 || dr >= size || dc >= size) { continue; }
				if (i === -1 || i === 7 || j === -1 || j === 7) { v = 0; }
				else if (i === 0 || i === 6 || j === 0 || j === 6) { v = 1; }
				else if (i >= 2 && i <= 4 && j >= 2 && j <= 4) { v = 1; }
				else { v = 0; }
				m[dr][dc] = v;
			}
		}
	}

	function placeAlignment(m, version) {
		var centers = ALIGN[version], size = m.length, a, b, i, j, r, c;
		for (a = 0; a < centers.length; a++) {
			for (b = 0; b < centers.length; b++) {
				r = centers[a];
				c = centers[b];
				if ((r === 6 && c === 6) || (r === 6 && c === size - 7) || (r === size - 7 && c === 6)) { continue; }
				for (i = -2; i <= 2; i++) {
					for (j = -2; j <= 2; j++) {
						m[r + i][c + j] = (Math.max(Math.abs(i), Math.abs(j)) !== 1) ? 1 : 0;
					}
				}
			}
		}
	}

	function placeTiming(m) {
		var size = m.length, i;
		for (i = 8; i < size - 8; i++) {
			if (m[6][i] === null) { m[6][i] = (i % 2 === 0) ? 1 : 0; }
			if (m[i][6] === null) { m[i][6] = (i % 2 === 0) ? 1 : 0; }
		}
	}

	function reserveFormat(m) {
		var size = m.length, i;
		for (i = 0; i <= 8; i++) {
			if (m[8][i] === null) { m[8][i] = 2; }
			if (m[i][8] === null) { m[i][8] = 2; }
		}
		for (i = size - 8; i < size; i++) {
			if (m[8][i] === null) { m[8][i] = 2; }
			if (m[i][8] === null) { m[i][8] = 2; }
		}
		m[size - 8][8] = 1; // ماژول تیره.
	}

	function reserveVersion(m, version) {
		if (version < 7) { return; }
		var size = m.length, i, j;
		for (i = 0; i < 6; i++) {
			for (j = 0; j < 3; j++) {
				if (m[size - 11 + j][i] === null) { m[size - 11 + j][i] = 2; }
				if (m[i][size - 11 + j] === null) { m[i][size - 11 + j] = 2; }
			}
		}
	}

	function placeData(m, codewords) {
		var size = m.length, bitIdx = 0, total = codewords.length * 8;
		var col = size - 1, row = size - 1, upward = true, i, c, bit;

		function nextBit() {
			if (bitIdx >= total) { return 0; }
			var b = (codewords[bitIdx >> 3] >> (7 - (bitIdx & 7))) & 1;
			bitIdx++;
			return b;
		}

		while (col > 0) {
			if (col === 6) { col--; }
			for (i = 0; i < size; i++) {
				row = upward ? size - 1 - i : i;
				for (c = 0; c < 2; c++) {
					var cc = col - c;
					if (m[row][cc] === null) {
						bit = nextBit();
						m[row][cc] = bit;
					}
				}
			}
			col -= 2;
			upward = !upward;
		}
	}

	function maskBit(mask, r, c) {
		switch (mask) {
			case 0: return (r + c) % 2 === 0;
			case 1: return r % 2 === 0;
			case 2: return c % 3 === 0;
			case 3: return (r + c) % 3 === 0;
			case 4: return (Math.floor(r / 2) + Math.floor(c / 3)) % 2 === 0;
			case 5: return ((r * c) % 2) + ((r * c) % 3) === 0;
			case 6: return (((r * c) % 2) + ((r * c) % 3)) % 2 === 0;
			default: return (((r + c) % 2) + ((r * c) % 3)) % 2 === 0;
		}
	}

	function formatBits(mask) {
		var data = (0 << 3) | mask; // سطح M = 00
		var value = data << 10, i;
		for (i = 4; i >= 0; i--) {
			if (value & (1 << (i + 10))) { value ^= 0x537 << i; }
		}
		return ((data << 10) | value) ^ 0x5412;
	}

	function applyFormat(m, mask) {
		var bits = formatBits(mask), size = m.length, i, bit;
		for (i = 0; i < 15; i++) {
			bit = (bits >> i) & 1;
			// ستون عمودی کنار فایندر بالا-چپ و پایین-چپ.
			if (i < 6) { m[i][8] = bit; }
			else if (i < 8) { m[i + 1][8] = bit; }
			else { m[size - 15 + i][8] = bit; }
			// ردیف افقی.
			if (i < 8) { m[8][size - 1 - i] = bit; }
			else if (i === 8) { m[8][7] = bit; }
			else { m[8][14 - i] = bit; }
		}
		m[size - 8][8] = 1; // ماژول تیره.
	}

	function applyVersionInfo(m, version) {
		if (version < 7) { return; }
		var bits = VERSION_BITS[version], size = m.length, i, bit;
		for (i = 0; i < 18; i++) {
			bit = (bits >> i) & 1;
			m[Math.floor(i / 3)][size - 11 + (i % 3)] = bit;
			m[size - 11 + (i % 3)][Math.floor(i / 3)] = bit;
		}
	}

	function penalty(m) {
		var size = m.length, score = 0, r, c, run, dark = 0, i;

		function lineScore(get) {
			var s = 0, prev = -1, len = 0, k;
			for (k = 0; k < size; k++) {
				var v = get(k);
				if (v === prev) { len++; } else { if (len >= 5) { s += 3 + (len - 5); } prev = v; len = 1; }
			}
			if (len >= 5) { s += 3 + (len - 5); }
			return s;
		}

		for (r = 0; r < size; r++) {
			score += lineScore(function (k) { return m[r][k]; });
		}
		for (c = 0; c < size; c++) {
			score += lineScore(function (k) { return m[k][c]; });
		}
		for (r = 0; r < size - 1; r++) {
			for (c = 0; c < size - 1; c++) {
				var v = m[r][c];
				if (v === m[r][c + 1] && v === m[r + 1][c] && v === m[r + 1][c + 1]) { score += 3; }
			}
		}
		var pat1 = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0];
		var pat2 = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];
		function match(arr, start, get) {
			for (i = 0; i < arr.length; i++) {
				if (get(start + i) !== arr[i]) { return false; }
			}
			return true;
		}
		for (r = 0; r < size; r++) {
			for (c = 0; c + 11 <= size; c++) {
				if (match(pat1, c, function (k) { return m[r][k]; }) || match(pat2, c, function (k) { return m[r][k]; })) { score += 40; }
			}
		}
		for (c = 0; c < size; c++) {
			for (r = 0; r + 11 <= size; r++) {
				if (match(pat1, r, function (k) { return m[k][c]; }) || match(pat2, r, function (k) { return m[k][c]; })) { score += 40; }
			}
		}
		for (r = 0; r < size; r++) {
			for (c = 0; c < size; c++) { if (m[r][c] === 1) { dark++; } }
		}
		var percent = (dark * 100) / (size * size);
		score += Math.floor(Math.abs(percent - 50) / 5) * 10;
		return score;
	}

	/* --- API --- */
	function encode(text) {
		var bytes = toBytes(String(text));
		var version = pickVersion(bytes.length);
		if (!version) { throw new Error('MBQR: متن برای نسخه‌های ۱ تا ۱۰ بلند است.'); }

		var codewords = interleave(bitsToCodewords(buildBits(bytes, version)), version);
		var size = version * 4 + 17;

		var base = makeMatrix(size);
		placeFinder(base, 0, 0);
		placeFinder(base, 0, size - 7);
		placeFinder(base, size - 7, 0);
		placeAlignment(base, version);
		placeTiming(base);
		reserveVersion(base, version);
		reserveFormat(base);

		var reserved = [], r, c;
		for (r = 0; r < size; r++) {
			reserved.push([]);
			for (c = 0; c < size; c++) { reserved[r].push(base[r][c] !== null); }
		}

		var withData = [];
		for (r = 0; r < size; r++) { withData.push(base[r].slice()); }
		for (r = 0; r < size; r++) {
			for (c = 0; c < size; c++) { if (withData[r][c] === 2) { withData[r][c] = null; } }
		}
		// جاهای رزروشده دوباره پر می‌شوند تا داده در آن‌ها نرود.
		for (r = 0; r < size; r++) {
			for (c = 0; c < size; c++) { if (reserved[r][c] && withData[r][c] === null) { withData[r][c] = 0; } }
		}
		placeData(withData, codewords);

		var best = null, bestScore = Infinity, mask;
		for (mask = 0; mask < 8; mask++) {
			var cand = [];
			for (r = 0; r < size; r++) { cand.push(withData[r].slice()); }
			for (r = 0; r < size; r++) {
				for (c = 0; c < size; c++) {
					if (!reserved[r][c] && maskBit(mask, r, c)) { cand[r][c] ^= 1; }
				}
			}
			applyFormat(cand, mask);
			applyVersionInfo(cand, version);
			var sc = penalty(cand);
			if (sc < bestScore) { bestScore = sc; best = cand; }
		}
		return { size: size, version: version, matrix: best };
	}

	function svg(text, options) {
		var opt = options || {};
		var quiet = typeof opt.quiet === 'number' ? opt.quiet : 2;
		var res = encode(text);
		var total = res.size + quiet * 2;
		var parts = [], r, c, run;

		for (r = 0; r < res.size; r++) {
			c = 0;
			while (c < res.size) {
				if (res.matrix[r][c] === 1) {
					run = 1;
					while (c + run < res.size && res.matrix[r][c + run] === 1) { run++; }
					parts.push('M' + (c + quiet) + ' ' + (r + quiet) + 'h' + run + 'v1h-' + run + 'z');
					c += run;
				} else { c++; }
			}
		}

		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + total + ' ' + total + '" shape-rendering="crispEdges" role="img" aria-label="کد QR">' +
			'<rect width="' + total + '" height="' + total + '" fill="' + (opt.light || '#ffffff') + '"/>' +
			'<path d="' + parts.join('') + '" fill="' + (opt.dark || '#07040C') + '"/></svg>';
	}

	function render(el, text, options) {
		if (!el) { return false; }
		try {
			el.innerHTML = svg(text, options);
			return true;
		} catch (e) {
			el.innerHTML = '';
			return false;
		}
	}

	root.MBQR = { encode: encode, svg: svg, render: render };
}(typeof window !== 'undefined' ? window : globalThis));
