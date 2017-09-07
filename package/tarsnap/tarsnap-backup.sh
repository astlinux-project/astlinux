#!/bin/bash
##
## tarsnap-backup
##
## Helper script to manage Tarsnap backups for the AstLinux Project
## 
## Copyright (C) 2017 Lonnie Abelbeck
##
## This is free software, licensed under the GNU General Public License
## version 3 as published by the Free Software Foundation; you can
## redistribute it and/or modify it under the terms of the GNU
## General Public License; and comes with ABSOLUTELY NO WARRANTY.

. /etc/rc.conf

TARSNAP_PROG="/usr/bin/tarsnap"

TARSNAP_KEYGEN_PROG="/usr/bin/tarsnap-keygen"

TARSNAP_DIR="/mnt/kd/tarsnap"

TARSNAP_CACHE_DIR="$TARSNAP_DIR/tarsnap-cache"

TARSNAP_KEY_FILE="$TARSNAP_DIR/tarsnap.key"

LOCKFILE="/var/lock/tarsnap-backup.lock"

if [ ! -x "$TARSNAP_PROG" ]; then
  echo "tarsnap-backup: executable file '$TARSNAP_PROG' not found." >&2
  exit 1
fi

if [ ! -d "$TARSNAP_CACHE_DIR" ]; then
  mkdir -p "$TARSNAP_CACHE_DIR"
fi

if ! cd "$TARSNAP_DIR"; then
  exit 1
fi

CRON_FILE="/var/spool/cron/crontabs/root"

CRON_UPDATE="/var/spool/cron/crontabs/cron.update"

is_cron_entry()
{
  grep -q '/usr/bin/tarsnap-backup ' "$CRON_FILE"
}

add_cron_entry()
{
  local min

  if is_cron_entry; then
    echo "tarsnap-backup: cron entry previously exists, no changes."
    return
  fi

  # randomize minutes in the range of 4-56, 53 is a prime number
  min=$(( RANDOM % 53 + 4 ))

  echo "$min 2 * * * /usr/bin/tarsnap-backup --cron >/dev/null 2>&1" >> "$CRON_FILE"
  echo 'root' >> "$CRON_UPDATE"

  if is_cron_entry; then
    echo "tarsnap-backup: Successfully added cron entry."
  else
    echo "tarsnap-backup: Failed adding cron entry."
  fi
}

del_cron_entry()
{
  if ! is_cron_entry; then
    echo "tarsnap-backup: cron entry does not exist, no changes."
    return
  fi

  sed -i -e '/\/usr\/bin\/tarsnap-backup /d' "$CRON_FILE"
  echo 'root' >> "$CRON_UPDATE"

  if ! is_cron_entry; then
    echo "tarsnap-backup: Successfully removed cron entry."
  else
    echo "tarsnap-backup: Failed removing cron entry."
  fi
}

tarsnap_keygen()
{
  local user="$1" machine="$2"

  if [ -f "$TARSNAP_KEY_FILE" ]; then
    echo "tarsnap-backup: '$TARSNAP_KEY_FILE' already exists, no changes."
    return 2
  fi

  $TARSNAP_KEYGEN_PROG --keyfile "$TARSNAP_KEY_FILE" --user "$user" --machine "$machine"
}

error_notify()
{
  local MESG="$1" dry_run="$2" TO notify_from IFS

  if [ $dry_run -eq 1 ]; then
    return 0
  fi

  logger -s -t tarsnap-backup -p kern.info "$MESG"

  notify_from="$BACKUP_NOTIFY_FROM"

  # Extract from possible <a@b.tld> format
  notify_from="${notify_from##*<}"
  notify_from="${notify_from%%>*}"

  if [ -z "$notify_from" -a -n "$SMTP_DOMAIN" ]; then
    notify_from="tarsnap-backup@$SMTP_DOMAIN"
  fi

  unset IFS
  for TO in $BACKUP_NOTIFY; do
    echo "To: ${TO}${notify_from:+
From: \"Backup-$HOSTNAME\" <$notify_from>}
Subject: Backup on '$HOSTNAME': $MESG

Backup on '$HOSTNAME': $MESG.

[Generated at $(date "+%H:%M:%S on %B %d, %Y")]" | \
    sendmail -t
  done
}

