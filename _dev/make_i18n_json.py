#!/usr/bin/env python3
"""Genera manualmente los JSON WordPress desde los .po del plugin."""

from pathlib import Path
import os
import shutil
import subprocess


ROOT = Path(__file__).resolve().parent.parent
CONFIG = Path(__file__).resolve().with_name("i18n-config.yml")


def read_config():
	values = {}
	for line in CONFIG.read_text(encoding="utf-8").splitlines():
		line = line.strip()
		if not line or line.startswith("#") or ":" not in line:
			continue
		key, value = line.split(":", 1)
		values[key.strip()] = value.strip()
	return values


def main():
	config = read_config()
	languages = ROOT / config.get("languages_dir", "languages")
	wp_cli = os.environ.get("WP_BIN", config.get("wp_cli", "/usr/local/bin/wp"))
	php_bin = os.environ.get("PHP_BIN")
	if not php_bin or php_bin == "auto":
		php_bin = shutil.which("php")
	if not php_bin:
		local_php = Path("/Users/22mw/Library/Application Support/Local/lightning-services")
		candidates = sorted(local_php.glob("*/bin/darwin-arm64/bin/php"))
		php_bin = str(candidates[-1]) if candidates else None
	if not php_bin or not Path(php_bin).exists():
		raise SystemExit("No se encontró PHP. Usa PHP_BIN=/ruta/a/php.")
	if not Path(wp_cli).exists():
		raise SystemExit(f"No se encontró WP-CLI: {wp_cli}")
	po_files = sorted(languages.glob("*.po"))
	if not po_files:
		raise SystemExit(f"No hay archivos .po en {languages}")
	for po_file in po_files:
		print(f"Generando JSON desde {po_file.relative_to(ROOT)}…")
		subprocess.run([php_bin, wp_cli, "i18n", "make-json", str(po_file), str(languages)], check=True)
	print("JSON generado. No se ha creado ningún .mo.")


if __name__ == "__main__":
	main()
