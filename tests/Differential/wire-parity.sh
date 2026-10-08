#!/usr/bin/env bash
#
# Wire-parity harness: proves 2.x sends byte-identical requests to v1.4.
#
# Without a gateway sandbox this is the strongest available evidence that the
# rewritten signing and transport path did not change what the bank receives.
# Both versions post through real curl to a local capture server, so what is
# compared is the actual byte stream, not an internal representation.
#
#   ./tests/Differential/wire-parity.sh
#
# Requires: php with curl, git. Exit 0 means parity.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LEGACY_TAG="${LEGACY_TAG:-v1.4}"
PORT="${PORT:-8599}"
WORK="$(mktemp -d)"

cleanup() {
  if [[ -n "${SERVER_PID:-}" ]] && kill -0 "$SERVER_PID" 2>/dev/null; then
    kill "$SERVER_PID" 2>/dev/null || true
    wait "$SERVER_PID" 2>/dev/null || true
  fi
  # KEEP_CAPTURES=1 leaves both capture sets on disk for inspection.
  if [[ -n "${KEEP_CAPTURES:-}" ]]; then
    echo
    echo "captures kept in $WORK"
  else
    rm -rf "$WORK"
  fi
}
trap cleanup EXIT

echo "wire-parity: ${LEGACY_TAG} vs working tree"
echo

# Identical deployment data for both sides. These are harness values, not
# credentials -- the capture server accepts anything.
export SAMPATH_SERVICE_ENDPOINT="http://127.0.0.1:${PORT}/rest/service/proxy"
export SAMPATH_AUTHTOKEN="00000000-0000-4000-8000-000000000000"
export SAMPATH_HMAC="wire-parity-harness-secret"
export SAMPATH_RETURN_URL="http://merchant.invalid/return"
export SAMPATH_TOKENIZE_CLIENT_ID="99990001"
export SAMPATH_PURCHASE_CLIENT_ID="99990002"
export SAMPATH_CURRENCY="LKR"

# 1 -- obtain the legacy source tree.
#      Normally extracted from the tag. LEGACY_SRC_DIR overrides that with an
#      already-extracted src/ directory, for environments where the git repo is
#      not reachable -- a container that mounts only the working tree, or a git
#      worktree, whose .git is a FILE pointing at a gitdir outside the mount.
mkdir -p "$WORK/legacy"

if [[ -n "${LEGACY_SRC_DIR:-}" ]]; then
  if [[ ! -d "$LEGACY_SRC_DIR" ]]; then
    echo "LEGACY_SRC_DIR=$LEGACY_SRC_DIR is not a directory" >&2
    exit 1
  fi
  cp -R "$LEGACY_SRC_DIR" "$WORK/legacy/src"
  echo "using pre-extracted legacy src/ from $LEGACY_SRC_DIR"
else
  if ! git -C "$ROOT" rev-parse --verify "$LEGACY_TAG" >/dev/null 2>&1; then
    echo "cannot resolve ${LEGACY_TAG} -- is this a full clone with tags?" >&2
    echo "(in CI use actions/checkout with fetch-depth: 0, or set LEGACY_SRC_DIR)" >&2
    exit 1
  fi
  git -C "$ROOT" archive "$LEGACY_TAG" src | tar -x -C "$WORK/legacy"
fi

if [[ ! -d "$WORK/legacy/src" ]]; then
  echo "legacy src/ was not produced" >&2
  exit 1
fi

echo "legacy source: ${LEGACY_TAG} ($(find "$WORK/legacy/src" -name '*.php' | wc -l | tr -d ' ') php files)"

# 2 -- start the capture server
mkdir -p "$WORK/legacy-caps" "$WORK/current-caps"
CAPTURE_DIR="$WORK/legacy-caps" php -S "127.0.0.1:${PORT}" \
  "$ROOT/tests/Differential/capture-server.php" >"$WORK/server.log" 2>&1 &
SERVER_PID=$!

for _ in $(seq 1 50); do
  if curl -fsS -o /dev/null "http://127.0.0.1:${PORT}/" 2>/dev/null; then break; fi
  sleep 0.2
done

if ! kill -0 "$SERVER_PID" 2>/dev/null; then
  echo "capture server failed to start:" >&2
  cat "$WORK/server.log" >&2
  exit 1
fi

# The readiness probe above is itself a request; discard it.
rm -f "$WORK"/legacy-caps/*.json

# 3 -- legacy run
echo
echo "running ${LEGACY_TAG} ..."
LEGACY_SRC="$WORK/legacy/src" CAPTURE_DIR="$WORK/legacy-caps" \
  php "$ROOT/tests/Differential/run-legacy.php"
echo "  captured $(find "$WORK/legacy-caps" -name '*.json' | wc -l | tr -d ' ') request(s)"

# 4 -- current run. The capture server reads CAPTURE_DIR per request, so
#      restart it pointed at the second directory.
kill "$SERVER_PID" 2>/dev/null || true
wait "$SERVER_PID" 2>/dev/null || true

CAPTURE_DIR="$WORK/current-caps" php -S "127.0.0.1:${PORT}" \
  "$ROOT/tests/Differential/capture-server.php" >>"$WORK/server.log" 2>&1 &
SERVER_PID=$!

for _ in $(seq 1 50); do
  if curl -fsS -o /dev/null "http://127.0.0.1:${PORT}/" 2>/dev/null; then break; fi
  sleep 0.2
done
rm -f "$WORK"/current-caps/*.json

echo
echo "running working tree ..."
LEGACY_CAPTURES="$WORK/legacy-caps" CAPTURE_DIR="$WORK/current-caps" \
  php "$ROOT/tests/Differential/run-current.php"
echo "  captured $(find "$WORK/current-caps" -name '*.json' | wc -l | tr -d ' ') request(s)"

# 5 -- compare
echo
echo "comparing ..."
php "$ROOT/tests/Differential/compare.php" "$WORK/legacy-caps" "$WORK/current-caps"
