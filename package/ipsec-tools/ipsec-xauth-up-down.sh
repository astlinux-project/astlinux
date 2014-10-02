#!/bin/sh

# IPsec XAuth Phase1 Up / Down script

PATH="/usr/bin:/bin:/usr/sbin:/sbin"

#
#   script "/usr/sbin/ipsec-xauth-up-down" phase1_up;
#   script "/usr/sbin/ipsec-xauth-up-down" phase1_down;
#

. /etc/rc.conf

findintf()
{
  ip -o addr show to "$1" \
    | awk '{ print $2; }'
}

case $1 in

  phase1_up)

    if [ -n "$IPSECM_XAUTH_LOCAL_GW" ]; then
      gw="$IPSECM_XAUTH_LOCAL_GW"
    else
      gw="$INTIP"
    fi
    if [ -n "$gw" ]; then
      intf="$(findintf $gw)"
      if [ -n "$INTERNAL_ADDR4" -a -n "$intf" ]; then
        ip route add $INTERNAL_ADDR4 via $gw dev $intf
      fi
    fi
    ;;

  phase1_down)

    if [ -n "$INTERNAL_ADDR4" ]; then
      ip route delete $INTERNAL_ADDR4
    fi
    ;;

esac

exit 0

