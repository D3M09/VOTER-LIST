import argparse
import json
import os
import re

from pypdf import PdfReader
from pypdf.generic import ContentStream, TextStringObject, ByteStringObject

HERE = os.path.dirname(os.path.abspath(__file__))
_MAP = next((p for p in (os.path.join(HERE, 'jsonmap.json'), os.path.join(HERE, 'gid2uni.json'))
             if os.path.exists(p)), os.path.join(HERE, 'jsonmap.json'))
GM = {int(k): v for k, v in json.load(open(_MAP)).items()}

HAS = '\u09cd'
SENT = '\x01'
REPH_GID = 206
PRE = '\u09bf\u09c7\u09c8'
CONS = set('\u0995\u0996\u0997\u0998\u0999\u099a\u099b\u099c\u099d\u099e'
           '\u099f\u09a0\u09a1\u09a2\u09a3\u09a4\u09a5\u09a6\u09a7\u09a8'
           '\u09aa\u09ab\u09ac\u09ad\u09ae\u09af\u09b0\u09b2\u09b6\u09b7'
           '\u09b8\u09b9\u09dc\u09dd\u09df\u09ce')
BN = '০১২৩৪৫৬৭৮৯'
BN2EN = str.maketrans(BN, '0123456789')
ANCHORS = [70.3, 306.9, 543.5]
RE_SERIAL = re.compile(r'^([০-৯]+)\.\s*নাম:\s*(.*)$')
RE_SERIAL_ONLY = re.compile(r'^([০-৯]+)\.\s*$')
RE_VOTER = re.compile(r'^ভোটার\s*নং:\s*(.*)$')
RE_FATHER = re.compile(r'^পিতা:\s*(.*)$')
RE_MOTHER = re.compile(r'^মাতা:\s*(.*)$')
RE_OCC = re.compile(r'^পেশা:\s*(.*?)(?:,\s*জন্ম\s*তারিখ:\s*(.*))?$')
RE_ADDR = re.compile(r'^ঠিকানা:\s*(.*)$')
RE_META = re.compile(r'^([^:]+?)\s*:\s*(.*)$')
LABELS = {
    'অঞ্চল': 'region', 'জেলা': 'district', 'উপজেলা/থানা': 'upazila', 'উপজেলা': 'upazila',
    'ইউনিয়ন': 'union', 'ডাকঘর': 'post_office', 'ভোটার এলাকার কোড': 'area_code',
    'ভোটার এলাকার নাম': 'area_name', 'ভোটার এলাকার নম্বর': 'area_no', 'পোষ্টকোড': 'postcode',
    'ওয়ার্ড নম্বর (ইউনিয়ন পরিষদের জন্য)': 'ward_no', 'সর্বমোট ভোটার সংখ্যা': 'total_voters',
    'মোট পুরুষ ভোটার সংখ্যা': 'male_voters', 'মোট মহিলা ভোটার সংখ্যা': 'female_voters',
    'প্রকাশের তারিখ': 'publish_date', 'ভোটার তালিকা প্রকাশের তারিখ': 'publish_date',
    'সিটি কর্পোরেশন/ পৌরসভা': 'city_corp_or_pourasabha',
    'ইউনিয়ন/ওয়ার্ড/ক্যাঃ বোঃ': 'union_ward_cantonment',
    'ইউনিয়ন/ পৌর ওয়ার্ড/': 'union_ward',
}
TITLES = ('ফরম-১', 'ছবি ছাড়া', 'চূড়ান্ত ভোটার তালিকা', 'পুরুষ', 'মহিলা', 'বাংলাদেশ নির্বাচন কমিশন')


def clean(s):
    s = (s or '').replace('\u00a0', ' ')
    return re.sub(r'[ \t]+', ' ', s).strip()


def is_cons(ch):
    return ch in CONS


def cids_of(it):
    if isinstance(it, TextStringObject):
        b = it.original_bytes
    elif isinstance(it, ByteStringObject):
        b = bytes(it)
    elif isinstance(it, (bytes, bytearray)):
        b = bytes(it)
    else:
        return []
    return [(b[i] << 8) | b[i + 1] for i in range(0, len(b) - 1, 2)]


def _cluster_start(out):
    i = len(out) - 1
    while i >= 0:
        if not is_cons(out[i]):
            break
        if i >= 2 and out[i - 1] == HAS and is_cons(out[i - 2]):
            i -= 2
        else:
            break
    return i


