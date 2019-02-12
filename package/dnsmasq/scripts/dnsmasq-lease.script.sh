#!/bin/ash

action="$1"
mac_duid="$2"
address="$3"
dns_name="$4"

old_ipv6_mac=""
old_time_secs=""
old_vendor=""
old_hostname=""

mac2vendor()
{
  local raw_mac="$1" MAC_VENDOR_DB MAC

  MAC_VENDOR_DB="/usr/share/oui-db"

  if [ -z "$raw_mac" ] || [ ! -d "$MAC_VENDOR_DB" ]; then
    return
  fi

  MAC="$(echo "$raw_mac" | tr -d ':' | tr 'a-f' 'A-F')"
  MAC="${MAC%??????}"

  if [ ${#MAC} -eq 6 ]; then
    grep -m1 "^${MAC}~" "$MAC_VENDOR_DB/xxxxx${MAC#?????}" | cut -d'~' -f2
  fi
}

get_old_del_db()
{
  local entry

  if [ ! -f "$DB" ]; then
    return
  fi

  entry="$(grep -m1 "${match}" "$DB")"
  if [ -n "$entry" ]; then
    if [ $ip6 -eq 1 ]; then
      old_ipv6_mac="$(echo "$entry" | cut -d'~' -f3)"
    fi
    old_time_secs="$(echo "$entry" | cut -d'~' -f5)"
    old_vendor="$(echo "$entry" | cut -d'~' -f7)"
    old_hostname="$(echo "$entry" | cut -d'~' -f8-)"

    sed -i "/${match}/ d" "$DB"
  fi
}

del_db()
{
  if [ ! -f "$DB" ]; then
    return
  fi

  if grep -q "${match}" "$DB"; then
    sed -i "/${match}/ d" "$DB"
  fi
}

add_db()
{
  local mac mac_vendor time_secs vendor hostname

  if [ $ip6 -eq 1 ]; then
    mac="${DNSMASQ_MAC:-$old_ipv6_mac}"
    mac_vendor="$mac"
  else
    mac=""
    mac_vendor="$mac_duid"
  fi

  time_secs="${old_time_secs:-$(date +%s)}"
  vendor="${old_vendor:-$(mac2vendor $mac_vendor)}"
  hostname="${DNSMASQ_SUPPLIED_HOSTNAME:-$old_hostname}"

  echo "${mac_duid}~${address}~${mac}~${DNSMASQ_INTERFACE}~${time_secs}~${dns_name}~${vendor}~${hostname}" >> "$DB"
}

## main

case "$mac_duid" in
  ??:??:??:??:??:??)
    DB="/var/db/dnsmasq-lease.db"
    match="^${mac_duid}~"
    ip6=0
    ;;
  ??:??:??:??:??:??:??*)
    DB="/var/db/dnsmasq-lease6.db"
    match="^${mac_duid}~${address}~"
    ip6=1
    ;;
  *)
    exit 0
    ;;
esac

if [ "$action" = "add" ]; then
  del_db
  add_db
elif [ "$action" = "old" ]; then
  get_old_del_db
  add_db
elif [ "$action" = "del" ]; then
  del_db
fi

exit 0
