# ------------------------------------------------------------------------------
#            -= Arno's iptables firewall - Adaptive Ban plugin =-
#
PLUGIN_NAME="Adaptive Ban plugin"
PLUGIN_VERSION="1.08"
PLUGIN_CONF_FILE="adaptive-ban.conf"
#
# Last changed          : December 22, 2016
# Requirements          : AIF 2.0.0+
# Comments              : Parse a log file for failed access with offending IP addresses
#                         Ban the IP address after multiple failed attempts
#
# Author                : (C) Copyright 2010-2016 by Lonnie Abelbeck
# Homepage              : http://www.astlinux-project.org/
# Credits               : Fail2ban Project
# Homepage              : http://www.fail2ban.org/
# Credits               : Arno van Amersfoort
# Homepage              : http://rocky.eld.leidenuniv.nl/
# Freshmeat homepage    : http://freshmeat.net/projects/iptables-firewall/?topic_id=151
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

# Plugin start function
plugin_start()
{
  local host net IFS

  iptables -N ADAPTIVE_BAN_DROP_CHAIN 2>/dev/null
  iptables -F ADAPTIVE_BAN_DROP_CHAIN
  iptables -A ADAPTIVE_BAN_DROP_CHAIN -m limit --limit 1/hour --limit-burst 1 -j LOG --log-level $LOGLEVEL --log-prefix "AIF:Adaptive-Ban host: "
  if [ "$ADAPTIVE_BAN_REJECT" = "1" ]; then
    ip4tables -A ADAPTIVE_BAN_DROP_CHAIN -j REJECT --reject-with icmp-host-unreachable
    if [ "$IPV6_SUPPORT" = "1" ]; then
      ip6tables -A ADAPTIVE_BAN_DROP_CHAIN -j REJECT --reject-with icmp6-addr-unreachable
    fi
  else
    iptables -A ADAPTIVE_BAN_DROP_CHAIN -j DROP
  fi

  iptables -N ADAPTIVE_BAN_CHAIN 2>/dev/null
  iptables -F ADAPTIVE_BAN_CHAIN
  if [ "$ADAPTIVE_BAN_WHITELIST_INTERNAL" != "0" ]; then
    printf "${INDENT}Adaptive Ban - Whitelisting INTERNAL net(s): "
    IFS=' ,'
    for net in $INTERNAL_NET; do
      printf "$net "
      iptables -A ADAPTIVE_BAN_CHAIN -s $net -j RETURN
    done
    echo ""
  fi
  if [ -n "$ADAPTIVE_BAN_WHITELIST" ]; then
    printf "${INDENT}Adaptive Ban - Whitelisting host(s): "
    IFS=' ,'
    for host in $ADAPTIVE_BAN_WHITELIST; do
      printf "$host "
      iptables -A ADAPTIVE_BAN_CHAIN -s $host -j RETURN
    done
    echo ""
  fi

  # Insert rule in the INPUT chain
  iptables -I INPUT -j ADAPTIVE_BAN_CHAIN

  # Insert rule in the FORWARD chain
  iptables -I FORWARD -j ADAPTIVE_BAN_CHAIN

  echo "${INDENT}File=$ADAPTIVE_BAN_FILE Time=$ADAPTIVE_BAN_TIME Count=$ADAPTIVE_BAN_COUNT Types=$ADAPTIVE_BAN_TYPES"

  start-stop-daemon -S -x "$PLUGIN_BIN_PATH/adaptive-ban-helper" -b -- start "$IP4TABLES" "$IP6TABLES" "$IPV6_SUPPORT" \
      "$ADAPTIVE_BAN_FILE" "$ADAPTIVE_BAN_TIME" "$ADAPTIVE_BAN_COUNT" $ADAPTIVE_BAN_TYPES

  return 0
}


# Plugin stop function
plugin_stop()
{

  printf "${INDENT}Adaptive Ban - Stopping... "

  # Stop helper script on next iteration
  "$PLUGIN_BIN_PATH/adaptive-ban-helper" stop "$IP4TABLES" "$IP6TABLES" "$IPV6_SUPPORT"

  echo "Stopped."

  iptables -D INPUT -j ADAPTIVE_BAN_CHAIN
  iptables -D FORWARD -j ADAPTIVE_BAN_CHAIN

  iptables -F ADAPTIVE_BAN_CHAIN
  iptables -X ADAPTIVE_BAN_CHAIN 2>/dev/null
  
  iptables -F ADAPTIVE_BAN_DROP_CHAIN
  iptables -X ADAPTIVE_BAN_DROP_CHAIN 2>/dev/null

  return 0
}


# Plugin status function
plugin_status()
{

  "$PLUGIN_BIN_PATH/adaptive-ban-helper" status "$IP4TABLES" "$IP6TABLES" "$IPV6_SUPPORT"

  return 0
}


# Check sanity of eg. environment
plugin_sanity_check()
{

  if [ ! -x "$PLUGIN_BIN_PATH/adaptive-ban-helper" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: The adaptive-ban-helper script can not be found or is not executable!\033[0m\n" >&2
    return 1
  fi

  if [ -z "$ADAPTIVE_BAN_FILE" -o -z "$ADAPTIVE_BAN_TIME" -o -z "$ADAPTIVE_BAN_COUNT" -o -z "$ADAPTIVE_BAN_TYPES" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: The plugin config file is not properly set!\033[0m\n" >&2
    return 1
  fi

  if [ "$PLUGIN_CMD" = "start" ] && [ ! -f "$ADAPTIVE_BAN_FILE" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: Input log file $ADAPTIVE_BAN_FILE does not exist!\033[0m\n" >&2
    return 1
  fi
  
  if ! check_command sort; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: Required command sort is not available!\033[0m\n" >&2
    return 1
  fi

  if ! check_command uniq; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: Required command uniq is not available!\033[0m\n" >&2
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

  if [ "$ENABLED" = "1" ] ||
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
        stop    ) plugin_stop; PLUGIN_RET_VAL=$? ;;
        status  ) plugin_status; PLUGIN_RET_VAL=$? ;;
        *       ) PLUGIN_RET_VAL=1; printf "\033[40m\033[1;31m${INDENT}ERROR: Invalid plugin option \"$PLUGIN_CMD\"!\033[0m\n" >&2 ;;
      esac
    fi
  fi
fi