def reorder(s):
    out = []
    for ch in s:
        if ch == SENT:
            start = _cluster_start(out)
            if start < 0:
                start = len(out)
            out[start:start] = ['\u09b0', HAS]
        else:
            out.append(ch)
    s = ''.join(out)
    res = []
    while s:
        ch = s[0]
        if ch in PRE and len(s) > 1 and is_cons(s[1]):
            k = 1
            while k + 2 < len(s) and s[k + 1] == HAS and is_cons(s[k + 2]):
                k += 2
            base = s[1:k + 1]
            rest = s[k + 1:]
            if ch == '\u09c7' and rest[:1] == '\u09be':
                res.append(base + '\u09cb'); s = rest[1:]; continue
            if ch == '\u09c7' and rest[:1] == '\u09d7':
                res.append(base + '\u09cc'); s = rest[1:]; continue
            res.append(base + ch); s = rest; continue
        res.append(ch); s = s[1:]
    return ''.join(res)


def decode_cids(cids):
    return reorder(''.join(SENT if c == REPH_GID else GM.get(c, '\ufffd') for c in cids))


def page_positioned(pdf, pageno):
    r = PdfReader(pdf)
    p = r.pages[pageno]
    ops = ContentStream(p.get_contents(), r)
    tm = [1, 0, 0, 1, 0, 0]
    tlm = tm[:]
    leading = 0.0
    rows = []
    for operands, op in ops.operations:
        op = op.decode() if isinstance(op, bytes) else op
        if op == 'BT':
            tm = [1, 0, 0, 1, 0, 0]; tlm = tm[:]
        elif op == 'Tm':
            tm = [float(v) for v in operands]; tlm = tm[:]
        elif op == 'Td':
            tx, ty = float(operands[0]), float(operands[1])
            tlm = [tlm[0], tlm[1], tlm[2], tlm[3],
                   tlm[4] + tx * tlm[0] + ty * tlm[2],
                   tlm[5] + tx * tlm[1] + ty * tlm[3]]
            tm = tlm[:]
        elif op == 'TD':
            tx, ty = float(operands[0]), float(operands[1]); leading = -ty
            tlm = [tlm[0], tlm[1], tlm[2], tlm[3],
                   tlm[4] + tx * tlm[0] + ty * tlm[2],
                   tlm[5] + tx * tlm[1] + ty * tlm[3]]
            tm = tlm[:]
        elif op == 'TL':
            leading = float(operands[0])
        elif op == 'T*':
            tlm = [tlm[0], tlm[1], tlm[2], tlm[3],
                   tlm[4] - leading * tlm[2], tlm[5] - leading * tlm[3]]
            tm = tlm[:]
        elif op in ('Tj', 'TJ', "'", '"'):
            if op in ("'", '"'):
                tlm = [tlm[0], tlm[1], tlm[2], tlm[3],
                       tlm[4] - leading * tlm[2], tlm[5] - leading * tlm[3]]
                tm = tlm[:]
            arr = operands[0] if op == 'TJ' else [operands[0]]
            cids = []
            for it in arr:
                cids += cids_of(it)
            if cids:
                rows.append((round(tm[4], 1), round(tm[5], 1), decode_cids(cids)))
    return rows


def parse_meta(pdf):
    meta = {}
    for pg in (0, 2):
        try:
            rows = page_positioned(pdf, pg)
        except Exception:
            continue
        byy = {}
        for x, y, t in rows:
            t = clean(t)
            if t:
                byy.setdefault(round(y, 1), []).append((x, t))
        for y in sorted(byy, reverse=True):
            pending = None
            for x, t in sorted(byy[y]):
                if t in TITLES:
                    continue
                m = RE_META.match(t)
                if m:
                    label = clean(m.group(1)); val = clean(m.group(2))
                    key = LABELS.get(label)
                    if key:
                        if val:
                            meta.setdefault(key, val)
                        else:
                            pending = (key, x)
                    continue
                key = LABELS.get(t)
                if key:
                    pending = (key, x); continue
                if pending and x > pending[1]:
                    meta.setdefault(pending[0], t); pending = None
    return meta


