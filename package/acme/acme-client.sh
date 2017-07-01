#!/usr/bin/env bash

. /etc/rc.conf

ACME_REPO="/stat/etc/acme"

ACME_PROG="$ACME_REPO/acme.sh"

ACME_WORKING_DIR="/mnt/kd/acme"

ACME_OPTS="--home $ACME_WORKING_DIR --useragent AstLinux"

LOCKFILE="/var/lock/acme-client.lock"

if [ ! -x "$ACME_PROG" ]; then
  echo "acme-client: executable file \"$ACME_PROG\" not found." >&2
  exit 1
fi

if [ ! -d "$ACME_WORKING_DIR" ]; then
  mkdir "$ACME_WORKING_DIR"
fi

if ! cd "$ACME_WORKING_DIR"; then
  exit 1
fi

CRON_FILE="/var/spool/cron/crontabs/root"

CRON_UPDATE="/var/spool/cron/crontabs/cron.update"

is_cron_entry()
{
  grep -q '/usr/sbin/acme-client ' "$CRON_FILE"
}

add_cron_entry()
{
  local min

  if is_cron_entry; then
    echo "acme-client: cron entry previously exists, no changes."
    return
  fi

  # randomize minutes in the range of 4-56, 53 is a prime number
  min=$(( RANDOM % 53 + 4 ))

  echo "$min 1 * * * /usr/sbin/acme-client --cron >/dev/null 2>&1" >> "$CRON_FILE"
  echo 'root' >> "$CRON_UPDATE"

  if is_cron_entry; then
    echo "acme-client: Successfully added cron entry."
  else
    echo "acme-client: Failed adding cron entry."
  fi
}

del_cron_entry()
{
  if ! is_cron_entry; then
    echo "acme-client: cron entry does not exist, no changes."
    return
  fi

  sed -i -e '/\/usr\/sbin\/acme-client /d' "$CRON_FILE"
  echo 'root' >> "$CRON_UPDATE"

  if ! is_cron_entry; then
    echo "acme-client: Successfully removed cron entry."
  else
    echo "acme-client: Failed removing cron entry."
  fi
}

no_op_arg()
{
  echo "acme-client: The $1 option has been disabled."
}

issue_without_dns()
{
  echo "acme-client: The '--issue' option also requires the '--dns' option."
}

special_arg_handler()
{
  local arg skip issue dns IFS

  issue=0
  dns=0

  IFS='~'  # expand command-line args using the unique 'tilde' character
  for arg in $*; do
    skip=0
    case "$arg" in
      --installcronjob|--install-cronjob)
        add_cron_entry ; skip=1 ;;
      --uninstallcronjob|--uninstall-cronjob)
        del_cron_entry ; skip=1 ;;
      --install)
        no_op_arg "$arg" ; skip=1 ;;
      --uninstall)
        no_op_arg "$arg" ; skip=1 ;;
      --upgrade)
        no_op_arg "$arg" ; skip=1 ;;
      --auto-upgrade)
        no_op_arg "$arg" ; skip=1 ;;
      --issue)
        issue=1 ;;
      --dns)
        dns=1 ;;
    esac
    if [ $skip -eq 1 ]; then
      return 0
    fi
  done

  if [ $issue -eq 1 -a $dns -ne 1 ]; then
    issue_without_dns
    return 0
  fi

  return 1
}

add_account_opts()
{
  local file="$1" line opt value IFS

  IFS=$'\n'
  for line in $(cat "$file" | sed -e 's/#.*//' -e 's/ *$//' -e '/^$/d'); do
    opt="$(echo "$line" | awk -F' ' '/^[a-z]/ { print $1; }')"
    value="$(echo "$line" | awk -F' ' '/^[a-z]/ { print $2; }')"
    if [ -n "$opt" ]; then
      if [ -n "$value" ]; then
        ACME_OPTS="$ACME_OPTS --$opt $value"
      else
        ACME_OPTS="$ACME_OPTS --$opt"
      fi
    fi
  done
}

if special_arg_handler "$@"; then
  exit 0
fi

# Robust 'bash' method of creating/testing for a lockfile
if ! ( set -o noclobber; echo "$$" > "$LOCKFILE" ) 2>/dev/null; then
  echo "acme-client: already running, lockfile \"$LOCKFILE\" exists, process id: $(cat "$LOCKFILE")." >&2
  exit 9
fi

accountemail="$ACME_ACCOUNT_EMAIL"

# Extract from possible <a@b.tld> format
accountemail="${accountemail##*<}"
accountemail="${accountemail%%>*}"

if [ -n "$accountemail" ]; then
  ACME_OPTS="$ACME_OPTS --accountemail $accountemail"
fi

if [ -f "$ACME_WORKING_DIR/account.opts" ]; then
  add_account_opts "$ACME_WORKING_DIR/account.opts"
fi

trap 'rm -f "$LOCKFILE"; exit $?' INT TERM EXIT

$ACME_PROG $ACME_OPTS "$@"
rtn=$?

rm -f "$LOCKFILE"
trap - INT TERM EXIT

exit $rtn
