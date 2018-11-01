#!/bin/bash

MAILQ="/var/spool/mail"

LOCKFILE="/var/lock/msmtp.lock"

# 5 minutes, wait on error
ERROR_WAIT=300

# msmtp error values from src/tools.h (sysexits.h)
EX_OK=0
EX_USAGE=64

flush()
{
  local file count wait msmtp_num msmtp_str IFS

  if [ ! -d "$MAILQ" ]; then
    log_msg "Mail queue directory \"$MAILQ\" not found"
    return 1
  fi

  # Robust 'bash' method of creating/testing for a lockfile
  if ! ( set -o noclobber; echo "$$" > "$LOCKFILE" ) 2>/dev/null; then
    echo "msmtpqueue: already running, lockfile \"$LOCKFILE\" exists, process id: $(cat "$LOCKFILE")."
    return 9
  fi

  # Load 'sleep' builtin if it exists
  if [ -f /usr/lib/bash/sleep ]; then
    enable -f /usr/lib/bash/sleep sleep
  fi

  trap 'rm -f "$LOCKFILE"; exit $?' INT TERM EXIT

  while true; do

    wait=1
    if msmtp_status stopped; then
      log_msg "Mail system is stopped. Use 'msmtpqueue' to display the mail queue."
      break
    fi

    count=$(ls -1 "$MAILQ"/*.msmtp 2>/dev/null | wc -l)
    if [ $count -eq 0 ]; then
      break
    fi

    if msmtp_status reachable; then
      unset IFS
      for file in $(ls -1 "$MAILQ"/*.msmtp 2>/dev/null); do
        MAILFILE="${file%.msmtp}.mail"
        if [ -f "$MAILFILE" ]; then
          msmtp_str="$(msmtp $(cat "$file") < "$MAILFILE" 2>&1)"
          msmtp_num=$?
          if [ $msmtp_num -eq $EX_OK ]; then
            rm -f "$MAILFILE" "$file"
            log_msg "Success: $file"
          else
            wait=$ERROR_WAIT
            log_msg "($msmtp_num) $msmtp_str"
            if [ $msmtp_num -eq $EX_USAGE ]; then    # error code which is not queue worthy
              rm -f "$MAILFILE" "$file"
              log_msg "Deleted mail queue ${file%.msmtp} msmtp/mail pair."
            else
              log_msg "Failure: Keeping mail queue ${file%.msmtp} msmtp/mail pair."
            fi
          fi
        else
          rm -f "$file"
          log_msg "Failure: No matching 'mail' file for $file"
        fi
      done
    else
      wait=$ERROR_WAIT
      log_msg "Failure: Mail server not reachable"
    fi

    sleep $wait
  done

  rm -f "$LOCKFILE"
  trap - INT TERM EXIT

  return 0
}

print()
{
  local file count IFS

  if [ ! -d "$MAILQ" ]; then
    echo "Mail queue directory \"$MAILQ\" not found"
    return 1
  fi

  if msmtp_status stopped; then
    echo "=============================="
    echo "   Mail system is stopped!    "
    echo "=============================="
    echo ""
  fi

  count=$(ls -1 "$MAILQ"/*.msmtp 2>/dev/null | wc -l)

  if [ $count -eq 0 ]; then
    echo "Mail queue is empty"
  else
    count=0
    unset IFS
    for file in $(ls -1 "$MAILQ"/*.msmtp 2>/dev/null); do
      count=$((count+1))
      MAILFILE="${file%.msmtp}.mail"
      echo "=============================="
      echo "Index: $count -- File: $file"
      echo "Args: $(cat "$file")"
      echo "Size: $(ls -lh "$MAILFILE" | awk '{ print $5; nextfile; }')"
      echo "------------------------------"
      grep -i -e '^From:' -e '^To:' -e '^Subject:' "$MAILFILE"
      echo ""
    done
  fi

  return 0
}

delete()
{
  local file

  if [ ! -d "$MAILQ" ]; then
    echo "Mail queue directory \"$MAILQ\" not found"
    return 1
  fi

  file="${1#$MAILQ/}"
  file="${file%.msmtp}"

  if [ ! -f "$MAILQ/$file.msmtp" ]; then
    echo "msmtpqueue: no file $MAILQ/$file.msmtp"
    return 1
  fi

  rm -f "$MAILQ/$file.msmtp" "$MAILQ/$file.mail"

  echo "Deleted mail queue $MAILQ/$file msmtp/mail pair."
  return 0
}

msmtp_status()
{
  local host=""

  if [ -f /etc/msmtprc ]; then
    host="$(awk '/^host / { print $2; nextfile; }' /etc/msmtprc)"
  elif [ "$1" = "stopped" ]; then
    return 0
  fi

  if [ -n "$host" ]; then
    if [ "$1" = "reachable" ]; then
      if nslookup "$host" >/dev/null; then
        return 0
      fi
    fi
  fi

  return 1
}

log_msg()
{
  logger -t msmtpqueue -p mail.info "$1"
  echo "$1" >&2
}

# main

# Must be root to proceed
if [ $EUID -ne 0 ]; then
  echo "msmtpqueue: Permission denied" >&2
  exit 2
fi

case $1 in

-f)
  flush
  ;;

-p|'')
  print
  ;;

delete)
  if [ -z "$2" ]; then
    echo "Usage: msmtpqueue delete filename.msmtp"
    exit 1
  fi
  delete "$2"
  ;;

*)
  echo "Usage: msmtpqueue [ -f -p ] [ delete filename.msmtp ]"
  exit 1
  ;;

esac

