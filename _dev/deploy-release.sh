#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────────────
# deploy-release.sh — Crea release de AI Knowledge & Visibility en GitHub
#
# REGLA DE SEGURIDAD: este script NO toca tu carpeta de trabajo. Todo el
# trabajo (borrar _dev/, crear la rama release, fusionar en main) ocurre en un
# CLON TEMPORAL fuera de tu carpeta, que se elimina al terminar. Así nunca se
# pierden archivos sin versionar (antes, un `rm -rf _dev` en tu propia carpeta
# borró archivos que no estaban en Git).
#
# Flujo:
#   1. Lee la versión del header de ai-knowledge.php
#   2. Verifica que el tag no exista, que estés en knowBaseDev, sin cambios
#      pendientes y con todo subido (HEAD == origin/knowBaseDev)
#   3. Extrae el changelog de CHANGELOG.md
#   4. En el clon temporal: crea/sobreescribe la rama "release" desde
#      origin/knowBaseDev sin archivos dev
#   5. Crea un ZIP con la carpeta ai-knowledge/ dentro
#   6. Crea la release en GitHub (GitHub crea el tag) y sube el ZIP
#   7. En el clon temporal: fusiona release en main y sube main
#
# Requisito: GITHUB_TOKEN en _dev/.env, .env.local, raíz del workspace o entorno
# Uso: cd .../ai-knowledge && ./_dev/deploy-release.sh
#      ./_dev/deploy-release.sh --dry-run   (no publica nada: sin push, sin
#                                            release; solo prepara y comprueba)
# ─────────────────────────────────────────────────────────────────────────────

DRY_RUN=0
if [ "$1" = "--dry-run" ]; then
    DRY_RUN=1
    echo "━━━ MODO PRUEBA (--dry-run): no se publica nada ━━━"
fi

# ── Cargar token ──────────────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_DIR"

if [ -f "$SCRIPT_DIR/.env" ]; then
    export $(grep -v '^#' "$SCRIPT_DIR/.env" | xargs)
fi

if [ -f ".env.local" ]; then
    export $(grep -v '^#' ".env.local" | xargs)
fi

WORKSPACE_ENV="$(cd "$REPO_DIR/../../../../.." && pwd)/.env"
if [ -f "$WORKSPACE_ENV" ]; then
    export $(grep -v '^#' "$WORKSPACE_ENV" | xargs)
fi

if [ -z "$GITHUB_TOKEN" ] && [ "$DRY_RUN" -eq 0 ]; then
    echo "Error: define GITHUB_TOKEN en _dev/.env, .env.local, raíz del workspace o variable de entorno"
    echo "  echo 'GITHUB_TOKEN=ghp_xxx' > /Users/22mw/Local\ Sites/plugins/.env"
    exit 1
fi

REPO="22MW/ai-knowledge"
BRANCH_DEV="knowBaseDev"
BRANCH_RELEASE="release"
PLUGIN_FOLDER="ai-knowledge"

# ── Leer versión del header del plugin ───────────────────────────────────────

VERSION=$(grep '^ \* Version:' ai-knowledge.php | grep -oE '[0-9]+\.[0-9]+\.[0-9]+(\.[0-9]+)?')

if [ -z "$VERSION" ]; then
    echo "Error: no se pudo leer la versión de ai-knowledge.php"
    exit 1
fi

TAG="v$VERSION"
echo "━━━ Release $TAG desde $BRANCH_DEV ━━━"

# ── Verificar que el tag no exista ya ─────────────────────────────────────────

if git ls-remote --tags origin "refs/tags/$TAG" | grep -q "$TAG"; then
    if [ "$DRY_RUN" -eq 1 ]; then
        echo "Aviso (prueba): el tag $TAG ya existe en remote; en una release real esto pararía el script."
    else
        echo "Error: el tag $TAG ya existe en remote. Incrementa la versión en ai-knowledge.php"
        exit 1
    fi
fi

# ── Verificar rama, cambios pendientes y que todo esté subido ────────────────

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" != "$BRANCH_DEV" ]; then
    echo "Error: debes estar en la rama $BRANCH_DEV (rama actual: $CURRENT_BRANCH)"
    exit 1
