#!/usr/bin/env python3
"""
Build the fonts in ../fonts from the Noto Sans SC variable font.

    build_fonts.py "NotoSansSC[wght].ttf"

(brew install --cask font-noto-sans-sc puts it in ~/Library/Fonts.)

Why this exists: WeasyPrint re-subsets every font it embeds, and doing that for a full CJK font costs
seconds per render. So the calendar uses static instances reduced to the GB2312 hanzi plus Latin and
punctuation (about 2 MB each; 0.1 s to embed). TrueType outlines are required: the same reduction of the
CFF-based "Noto Sans CJK" loses all CJK glyph shapes in the PDF unless --retain-gids is used, which
brings the slowness back.

A full static Regular is kept as a fallback for rare characters (some names); it is only embedded in a
PDF when such a character actually appears.

Needs fontTools (installed together with WeasyPrint).
"""
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

from fontTools.ttLib import TTFont
from fontTools.varLib import instancer

OUT = Path(__file__).resolve().parent.parent / "fonts"
WEIGHTS = {"Light": 300, "Regular": 400, "Medium": 500}

# Latin (German umlauts), general punctuation, arrows, circled digits, CJK symbols, full-width forms
EXTRA_RANGES = [(0x20, 0x7E), (0xA0, 0xFF), (0x100, 0x17F), (0x2000, 0x206F), (0x20AC, 0x20AC),
                (0x2190, 0x21FF), (0x2460, 0x24FF), (0x25A0, 0x25FF), (0x3000, 0x303F), (0xFF00, 0xFFEF)]


def gb2312_hanzi():
    chars = set()
    for hi in range(0xB0, 0xF8):
        for lo in range(0xA1, 0xFF):
            try:
                chars.add(bytes([hi, lo]).decode("gb2312"))
            except UnicodeDecodeError:
                pass
    return chars


def instance(variable_font, weight):
    return instancer.instantiateVariableFont(TTFont(variable_font), {"wght": weight}, inplace=False)


def main():
    if len(sys.argv) != 2:
        print(__doc__)
        return 1
    variable_font = sys.argv[1]
    OUT.mkdir(exist_ok=True)

    chars = gb2312_hanzi()
    for a, b in EXTRA_RANGES:
        chars.update(chr(c) for c in range(a, b + 1))
    print(f"{len(chars)} characters")

    with tempfile.TemporaryDirectory() as tmp:
        charset = Path(tmp) / "charset.txt"
        charset.write_text("".join(sorted(chars)), encoding="utf-8")

        for name, weight in WEIGHTS.items():
            static = Path(tmp) / f"static-{name}.ttf"
            instance(variable_font, weight).save(static)

            target = OUT / f"NotoSC-{name}-GB2312.ttf"
            subprocess.run([sys.executable, "-m", "fontTools.subset", str(static), f"--text-file={charset}",
                            "--layout-features=*", "--no-hinting", f"--output-file={target}"], check=True)
            print(f"{target.name}: {target.stat().st_size // 1024} KB")

            if name == "Regular":  # full static Regular as the rare-character fallback
                full = OUT / "NotoSC-Regular-Full.ttf"
                shutil.move(str(static), str(full))
                print(f"{full.name}: {full.stat().st_size // 1024} KB")
    return 0


if __name__ == "__main__":
    sys.exit(main())
