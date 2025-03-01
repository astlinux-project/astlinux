#!/bin/sh

##
## Wrapper for smartctl
##

DRIVE="$(/sbin/findfs LABEL=RUNNIX)"
case $DRIVE in
  /dev/nvme[0-9]n*) DRIVE="${DRIVE%p[1-9]}" ;;
                 *) DRIVE="${DRIVE%[1-9]}" ;;
esac

if [ -z "$DRIVE" ]; then
  echo "smart-status: Drive not Found"
  exit 1
fi

clean_output()
{
  sed -n '/^=== START/,$ p' | sed -e 's/^=== START.*$//' -e '/^$/d'
}

do_smartctl() {
  local output rtn;

  output="$(smartctl "$@")"
  rtn=$?

  if [ $rtn -eq 0 ]; then
    echo "$output" | clean_output
  else
    echo "Error retrieving S.M.A.R.T information."
    echo "$output" | grep '^SMART support is:'
  fi

  return $rtn
}

case $1 in

attr*)
  do_smartctl -A -f brief "$DRIVE"
  ;;

ATTR*)
  do_smartctl -A -f old "$DRIVE"
  ;;

health)
  do_smartctl -H "$DRIVE"
  ;;

info)
  do_smartctl -i "$DRIVE"
  ;;

*)
  echo "Usage: smart-status attr[ibutes]|ATTR[IBUTES]|health|info"
  exit 1
  ;;

esac

