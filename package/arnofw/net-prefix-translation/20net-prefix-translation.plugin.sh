# ------------------------------------------------------------------------------
#      -= Arno's iptables firewall - Network Prefix Translation plugin =-
#
PLUGIN_NAME="Network Prefix Translation plugin"
PLUGIN_VERSION="1.00"
PLUGIN_CONF_FILE="net-prefix-translation.conf"
#
# Last changed          : June 7, 2017
# Requirements          : AIF 2.0.1g+, ip6tables NETMAP support
# Comments              : NPTv6 (Network Prefix Translation) for IPv6
#                         Perform a 1:1 mapping of ULA <-> GUA prefixes
#                         via the external interface.
#
# Author                : (C) Copyright 2017 by Lonnie Abelbeck & Arno van Amersfoort
# Homepage              : http://rocky.eld.leidenuniv.nl/
# Email                 : a r n o v a AT r o c k y DOT e l d DOT l e i d e n u n i v DOT n l
#                         (note: you must remove all spaces and substitute the @ and the .
#                         at the proper locations!)
# ------------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# version 2 as published by the Free Software Foundation.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
# ------------------------------------------------------------------------------

NET_PREFIX_TRANSLATION_GLOBAL_IPV6="/var/tmp/aif-net-prefix-translation-global-ipv6"

net_prefix_translation_global_ipv6()
{
  local lan IFS

  IFS=' ,'
  for lan in $NET_PREFIX_TRANSLATION_IF; do
    ip -6 -o addr show dev $lan scope global 2>/dev/null \
      | awk '$3 == "inet6" { print $4; }' \
      | grep -i -v '^fd'
  done
}

net_prefix_translation_global_prefix()
{
  local global_prefix prefix len cut_chars prefix_label prefix_len ipv6 ipv6_ex IFS

  global_prefix=""
  if [ -n "$NET_PREFIX_TRANSLATION_IF" ]; then
    prefix_len="$(echo "$NET_PREFIX_TRANSLATION_GLOBAL_PREFIX" | cut -s -d'/' -f2)"
    case $prefix_len in
      64) cut_chars="1-19"
          prefix_label="::/64"
          ;;
      60) cut_chars="1-18"
          prefix_label="0::/60"
          ;;
      56) cut_chars="1-17"
          prefix_label="00::/56"
          ;;
      52) cut_chars="1-16"
          prefix_label="000::/52"
          ;;
      48) cut_chars="1-15"
          prefix_label="0000::/48"
          ;;
       *) cut_chars=""
          prefix_label=""
          ;;
    esac

    if [ -n "$cut_chars" -a -n "$prefix_label" ]; then
      unset IFS
      for prefix in $(net_prefix_translation_global_ipv6); do
        len="$(echo "$prefix" | sed -n -r -e 's/^[0-9a-fA-F:]+\/([0-9]+)$/\1/p')"
        if [ -n "$len" ]; then
          if [ $len -ge 32 -a $len -le 64 ]; then
            ipv6="$(echo "$prefix" | cut -d'/' -f1)"
            ipv6_ex="$(netcalc "$ipv6" | sed -n -r -e 's/^Expanded IPv6 *: *([0-9a-fA-F:]+).*$/\1/p')"
            global_prefix="$(echo "$ipv6_ex" | cut -c $cut_chars)"
            if [ -n "$global_prefix" ]; then
              global_prefix="$global_prefix$prefix_label"
              break
            fi
          fi
        fi
      done
    fi
  else
    global_prefix="$NET_PREFIX_TRANSLATION_GLOBAL_PREFIX"
  fi

  echo "$global_prefix"
}

# Plugin start function
plugin_start()
{
  local global_prefix local_prefix eif IFS

  ip6tables -t nat -N NET_PREFIX_TRANSLATION_IN 2>/dev/null
  ip6tables -t nat -F NET_PREFIX_TRANSLATION_IN

  ip6tables -t nat -N NET_PREFIX_TRANSLATION_OUT 2>/dev/null
  ip6tables -t nat -F NET_PREFIX_TRANSLATION_OUT

  global_prefix="$(net_prefix_translation_global_prefix)"

  if [ ! -f "$NET_PREFIX_TRANSLATION_GLOBAL_IPV6" ]; then
    : > "$NET_PREFIX_TRANSLATION_GLOBAL_IPV6"
  fi

  if [ -z "$global_prefix" ]; then
    echo "${INDENT}Network Prefix Translation Global Prefix: Not Found"

    : > "$NET_PREFIX_TRANSLATION_GLOBAL_IPV6"
    return 1
  fi

  local_prefix="$NET_PREFIX_TRANSLATION_LOCAL_PREFIX"

  echo "${INDENT}Network Prefix Translation Global Prefix: $global_prefix"
  echo "${INDENT}Network Prefix Translation Local Prefix: $local_prefix"

  IFS=' ,'
  for eif in $EXT_IF; do
    ip6tables -t nat -A NET_PREFIX_TRANSLATION_IN -i $eif -d $global_prefix -j NETMAP --to $local_prefix
    ip6tables -t nat -A NET_PREFIX_TRANSLATION_OUT -o $eif -s $local_prefix -j NETMAP --to $global_prefix
  done

  echo "$global_prefix" > "$NET_PREFIX_TRANSLATION_GLOBAL_IPV6"

  ip6tables -t nat -A PREROUTING -j NET_PREFIX_TRANSLATION_IN
  ip6tables -t nat -A POSTROUTING -j NET_PREFIX_TRANSLATION_OUT

  return 0
}


# Plugin restart function
plugin_restart()
{

  # Skip plugin_stop on a restart
  plugin_start

  return 0
}


