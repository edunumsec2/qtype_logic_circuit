#!/usr/bin/env bash
# Regenerates amd/build/*.min.js and *.min.js.map from amd/src/*.js
# Usage: ./build-amd.sh [module-name]
#   With no argument: rebuilds all modules found in amd/src/
#   With argument:    rebuilds only amd/src/<module-name>.js
#                     e.g. ./build-amd.sh save-result
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_DIR="$SCRIPT_DIR/amd/src"
BUILD_DIR="$SCRIPT_DIR/amd/build"
PLUGIN="qtype_logiccircuit"

build_module() {
    local base="$1"
    local src="$SRC_DIR/${base}.js"
    local modname="${PLUGIN}/${base}"

    if [[ ! -f "$src" ]]; then
        echo "ERROR: $src not found" >&2
        return 1
    fi

    echo "Building $modname ..."

    # Terser requires a named define() to satisfy Moodle's RequireJS.
    # Inject the module name if the file starts with an anonymous define().
    local tmp
    tmp="$(mktemp /tmp/${base}.XXXXXX.js)"
    perl -0pe "s/^define\(/define(\"${modname//\//\\/}\", /m" "$src" > "$tmp"

    npx --yes terser "$tmp" \
        --compress \
        --mangle \
        --comments '/@module|@package|@copyright|@license/' \
        --source-map "filename='${base}.min.js',url='${base}.min.js.map',includeSources" \
        -o "${BUILD_DIR}/${base}.min.js"

    # Fix the sources path in the map to point back to ../src/<module>.js
    node -e "
        const fs = require('fs');
        const p = '${BUILD_DIR}/${base}.min.js.map';
        const m = JSON.parse(fs.readFileSync(p, 'utf8'));
        m.sources = ['../src/${base}.js'];
        fs.writeFileSync(p, JSON.stringify(m));
    "

    rm -f "$tmp"
    echo "  -> ${BUILD_DIR}/${base}.min.js"
    echo "  -> ${BUILD_DIR}/${base}.min.js.map"
}

if [[ $# -gt 0 ]]; then
    build_module "$1"
else
    for src in "$SRC_DIR"/*.js; do
        base="$(basename "$src" .js)"
        build_module "$base"
    done
fi

echo "Done."