do_backup()
{
  local dry_run="$1" opts cd_dir dir dirs file files includes rtn IFS

  if [ $dry_run -eq 1 ]; then
    echo "**** Dry Run ****"
    opts="-v --dry-run --print-stats"
  else
    opts=""
  fi

  ##
  ## /oldroot/mnt/asturw
  ##
  cd_dir="/oldroot/mnt/asturw"
  if ! cd "$cd_dir"; then
    return 1
  fi
  if [ "$BACKUP_ASTURW_DEFAULTS" = "no" ]; then
    dirs=""
    files=""
  else
    dirs="etc stat/var/www/*"
    files="stat/var/www/*"
  fi

  includes=""

  unset IFS
  for dir in $dirs $BACKUP_ASTURW_INCLUDE_DIRS; do
    case "$dir" in
      /*) ;;
      mnt/kd*) ;;
      stat/var/lib/asterisk*) ;;
      stat/var/packages*) ;;
      stat/var/www/cache*) ;;
      usr/lib*) ;;
      *) if [ -d "$dir" ]; then
           includes="$includes${includes:+ }$dir"
         fi
         ;;
    esac
  done

  for file in $files $BACKUP_ASTURW_INCLUDE_FILES; do
    case "$file" in
      /*) ;;
      mnt/kd*) ;;
      stat/var/www/cache*) ;;
      *) if [ -f "$file" ]; then
           includes="$includes${includes:+ }$file"
         fi
         ;;
    esac
  done

  if [ -n "$includes" ]; then
    $TARSNAP_PROG -cf "${HOSTNAME}-${cd_dir##*/}-$(date +%Y%m%d-%H%M%S)" $opts -C $cd_dir $includes
    rtn=$?

    if [ $dry_run -eq 1 ]; then
      echo "**** Dry Run ****"
    fi
    if [ $rtn -ne 0 ]; then
      return $rtn
    fi
  fi

  ##
  ## /mnt/kd
  ##
  cd_dir="/mnt/kd"
  if ! cd "$cd_dir"; then
    return 1
  fi
  if [ "$BACKUP_KD_DEFAULTS" = "no" ]; then
    dirs=""
    files=""
  else
    dirs="rc.conf.d crontabs avahi arno-iptables-firewall/plugins monit openvpn ipsec ssl ssh ssh_keys ssh_root_keys"
    if [ "$ASTERISK_DAHDI_DISABLE" != "yes" ]; then
      dirs="$dirs asterisk dahdi fop2 phoneprov/templates"
    fi
    files="*.conf rc.elocal rc.local rc.local.stop dnsmasq.static arno-iptables-firewall/custom-rules"
  fi

  includes=""

  unset IFS
  for dir in $dirs $BACKUP_KD_INCLUDE_DIRS; do
    case "$dir" in
      /*) ;;
      *tarsnap*) ;;
      *) if [ -d "$dir" ]; then
           includes="$includes${includes:+ }$dir"
         fi
         ;;
    esac
  done

  for file in $files $BACKUP_KD_INCLUDE_FILES; do
    case "$file" in
      /*) ;;
      *tarsnap*) ;;
      *) if [ -f "$file" ]; then
           includes="$includes${includes:+ }$file"
         fi
         ;;
    esac
  done

  if [ -n "$includes" ]; then
    $TARSNAP_PROG -cf "${HOSTNAME}-${cd_dir##*/}-$(date +%Y%m%d-%H%M%S)" $opts -C $cd_dir $includes
    rtn=$?

    if [ $dry_run -eq 1 ]; then
      echo "**** Dry Run ****"
    fi
    if [ $rtn -ne 0 ]; then
      return $rtn
    fi
  fi

  return 0
}

