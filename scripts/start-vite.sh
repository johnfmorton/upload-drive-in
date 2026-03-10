#!/usr/bin/env bash
#
# Smart Vite dev server startup with port conflict handling.
# Detects if the default port is in use and offers interactive choices.
#

PORT=3000
export NODE_ENV=dev

port_in_use() {
  (echo >/dev/tcp/localhost/"$1") 2>/dev/null
}

show_port_process() {
  if command -v lsof &>/dev/null; then
    lsof -i :"$1" 2>/dev/null
  elif command -v ss &>/dev/null; then
    ss -tlnp "sport = :$1" 2>/dev/null
  else
    echo "(unable to identify process — neither lsof nor ss available)"
  fi
}

get_port_pid() {
  local pid=""
  if command -v lsof &>/dev/null; then
    pid=$(lsof -ti :"$1" 2>/dev/null | head -1)
  fi
  if [ -z "$pid" ] && command -v ss &>/dev/null; then
    pid=$(ss -tlnp "sport = :$1" 2>/dev/null | grep -oP 'pid=\K[0-9]+' | head -1)
  fi
  echo "$pid"
}

kill_port_process() {
  local pid
  pid=$(get_port_pid "$1")
  if [ -z "$pid" ]; then
    echo "Could not find PID for port $1."
    return 1
  fi
  echo "Killing process $pid..."
  kill "$pid" 2>/dev/null
  sleep 1
  if port_in_use "$1"; then
    echo "Process still running, sending SIGKILL..."
    kill -9 "$pid" 2>/dev/null
    sleep 1
  fi
  if port_in_use "$1"; then
    echo "Failed to free port $1."
    return 1
  fi
  echo "Port $1 is now free."
}

find_available_port() {
  local p=$(( $1 + 1 ))
  while port_in_use "$p"; do
    p=$(( p + 1 ))
  done
  echo "$p"
}

# Main
if ! port_in_use "$PORT"; then
  echo "Starting Vite on port $PORT..."
  exec vite
fi

echo ""
echo "⚠  Port $PORT is already in use!"
echo ""
show_port_process "$PORT"
echo ""
echo "What would you like to do?"
echo "  1) Kill the process on port $PORT and start Vite"
echo "  2) Start Vite on the next available port"
echo "  3) Quit"
echo ""
read -rp "Choose [1/2/3]: " choice

case "$choice" in
  1)
    if kill_port_process "$PORT"; then
      echo "Starting Vite on port $PORT..."
      exec vite
    else
      echo "Could not free port $PORT. Exiting."
      exit 1
    fi
    ;;
  2)
    ALT_PORT=$(find_available_port "$PORT")
    echo "Starting Vite on port $ALT_PORT..."
    exec vite --port "$ALT_PORT" --strictPort false
    ;;
  3)
    echo "Exiting."
    exit 0
    ;;
  *)
    echo "Invalid choice. Exiting."
    exit 1
    ;;
esac