fi

if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "Error: hay cambios sin commitear en $BRANCH_DEV. Haz commit y push antes de hacer release."
    exit 1
fi

git fetch origin "+refs/heads/$BRANCH_DEV:refs/remotes/origin/$BRANCH_DEV" --quiet
if [ "$(git rev-parse HEAD)" != "$(git rev-parse origin/$BRANCH_DEV)" ]; then
    echo "Error: $BRANCH_DEV local y origin/$BRANCH_DEV no coinciden."
    echo "  La release se construye desde lo YA SUBIDO. Haz git push (o git pull) y vuelve a intentarlo."
    exit 1
fi

# Archivos nuevos sin versionar: no se pierden (el script no toca tu carpeta),
# pero tampoco entran en la release. Aviso para que decidas si los subes.
UNTRACKED=$(git ls-files --others --exclude-standard | head -20)
if [ -n "$UNTRACKED" ]; then
    echo "Aviso: hay archivos sin versionar (no van en la release y NO se tocan):"
    echo "$UNTRACKED" | sed 's/^/    /'
fi

# ── Extraer changelog de CHANGELOG.md ─────────────────────────────────────────

CHANGELOG_BODY=$(awk "/^## \[$VERSION\]/{found=1; next} found && /^## \[/{exit} found{print}" CHANGELOG.md | sed '/^[[:space:]]*$/d' | head -60)
if [ -z "$CHANGELOG_BODY" ]; then
    CHANGELOG_BODY="Release $VERSION"
fi

# ── Clon temporal: aquí ocurre todo lo destructivo ───────────────────────────

WORK_DIR=$(mktemp -d)
trap 'rm -rf "$WORK_DIR"' EXIT
CLONE_DIR="$WORK_DIR/repo"
ZIP_DIR="$WORK_DIR/$PLUGIN_FOLDER"

REMOTE_URL=$(git remote get-url origin)
git clone --quiet --no-hardlinks "$REPO_DIR" "$CLONE_DIR"
git -C "$CLONE_DIR" remote set-url origin "$REMOTE_URL"
git -C "$CLONE_DIR" config user.name "$(git config user.name)"
git -C "$CLONE_DIR" config user.email "$(git config user.email)"
git -C "$CLONE_DIR" fetch origin "+refs/heads/*:refs/remotes/origin/*" --quiet

# ── Preparar rama release (limpia, sin archivos dev) ─────────────────────────

echo "[1/5] Preparando rama $BRANCH_RELEASE (en clon temporal)..."

git -C "$CLONE_DIR" checkout -q -B "$BRANCH_RELEASE" "origin/$BRANCH_DEV"

