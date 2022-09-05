#!/bin/ash
##
## system-vendor
##
## Usage: system-vendor [ Test_Vendor_Label ]
##
## Generate an informative model name describing
## the bare metal hardware or guest VM
##
## The data file consists of an 8 character hash,
## a tilde (~), followed by a string.
##

vendor_test="$@"

DATA_FILE="/usr/share/system-vendor.ids"

CACHE_FILE="/tmp/etc/system-vendor"

eth_list()
{
  local eth IFS

  unset IFS
  for eth in $(ip -o link show 2>/dev/null | cut -d':' -f2); do
    case $eth in
      eth[0-9])
      ethtool -P $eth 2>/dev/null | sed -n -r 's/^Permanent address: *(..):(..):(..):.*$/\1\2\3/p' | tr 'a-f' 'A-F'
      ;;
    esac
  done
}

eth_signature()
{
  local sig sigs="$1" x cnt max rtn IFS

  max=0
  rtn=""

  unset IFS
  for sig in $sigs; do
    cnt=0
    for x in $sigs; do
      if [ "$x" = "$sig" ]; then
        cnt=$((cnt+1))
      fi
    done
    if [ $cnt -gt $max ]; then
      max=$cnt
      rtn="$max$sig"
    fi
  done

  echo "$rtn"
}

## Use cache file if it exists and no vendor_test
if [ -f "$CACHE_FILE" -a -z "$vendor_test" ]; then
  cat "$CACHE_FILE"
  exit 0
fi

## Test for guest VM
if [ -f /var/run/dmesg.boot ]; then
  vm_guest="$(sed -n -r 's/^.*Hypervisor detected: *([^ ].*)$/\1/p' /var/run/dmesg.boot)"
else
  vm_guest=""
fi

if [ -z "$vm_guest" ]; then
  vm_guest="$(lscpu | sed -n -r 's/^Hypervisor vendor: *([^ ].*)$/\1/p')"
fi

if [ -n "$vm_guest" ]; then
  echo "$vm_guest Hypervisor Guest VM" > "$CACHE_FILE"
  echo "$vm_guest Hypervisor Guest VM"
  exit 0
fi

## Test for bare metal
cpu_info="$(cat /proc/cpuinfo | sed -n -r 's/^model name[[:space:]]*:[[:space:]]*(.+)$/\1/p' | tr -d '[:space:]')"

if [ -z "$cpu_info" ]; then
  echo "system-vendor: No CPU model name available." >&2
  exit 1
fi

## Find most occurring ethernet MAC vendor, starting with the NIC count
eth_sig="$(eth_signature "$(eth_list)")"

if [ -z "$eth_sig" ]; then
  echo "system-vendor: No Ethernet MAC address available" >&2
  exit 1
fi

## Use printf to suppress a trailing newline before sha1sum
vendor_id="$(printf '%s%s' "$cpu_info" "$eth_sig" | sha1sum | cut -c 33-40)"

if [ -n "$vendor_test" ]; then
  echo "${vendor_id}~${vendor_test} NIC x${eth_sig%??????}"
  exit 0
fi

if [ ! -f "$DATA_FILE" ]; then
  echo "system-vendor: File '$DATA_FILE' not found." >&2
  exit 1
fi

vendor_label="$(sed -n -r "s/^${vendor_id}~(.*)$/\1/p" "$DATA_FILE")"

if [ -n "$vendor_label" ]; then
  echo "$vendor_label" > "$CACHE_FILE"
  echo "$vendor_label"
  exit 0
fi

exit 1