def extract_pdf(pdf):
    total = len(PdfReader(pdf).pages)
    meta = parse_meta(pdf)
    records = []
    cancelled = []
    for pg in range(1, total):
        rows = page_positioned(pdf, pg)
        if not rows:
            continue
        cols = {0: [], 1: [], 2: []}
        for x, y, t in rows:
            t = clean(t)
            if not t:
                continue
            if (RE_SERIAL.match(t) or RE_SERIAL_ONLY.match(t) or RE_VOTER.match(t)
                    or RE_FATHER.match(t) or RE_MOTHER.match(t) or RE_OCC.match(t)
                    or RE_ADDR.match(t)):
                ci = min(range(3), key=lambda i: abs(x - ANCHORS[i]))
                cols[ci].append((y, x, t))
        for ci in range(3):
            cur = None
            for y, x, t in sorted(cols[ci], key=lambda z: (-z[0], z[1])):
                m = RE_SERIAL.match(t)
                if m:
                    if cur:
                        records.append(cur)
                    sn = m.group(1)
                    cur = {'serial': int(sn.translate(BN2EN)), 'serial_bn': sn, 'name': clean(m.group(2)),
                           'voter_no': None, 'voter_no_digits': None, 'father': None, 'mother': None,
                           'occupation': None, 'dob': None, 'address': None,
                           'page': pg + 1, 'column': ci + 1, 'status': 'ok'}
                    continue
                mo = RE_SERIAL_ONLY.match(t)
                if mo:
                    if cur:
                        records.append(cur); cur = None
                    sn = mo.group(1)
                    records.append({'serial': int(sn.translate(BN2EN)), 'serial_bn': sn, 'name': None,
                                    'voter_no': None, 'voter_no_digits': None, 'father': None, 'mother': None,
                                    'occupation': None, 'dob': None, 'address': None,
                                    'page': pg + 1, 'column': ci + 1, 'status': 'cancelled'})
                    cancelled.append(int(sn.translate(BN2EN)))
                    continue
                if cur is None:
                    continue
                m = RE_VOTER.match(t)
                if m:
                    v = clean(m.group(1)); cur['voter_no'] = v
                    cur['voter_no_digits'] = v.translate(BN2EN) if all(ch in BN + '-' for ch in v) else None
                    continue
                m = RE_FATHER.match(t)
                if m:
                    cur['father'] = clean(m.group(1)); continue
                m = RE_MOTHER.match(t)
                if m:
                    cur['mother'] = clean(m.group(1)); continue
                m = RE_OCC.match(t)
                if m:
                    cur['occupation'] = clean(m.group(1))
                    cur['dob'] = clean(m.group(2)) if m.group(2) else None
                    continue
                m = RE_ADDR.match(t)
                if m:
                    cur['address'] = clean(m.group(1)); continue
            if cur:
                records.append(cur)
    records.sort(key=lambda r: r['serial'])
    serials = [r['serial'] for r in records]
    dups = sorted({s for s in serials if serials.count(s) > 1})
    have = set(serials)
    missing = [s for s in range(1, max(serials) + 1) if s not in have] if serials else []
    return {'file': os.path.basename(pdf), 'pages': total, 'meta': meta, 'records': records,
            'stats': {'records': len(records),
                      'active': sum(1 for r in records if r['status'] == 'ok'),
                      'first_serial': min(serials) if serials else None,
                      'last_serial': max(serials) if serials else None,
                      'cancelled': sorted(set(cancelled)), 'duplicates': dups,
                      'missing_serials': missing,
                      'incomplete': [r['serial'] for r in records if r['status'] != 'ok']}}


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('pdfs', nargs='*')
    ap.add_argument('-o', '--outdir', default='json')
    args = ap.parse_args()
    pdfs = list(args.pdfs)
    if not pdfs:
        for root, _, files in os.walk('pdf'):
            pdfs += [os.path.join(root, f) for f in sorted(files) if f.lower().endswith('.pdf')]
    os.makedirs(args.outdir, exist_ok=True)
    for pdf in pdfs:
        d = extract_pdf(pdf)
        out = os.path.join(args.outdir, os.path.basename(pdf)[:-4] + '.json')
        json.dump(d, open(out, 'w'), ensure_ascii=False, indent=1)
        print(f"{d['file']}: records={d['stats']['records']} active={d['stats']['active']} "
              f"cancelled={len(d['stats']['cancelled'])} -> {out}")


if __name__ == '__main__':
    main()