# Eliminar archivos que no deben ir en el ZIP de producción (solo en el clon)
rm -rf "$CLONE_DIR/_dev" "$CLONE_DIR/.kilo"
rm -f "$CLONE_DIR/deploy-release.sh" "$CLONE_DIR/.env.local" "$CLONE_DIR/.env" "$CLONE_DIR"/*.zip
find "$CLONE_DIR" -name '.DS_Store' -not -path '*/.git/*' -delete 2>/dev/null || true

git -C "$CLONE_DIR" add -A
git -C "$CLONE_DIR" commit -q -m "Release $TAG" || true   # "|| true" por si no hay cambios que commitear

if [ "$DRY_RUN" -eq 1 ]; then
    echo "    (prueba) no se sube la rama $BRANCH_RELEASE"
else
    git -C "$CLONE_DIR" push origin "$BRANCH_RELEASE" --force
    echo "    Rama $BRANCH_RELEASE lista"
fi

# ── Crear ZIP con carpeta ai-knowledge/ dentro ────────────────────────────────

echo "[2/5] Creando ZIP..."

mkdir -p "$ZIP_DIR"
git -C "$CLONE_DIR" archive "$BRANCH_RELEASE" | tar -x -C "$ZIP_DIR"

(cd "$WORK_DIR" && zip -r ai-knowledge.zip "$PLUGIN_FOLDER/" --quiet)
ZIP_PATH="$WORK_DIR/ai-knowledge.zip"
ZIP_SIZE=$(du -sh "$ZIP_PATH" | cut -f1)
echo "    ZIP: ai-knowledge.zip ($ZIP_SIZE)"

if unzip -l "$ZIP_PATH" | grep -q "$PLUGIN_FOLDER/_dev/"; then
    echo "Error: el ZIP contiene _dev/. Se aborta."
    exit 1
fi

if [ "$DRY_RUN" -eq 1 ]; then
    echo ""
    echo "━━━ PRUEBA OK: no se ha publicado nada y tu carpeta no se ha tocado ━━━"
    echo "  Versión: $TAG · ZIP sin _dev/ · $ZIP_SIZE"
    echo "  Changelog que iría en la release:"
    echo "$CHANGELOG_BODY" | sed 's/^/    /'
    exit 0
fi

# ── Crear release en GitHub ───────────────────────────────────────────────────

echo "[3/5] Creando release en GitHub ($TAG)..."

TEMP_CL=$(mktemp)
printf '%s' "$CHANGELOG_BODY" > "$TEMP_CL"

RELEASE_JSON=$(python3 - "$TEMP_CL" "$TAG" "$BRANCH_RELEASE" <<'PYEOF'
import json, sys
cl_file, tag, branch = sys.argv[1], sys.argv[2], sys.argv[3]
with open(cl_file) as f:
    body = f.read().strip()
print(json.dumps({
    'tag_name':         tag,
    'target_commitish': branch,
    'name':             tag,
    'body':             body,
    'draft':            False,
    'prerelease':       False
}))
PYEOF
)
rm -f "$TEMP_CL"

RELEASE_RESPONSE=$(curl -sf -X POST \
    -H "Authorization: token $GITHUB_TOKEN" \
    -H "Accept: application/vnd.github.v3+json" \
    -H "Content-Type: application/json" \
    https://api.github.com/repos/$REPO/releases \
    -d "$RELEASE_JSON")

RELEASE_ID=$(echo "$RELEASE_RESPONSE" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('id',''))" 2>/dev/null)

if [ -z "$RELEASE_ID" ]; then
    echo "Error al crear la release en GitHub:"
    echo "$RELEASE_RESPONSE"
    exit 1
fi

echo "    Release ID: $RELEASE_ID"

# ── Subir ai-knowledge.zip como asset ─────────────────────────────────────────

echo "[4/5] Subiendo ai-knowledge.zip..."

curl -sf -X POST \
    -H "Authorization: token $GITHUB_TOKEN" \
    -H "Content-Type: application/zip" \
    --data-binary @"$ZIP_PATH" \
    "https://uploads.github.com/repos/$REPO/releases/$RELEASE_ID/assets?name=ai-knowledge.zip" > /dev/null

echo "    ZIP subido"

# ── Fusionar release en main (en el clon temporal) ────────────────────────────
# El tag $TAG ya lo creó GitHub al crear la release (paso 3): no se crea otro
# en local ni se sube con --tags, porque el remoto lo rechazaría.

echo "[5/5] Mergeando $BRANCH_RELEASE en main..."

git -C "$CLONE_DIR" checkout -q -B main origin/main
git -C "$CLONE_DIR" merge "$BRANCH_RELEASE" --no-edit
git -C "$CLONE_DIR" push origin main

# Trae a tu repo el tag y el main remoto; no cambia tu rama ni tus archivos.
git fetch --tags --quiet || true

echo ""
echo "━━━ Release $TAG publicada correctamente ━━━"
echo "  Release:  https://github.com/$REPO/releases/tag/$TAG"
echo "  ZIP fijo: https://github.com/$REPO/releases/latest/download/ai-knowledge.zip"
echo ""
echo "Tu carpeta de trabajo no se ha tocado (sigues en $BRANCH_DEV)."
echo ""
echo "Próximo release:"
echo "  1. Incrementa la versión en ai-knowledge.php, readme.txt y CHANGELOG.md"
echo "  2. Haz commit y push en $BRANCH_DEV"
echo "  3. Ejecuta ./_dev/deploy-release.sh (o con --dry-run para probar)"
echo ""
