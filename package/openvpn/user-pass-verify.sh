#!/bin/sh
# arg: filename - containing user<NL>pass
#
# Return 1 when user/pass failed
# Return 0 when user/pass is matched
#

. /etc/rc.conf

user="$(sed -n '1 p' "$1")"
pass="$(sed -n '2 p' "$1")"

if [ -n "$user" -a -n "$pass" ]; then
  IFS=$'\n'
  for line in $OVPN_USER_PASS; do
    validuser="$(echo "$line" | awk -F' ' '{ print $1; }')"
    validpass="$(echo "$line" | awk -F' ' '{ print $2; }')"
    if [ "$validuser" = "$user" -a "$validpass" = "$pass" ]; then
      exit 0
    fi
  done
fi

exit 1

