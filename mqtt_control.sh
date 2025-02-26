#!/bin/bash

DAEMON="/usr/bin/php"
DAEMON_ARGS="/var/www/html/iotgateway/mqtt_listener.php"
DAEMON_USER="www-data"
NAME="mqtt_listener"
PIDFILE="/var/www/html/iotgateway/mqtt_listener.pid"

case "$1" in
  start)
    echo "Starting $NAME..."
    if [ -f "$PIDFILE" ]; then
      PID=$(cat "$PIDFILE")
      if kill -0 $PID > /dev/null 2>&1; then
        echo "$NAME is already running (PID: $PID)"
        exit 1
      else
        rm -f "$PIDFILE"
      fi
    fi
    
    sudo -u $DAEMON_USER $DAEMON $DAEMON_ARGS > /dev/null 2>&1 &
    echo $! > "$PIDFILE"
    chown $DAEMON_USER:$DAEMON_USER "$PIDFILE"
    echo "$NAME started"
    ;;
    
  stop)
    echo "Stopping $NAME..."
    if [ -f "$PIDFILE" ]; then
      PID=$(cat "$PIDFILE")
      kill $PID
      rm -f "$PIDFILE"
      echo "$NAME stopped"
    else
      echo "$NAME is not running"
    fi
    
    # Kill any remaining processes just to be sure
    PIDS=$(pgrep -f "php.*mqtt_listener")
    if [ ! -z "$PIDS" ]; then
      kill $PIDS
      echo "Killed remaining processes"
    fi
    ;;
    
  restart)
    $0 stop
    sleep 2
    $0 start
    ;;
    
  status)
    if [ -f "$PIDFILE" ]; then
      PID=$(cat "$PIDFILE")
      if kill -0 $PID > /dev/null 2>&1; then
        echo "$NAME is running (PID: $PID)"
      else
        echo "$NAME is not running (stale PID file)"
      fi
    else
      echo "$NAME is not running"
    fi
    ;;
    
  *)
    echo "Usage: $0 {start|stop|restart|status}"
    exit 1
    ;;
esac

exit 0
