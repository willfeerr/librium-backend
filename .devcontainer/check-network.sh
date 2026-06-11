#!/bin/sh
set -eu

cd "$(dirname "$0")/.."

api_port="${BOOKMARKET_API_PORT:-8000}"
db_port="${BOOKMARKET_DB_PORT:-5433}"

echo "== BookMarket Dev Container network check =="
echo

if [ -f /.dockerenv ]; then
  echo "Running inside container."
  echo
  echo "Container API check:"
  curl -fsS "http://127.0.0.1:8000/api/health" || {
    echo
    echo "FAIL: Laravel is not responding inside the app container."
    echo "Check: ps aux | grep 'artisan serve'"
    exit 1
  }
  echo
  echo "OK: Laravel responds inside the container."
  echo
  echo "Now run this from the SSH remote host, outside the container:"
  echo "  sh .devcontainer/check-network.sh"
  exit 0
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "FAIL: docker command not found on this host."
  exit 1
fi

echo "Docker services:"
docker compose -f .devcontainer/docker-compose.yml ps
echo

echo "Published app port:"
docker compose -f .devcontainer/docker-compose.yml port app 8000 || true
echo

echo "Remote host API check:"
if curl -fsS "http://127.0.0.1:${api_port}/api/health"; then
  echo
  echo "OK: API responds on the SSH remote host at 127.0.0.1:${api_port}."
else
  echo
  echo "FAIL: API does not respond on the SSH remote host at 127.0.0.1:${api_port}."
  echo "Check app logs:"
  echo "  docker compose -f .devcontainer/docker-compose.yml logs -f app"
  exit 1
fi

echo
echo "Remote host PostgreSQL port:"
echo "  127.0.0.1:${db_port} -> container postgres:5432"
echo

remote_user="$(id -un 2>/dev/null || echo USER)"
remote_host="$(hostname -f 2>/dev/null || hostname 2>/dev/null || echo HOST)"

echo "If this host check passed but your local browser cannot open the API,"
echo "run this on your local machine, not inside the SSH session:"
echo
echo "  ssh -N -L ${api_port}:127.0.0.1:${api_port} ${remote_user}@${remote_host}"
echo
echo "Then open:"
echo "  http://localhost:${api_port}/api/docs"
