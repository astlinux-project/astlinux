#!/bin/bash

LOCKFILE="/var/lock/aif_dyndns_host_open.lock"

ARGSFILE="/var/tmp/aif_dyndns_host_open.args"

start_run()
{
  local num file time arg args ARGS IFS
  
  ARGS="$@"
  
  # Robust 'bash' method of creating/testing for a lockfile
  if ! ( set -o noclobber; echo "$$" > "$LOCKFILE" ) 2>/dev/null; then
    echo "$ARGS" > "$ARGSFILE"
    echo "dyndns-host-open-helper: already running, lockfile \"$LOCKFILE\" exists, process id: $(cat "$LOCKFILE")."
    return 9
  fi
  
  trap 'rm -f "$LOCKFILE" "$ARGSFILE"; exit $?' INT TERM EXIT
  
  echo "$ARGS" > "$ARGSFILE"
  
  # Delay to allow firewall script to complete
  idle_wait 45
  
  while [ -f "$ARGSFILE" ]; do
  
    # Check whether chains exists
    if ! check_for_chain DYNDNS_CHAIN; then
      log_msg "DYNDNS_CHAIN does not exist"
      break
    fi
    
    ARGS="$(cat "$ARGSFILE")"
    
    file=""
    args=""
    num=0
    unset IFS
    for arg in $ARGS; do
      num=$((num+1))
      case "$num" in
        1) time="$arg" ;;
        2) file="$arg" ;;
        *) args="${args}${args:+ }$arg" ;;
      esac
    done
    
    if [ ! -f "$file" ]; then
      log_msg "Input rules file $file does not exist"
      break
    fi
    
    apply_rules "$file"
    
    # Idle - interrupted if ARGSFILE is deleted
    idle_wait $time
  done
  
  rm -f "$LOCKFILE" "$ARGSFILE"
  trap - INT TERM EXIT
  
  return 0
}

stop()
{

  rm -f "$ARGSFILE"

  # If the background start_run() is in idle_wait() this ensures a clean stop.
  sleep 1
  # If start_run() is not in idle_wait() we deal with that as well.
  # We could loop while LOCKFILE exists, but doesn't seem necessary.
}

status()
{

  echo "  DynDNS Open Hosts:"
  echo "  =============================="
  ip4tables -n -L DYNDNS_CHAIN | sed -n -e 's/^ACCEPT.*$/  &/p'
  echo "  ------------------------------"
  echo ""
  
}

apply_rules()
{
  local cnt rule end_cnt IFS
  
  # Count existing rules, to be deleted later
  cnt=$(ip4tables -n -L DYNDNS_CHAIN | grep -c '^ACCEPT')

  unset IFS
  cat "$1" | while read rule; do
    ip4tables $rule
    if [ $? -ne 0 -a $cnt -gt 0 ]; then
      # Keep pre-existing rules, delete incomplete set
      end_cnt=$(ip4tables -n -L DYNDNS_CHAIN | grep -c '^ACCEPT')
      while [ $end_cnt -gt $cnt ]; do
        ip4tables -D DYNDNS_CHAIN $end_cnt
        end_cnt=$((end_cnt-1))
      done
      break
    fi
  done
  
  end_cnt=$(ip4tables -n -L DYNDNS_CHAIN | grep -c '^ACCEPT')
  
  if [ $end_cnt -gt $cnt ]; then
    # Delete pre-existing rules
    while [ $cnt -gt 0 ]; do
      ip4tables -D DYNDNS_CHAIN 1
      cnt=$((cnt-1))
    done
  fi
}

idle_wait()
{
  local time="$1" cnt=0

  while [ -f "$ARGSFILE" -a $cnt -lt $time ]; do
    cnt=$((cnt+1))
    sleep 1
  done
}

check_for_chain()
{
  local err

  ip4tables -n -L "$1" >/dev/null 2>&1
  err=$?

  return $err
}

ip4tables()
{
  local result retval

  result="$($IP4TABLES "$@" 2>&1)"
  retval=$?
  
  if [ $retval -ne 0 ]; then
    log_msg "$IP4TABLES: ($retval) $result"
  elif [ -n "$result" ]; then
    echo "$result"
  fi

  return $retval
}

log_msg()
{
  logger -t "firewall: dyndns-host-open" -p kern.info "$1"
  echo "$1" >&2
}

# main

ACTION="$1"

IP4TABLES="$2"
if [ -z "$IP4TABLES" -o "$IP4TABLES" = "ip4tables" ]; then
  ACTION=""
fi

shift 2

case $ACTION in

start)
  if [ -z "$1" -o -z "$2" ]; then
    echo "Usage: dyndns-host-open-helper start ip4tables_path time rules_file"
    exit 1
  fi
  start_run "$@"
  ;;

stop)
  stop
  ;;

status)
  status
  ;;

*)
  echo "Usage: dyndns-host-open-helper start|stop|status ip4tables_path"
  echo "                           [ time rules_file ]"
  exit 1
  ;;
  
esac