# Plugin stop function
plugin_stop()
{

  ip6tables -t nat -D PREROUTING -j NET_PREFIX_TRANSLATION_IN
  ip6tables -t nat -D POSTROUTING -j NET_PREFIX_TRANSLATION_OUT

  ip6tables -t nat -F NET_PREFIX_TRANSLATION_IN
  ip6tables -t nat -X NET_PREFIX_TRANSLATION_IN 2>/dev/null

  ip6tables -t nat -F NET_PREFIX_TRANSLATION_OUT
  ip6tables -t nat -X NET_PREFIX_TRANSLATION_OUT 2>/dev/null

  rm -f "$NET_PREFIX_TRANSLATION_GLOBAL_IPV6"

  return 0
}


# Plugin status function
plugin_status()
{
  local old_prefix global_prefix local_prefix eif IFS

  if [ -f "$NET_PREFIX_TRANSLATION_GLOBAL_IPV6" ]; then
    old_prefix="$(cat "$NET_PREFIX_TRANSLATION_GLOBAL_IPV6")"
  else
    old_prefix=""
  fi

  global_prefix="$(net_prefix_translation_global_prefix)"

  if [ -z "$global_prefix" ]; then
    echo "  Network Prefix Translation Global Prefix: Not Found"

    if [ -n "$old_prefix" ]; then
      if [ "$NET_PREFIX_TRANSLATION_UPDATE_ON_STATUS" != "0" ]; then
        # update rules
        ip6tables -t nat -F NET_PREFIX_TRANSLATION_IN
        ip6tables -t nat -F NET_PREFIX_TRANSLATION_OUT

        : > "$NET_PREFIX_TRANSLATION_GLOBAL_IPV6"
      fi
    fi
    return 0
  fi

  if [ "$old_prefix" = "$global_prefix" ]; then
    echo "  Network Prefix Translation Global Prefix did not change: $global_prefix"
    return 0
  fi

  local_prefix="$NET_PREFIX_TRANSLATION_LOCAL_PREFIX"

  if [ "$NET_PREFIX_TRANSLATION_UPDATE_ON_STATUS" != "0" ]; then
    # update rules

    ip6tables -t nat -F NET_PREFIX_TRANSLATION_IN
    ip6tables -t nat -F NET_PREFIX_TRANSLATION_OUT

    IFS=' ,'
    for eif in $EXT_IF; do
      ip6tables -t nat -A NET_PREFIX_TRANSLATION_IN -i $eif -d $global_prefix -j NETMAP --to $local_prefix
      ip6tables -t nat -A NET_PREFIX_TRANSLATION_OUT -o $eif -s $local_prefix -j NETMAP --to $global_prefix
    done

    echo "$global_prefix" > "$NET_PREFIX_TRANSLATION_GLOBAL_IPV6"

    echo "  Network Prefix Translation Global Prefix (updated): $global_prefix"
  else
    echo "  Network Prefix Translation Global Prefix needs updating to: $global_prefix"
  fi

  return 0
}


# Check sanity of eg. environment
plugin_sanity_check()
{
  # Sanity check

  if [ -z "$(echo "$NET_PREFIX_TRANSLATION_GLOBAL_PREFIX" | cut -s -d'/' -f2)" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: NET_PREFIX_TRANSLATION_GLOBAL_PREFIX is missing a /nn prefix!\033[0m\n" >&2
    return 1
  fi

  if [ -z "$(echo "$NET_PREFIX_TRANSLATION_LOCAL_PREFIX" | cut -s -d'/' -f2)" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: NET_PREFIX_TRANSLATION_LOCAL_PREFIX is missing a /nn prefix!\033[0m\n" >&2
    return 1
  fi

  if [ -n "$NET_PREFIX_TRANSLATION_IF" ] && ! check_command netcalc; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: Required binary \"netcalc\" is not available!\033[0m\n" >&2
    return 1
  fi

  return 0
}


############
# Mainline #
############

# Check where to find the config file
CONF_FILE=""
if [ -n "$PLUGIN_CONF_PATH" ]; then
  CONF_FILE="$PLUGIN_CONF_PATH/$PLUGIN_CONF_FILE"
fi

# Preinit to success:
PLUGIN_RET_VAL=0

# Check if the config file exists
if [ ! -e "$CONF_FILE" ]; then
  printf "NOTE: Config file \"$CONF_FILE\" not found!\n        Plugin \"$PLUGIN_NAME v$PLUGIN_VERSION\" ignored!\n" >&2
else
  # Source the plugin config file
  . "$CONF_FILE"

  if [ "$ENABLED" = "1" -a "$PLUGIN_CMD" != "stop-restart" ] ||
     [ "$ENABLED" = "0" -a "$PLUGIN_CMD" = "stop-restart" ] ||
     [ -n "$PLUGIN_LOAD_FILE" -a "$PLUGIN_CMD" = "stop" ] ||
     [ -n "$PLUGIN_LOAD_FILE" -a "$PLUGIN_CMD" = "status" ]; then
    # Show who we are:
    echo "${INDENT}$PLUGIN_NAME v$PLUGIN_VERSION"

    # Increment indention
    INDENT="$INDENT "

    # Only proceed if environment ok
    if ! plugin_sanity_check; then
      PLUGIN_RET_VAL=1
    else
      case $PLUGIN_CMD in
        start|'') plugin_start; PLUGIN_RET_VAL=$? ;;
        restart ) plugin_restart; PLUGIN_RET_VAL=$? ;;
        stop|stop-restart) plugin_stop; PLUGIN_RET_VAL=$? ;;
        status  ) plugin_status; PLUGIN_RET_VAL=$? ;;
        *       ) PLUGIN_RET_VAL=1; printf "\033[40m\033[1;31m  ERROR: Invalid plugin option \"$PLUGIN_CMD\"!\033[0m\n" >&2 ;;
      esac
    fi
  fi
fi