usage()
{
  echo "
Usage: tarsnap-backup [options...]

Options:
  --keygen email       Use tarsnap-keygen to generate '$TARSNAP_KEY_FILE'
  --machine name       Optional with --keygen, defaults to '$HOSTNAME'
  --install-cronjob    Install cron entry using random minutes
  --uninstall-cronjob  Remove cron entry
  --cron               Called via cron to perform standard backup
  --dry-run            Optional with --cron, don't create an archive, simulate doing so.
  --list               Same as 'tarsnap --list-archives'
  --stats              Same as 'tarsnap --print-stats'
  --version            Same as 'tarsnap --version'
  --help               Show this help text
"
  exit 1
}

ARGS="$(getopt --name tarsnap-backup \
               --long keygen:,machine:,install-cronjob,uninstall-cronjob,cron,dry-run,list,stats,version,help \
               --options Vh \
               -- "$@")"
if [ $? -ne 0 ]; then
  usage
fi
eval set -- $ARGS

keygen=""
machine=""
install_cronjob=0
uninstall_cronjob=0
cron=0
dry_run=0
list=0
stats=0
version=0
while [ $# -gt 0 ]; do
  case "$1" in
    --keygen)  keygen="$2"; shift ;;
    --machine)  machine="$2"; shift ;;
    --install-cronjob)  install_cronjob=1 ;;
    --uninstall-cronjob)  uninstall_cronjob=1 ;;
    --cron)  cron=1 ;;
    --dry-run)  dry_run=1 ;;
    --list)  list=1 ;;
    --stats)  stats=1 ;;
    -V|--version)  version=1 ;;
    -h|--help)  usage ;;
    --)  shift; break ;;
  esac
  shift
done

if [ $version -eq 1 ]; then
  $TARSNAP_PROG --version
  exit
fi

if [ -n "$keygen" ]; then
  tarsnap_keygen "$keygen" "${machine:-$HOSTNAME}"
  exit
fi

if [ ! -f "$TARSNAP_KEY_FILE" ]; then
  echo "tarsnap-backup: Missing '$TARSNAP_KEY_FILE' keyfile.

You must register with Tarsnap [ https://www.tarsnap.com ] and define account credentials.
Using account credentials, generate a local '$TARSNAP_KEY_FILE'. **don't lose it**

Example:
tarsnap-backup --keygen me@example.com [ --machine machine-name ]
               --machine will default to '$HOSTNAME'
"
  exit 1
fi

if [ $list -eq 1 ]; then
  $TARSNAP_PROG --list-archives
  exit
fi

if [ $stats -eq 1 ]; then
  $TARSNAP_PROG --print-stats
  exit
fi

if [ $install_cronjob -eq 1 ]; then
  add_cron_entry
  exit 0
fi

if [ $uninstall_cronjob -eq 1 ]; then
  del_cron_entry
  exit 0
fi

# Don't continue without the --cron option
if [ $cron -ne 1 ]; then
  usage
fi

# Robust 'bash' method of creating/testing for a lockfile
if ! ( set -o noclobber; echo "$$" > "$LOCKFILE" ) 2>/dev/null; then
  echo "tarsnap-backup: already running, lockfile \"$LOCKFILE\" exists, process id: $(cat "$LOCKFILE")." >&2
  error_notify "Backup blocked by lockfile" "$dry_run"
  exit 9
fi

trap 'rm -f "$LOCKFILE"; error_notify "Backup interrupted" "$dry_run"; exit $?' INT TERM EXIT

do_backup "$dry_run"
rtn=$?

rm -f "$LOCKFILE"
trap - INT TERM EXIT

if [ $rtn -ne 0 ]; then
  error_notify "Backup failed" "$dry_run"
fi

exit $rtn
