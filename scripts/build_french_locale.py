#!/usr/bin/env python3
"""Build complete system/lan/french.json from english.json via Google Translate."""
import json
import time
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
EN = ROOT / "system/lan/english.json"
FR = ROOT / "system/lan/french.json"
BATCH = 25
DELAY = 0.35
MAX_RETRIES = 3


def main():
    try:
        from deep_translator import GoogleTranslator
    except ImportError:
        import subprocess
        subprocess.check_call([sys.executable, "-m", "pip", "install", "deep-translator", "-q"])
        from deep_translator import GoogleTranslator

    en = json.loads(EN.read_text(encoding="utf-8"))
    existing = {}
    if FR.exists():
        existing = json.loads(FR.read_text(encoding="utf-8"))

    translator = GoogleTranslator(source="en", target="fr")
    fr = dict(existing)
    keys = list(en.keys())
    total = len(keys)
    done = 0

    for i in range(0, total, BATCH):
        chunk_keys = keys[i : i + BATCH]
        texts = [en[k] for k in chunk_keys]
        # Skip if all already translated (value differs from English source)
        need = []
        need_keys = []
        for k, t in zip(chunk_keys, texts):
            if k in fr and fr[k] != t and fr[k] != en[k]:
                continue
            need_keys.append(k)
            need.append(t)
        if not need:
            done += len(chunk_keys)
            print(f"{done}/{total} (cached)", flush=True)
            continue
        translated = []
        for attempt in range(MAX_RETRIES):
            try:
                translated = translator.translate_batch(need)
                break
            except Exception as e:
                print(f"Batch error at {i} (try {attempt + 1}): {e}", flush=True)
                time.sleep(1.0 * (attempt + 1))
        if not translated:
            print(f"Batch {i}: one-by-one fallback", flush=True)
            for t in need:
                tr = t
                for attempt in range(MAX_RETRIES):
                    try:
                        tr = translator.translate(t)
                        break
                    except Exception as e2:
                        time.sleep(0.5 * (attempt + 1))
                translated.append(tr)
                time.sleep(DELAY)
        for k, tr in zip(need_keys, translated):
            fr[k] = tr if tr else en[k]
        done += len(chunk_keys)
        print(f"{done}/{total}", flush=True)
        time.sleep(DELAY)
        FR.write_text(json.dumps(fr, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")

    # Ensure every English key exists
    for k, v in en.items():
        if k not in fr:
            fr[k] = v
    FR.write_text(json.dumps(fr, ensure_ascii=False, indent=4) + "\n", encoding="utf-8")
    print(f"Wrote {len(fr)} keys to {FR}")


if __name__ == "__main__":
    main()
