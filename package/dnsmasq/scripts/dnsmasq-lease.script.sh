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
  local entry x

  if [ ! -f "$DB" ]; then
    return
  fi

  entry="$(grep -m1 "${match}" "$DB")"
  if [ -n "$entry" ]; then
    # Optimize using POSIX builtins instead of 'echo | cut'
    # Requires minimum number of ~'s (7) to work properly
    x="$entry"
    x="${x#*~}" # -f1
    x="${x#*~}" # -f2
    old_ipv6_mac="${x%%~*}";  x="${x#*~}" # -f3
    x="${x#*~}" # -f4
    old_time_secs="${x%%~*}"; x="${x#*~}" # -f5
    x="${x#*~}" # -f6
    old_vendor="${x%%~*}";    x="${x#*~}" # -f7
    old_hostname="$x"                     # -f8-

    sed -i "/${match}/ d" "$DB"
  fi
}

del_db()
{
  if [ ! -f "$DB" ]; then
    : > "$DB"
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

save_db_file()
{
  # Optimize "old" actions that don't change the byte count
  # When dnsmasq starts, this saves a lot of writes to persistent storage
  if [ "$action" = "old" -a -f "/mnt/kd/${DB##*/}" ]; then
    if [ "$(stat -c '%s' "$DB")" = "$(stat -c '%s' "/mnt/kd/${DB##*/}")" ]; then
      return
    fi
  fi

  if [ -f /mnt/kd/dnsmasq.leases ]; then
    cp "$DB" "/mnt/kd/${DB##*/}"
  fi
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
  save_db_file
elif [ "$action" = "old" ]; then
  get_old_del_db
  add_db
  save_db_file
elif [ "$action" = "del" ]; then
  del_db
  save_db_file
fi

exit 0
