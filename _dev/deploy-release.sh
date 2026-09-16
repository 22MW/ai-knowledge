#!/bin/bash
set -e

# ─────────────────────────────────────────────────────────────────────────────
# deploy-release.sh — Crea release de AI Knowledge & Visibility en GitHub
#
# Flujo:
#   1. Lee la versión del header de ai-knowledge.php
#   2. Verifica que el tag no exista ya en remote
#   3. Extrae el changelog de CHANGELOG.md
#   4. Crea/sobreescribe la rama "release" desde knowBaseDev sin archivos dev
#   5. Crea un ZIP con la carpeta ai-knowledge/ dentro
#   6. Crea la release en GitHub y sube el ZIP como asset
#
# Requisito: GITHUB_TOKEN en _dev/.env, .env.local, raíz del workspace o entorno
# Uso recomendado: cd .../ai-knowledge && ./_dev/deploy-release.sh
# ─────────────────────────────────────────────────────────────────────────────

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

if [ -z "$GITHUB_TOKEN" ]; then
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
    echo "Error: el tag $TAG ya existe en remote. Incrementa la versión en ai-knowledge.php"
    exit 1
fi

# ── Verificar que estamos en knowBaseDev y sin cambios sin commitear ─────────

CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" != "$BRANCH_DEV" ]; then
    echo "Error: debes estar en la rama $BRANCH_DEV (rama actual: $CURRENT_BRANCH)"
    exit 1
fi

if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "Error: hay cambios sin commitear en $BRANCH_DEV. Haz commit antes de hacer release."
    exit 1
fi

# ── Extraer changelog de CHANGELOG.md ─────────────────────────────────────────

CHANGELOG_BODY=$(awk "/^## \[$VERSION\]/{found=1; next} found && /^## \[/{exit} found{print}" CHANGELOG.md | sed '/^[[:space:]]*$/d' | head -60)
if [ -z "$CHANGELOG_BODY" ]; then
    CHANGELOG_BODY="Release $VERSION"
fi

# ── Preparar rama release (limpia, sin archivos dev) ─────────────────────────

echo "[1/5] Preparando rama $BRANCH_RELEASE..."

git checkout -B "$BRANCH_RELEASE" "origin/$BRANCH_DEV" 2>/dev/null || git checkout -B "$BRANCH_RELEASE" "$BRANCH_DEV"

# Eliminar archivos que no deben ir en el ZIP de producción
rm -rf _dev .kilo
rm -f deploy-release.sh .env.local .env
find . -name '.DS_Store' -delete 2>/dev/null || true

git add -A
git commit -m "Release $TAG" || true   # "|| true" por si no hay cambios que commitear
git push origin "$BRANCH_RELEASE" --force

echo "    Rama $BRANCH_RELEASE lista"

# ── Volver a la rama de desarrollo ───────────────────────────────────────────

git checkout "$BRANCH_DEV"

# ── Crear ZIP con carpeta ai-knowledge/ dentro ────────────────────────────────

echo "[2/5] Creando ZIP..."

TEMP_DIR=$(mktemp -d)
ZIP_DIR="$TEMP_DIR/$PLUGIN_FOLDER"
mkdir -p "$ZIP_DIR"

git archive "$BRANCH_RELEASE" | tar -x -C "$ZIP_DIR"

# Asegurarse de que el archivo zip se llame ai-knowledge.zip
cd "$TEMP_DIR"
zip -r ai-knowledge.zip "$PLUGIN_FOLDER/" --quiet
ZIP_PATH="$TEMP_DIR/ai-knowledge.zip"
ZIP_SIZE=$(du -sh "$ZIP_PATH" | cut -f1)
echo "    ZIP: ai-knowledge.zip ($ZIP_SIZE)"

cd "$REPO_DIR"

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
    rm -rf "$TEMP_DIR"
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

rm -rf "$TEMP_DIR"
echo "    ZIP subido"

# ── Tag en main (opcional pero recomendado para trazabilidad) ─────────────────

echo "[5/5] Mergeando $BRANCH_RELEASE en main y creando tag..."

git checkout main
git merge "$BRANCH_RELEASE" --no-edit
git tag "$TAG"
git push origin main --tags
git checkout "$BRANCH_DEV"

echo ""
echo "━━━ Release $TAG publicada correctamente ━━━"
echo "  Release:  https://github.com/$REPO/releases/tag/$TAG"
echo "  ZIP fijo: https://github.com/$REPO/releases/latest/download/ai-knowledge.zip"
echo ""
echo "Próximos pasos:"
echo "  1. Incrementa la versión en ai-knowledge.php y CHANGELOG.md"
echo "  2. Commitea los cambios en $BRANCH_DEV"
echo "  3. Ejecuta ./deploy-release.sh cuando esté listo"
echo ""
