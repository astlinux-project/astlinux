# ------------------------------------------------------------------------------
#           -= Arno's iptables firewall - DynDNS IPv6 Open plugin =-
#
PLUGIN_NAME="DynDNS IPv6 Open plugin"
PLUGIN_VERSION="1.00"
PLUGIN_CONF_FILE="dyndns-ipv6-open.conf"
#
# Last changed          : February 28, 2017
# Requirements          : kernel 2.6 + AIF 2.0.1 or better
# Comments              : This implements support to open ports for DynDNS IPv6 hosts
#
# Author                : (C) Copyright 2008-2017 by Arno van Amersfoort & Lonnie Abelbeck
# Credits               : David Kerr
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

DYNDNS_OPEN_RULES="/var/tmp/aif-dyndns-ipv6-open-rules"

dyndns_open_generate_rules()
{
  local rules_file="$1" IFS

  rm -f "$rules_file"

  # Add TCP ports to allow for certain hosts
  ##########################################
  unset IFS
  for rule in $DYNDNS_IPV6_OPEN_TCP; do
    if parse_rule "$rule" DYNDNS_IPV6_OPEN_TCP "interfaces-destips-hosts-ports"; then

      echo "${INDENT}$(show_if_ip "$interfaces" "$destips")Allowing(IPv6) $hosts for TCP port(s): $ports"

      IFS=','
      for host in $hosts; do
        for port in $ports; do
          for destip in $destips; do
            for interface in $interfaces; do
              echo "-A DYNDNS_CHAIN -i $interface -s $host -d $destip -p tcp --dport $port -j ACCEPT" >> "$rules_file"
            done
          done
        done
      done
    fi
  done


  # Add UDP ports to allow for certain hosts
  ##########################################
  unset IFS
  for rule in $DYNDNS_IPV6_OPEN_UDP; do
    if parse_rule "$rule" DYNDNS_IPV6_OPEN_UDP "interfaces-destips-hosts-ports"; then

      echo "${INDENT}$(show_if_ip "$interfaces" "$destips")Allowing(IPv6) $hosts for UDP port(s): $ports"

      IFS=','
      for host in $hosts; do
        for port in $ports; do
          for destip in $destips; do
            for interface in $interfaces; do
              echo "-A DYNDNS_CHAIN -i $interface -s $host -d $destip -p udp --dport $port -j ACCEPT" >> "$rules_file"
            done
          done
        done
      done
    fi
  done


  # Add IP protocols to allow for certain hosts
  #############################################
  unset IFS
  for rule in $DYNDNS_IPV6_OPEN_IP; do
    if parse_rule "$rule" DYNDNS_IPV6_OPEN_IP "interfaces-destips-hosts-protos"; then

      echo "${INDENT}$(show_if_ip "$interfaces" "$destips")Allowing(IPv6) $hosts for IP protocol(s): $protos"

      IFS=','
      for host in $hosts; do
        for proto in $protos; do
          for destip in $destips; do
            for interface in $interfaces; do
              echo "-A DYNDNS_CHAIN -i $interface -s $host -d $destip -p $proto -j ACCEPT" >> "$rules_file"
            done
          done
        done
      done
    fi
  done

  if [ ! -f "$rules_file" ]; then
    return 1
  fi

  # Only allow root to edit rules file
  chmod 600 "$rules_file"

  return 0
}

# Plugin start function
plugin_start()
{
  # Create new DYNDNS_CHAIN chain:
  ip6tables -N DYNDNS_CHAIN 2>/dev/null
  ip6tables -F DYNDNS_CHAIN

  # Insert rule into the main chain:
  ip6tables -A EXT_INPUT_CHAIN -j DYNDNS_CHAIN

  # Create the rules file
  dyndns_open_generate_rules "$DYNDNS_OPEN_RULES"

  # Start helper script
  start-stop-daemon -S -x "$PLUGIN_BIN_PATH/dyndns-ipv6-open-helper" -b -- start "$IP6TABLES" \
      "$DYNDNS_IPV6_UPDATE_TIME" "$DYNDNS_OPEN_RULES"

  return 0
}


# Plugin restart function
plugin_restart()
{
  # Stop helper script on next iteration
  "$PLUGIN_BIN_PATH/dyndns-ipv6-open-helper" stop "$IP6TABLES"

  # Insert rule into the main chain:
  ip6tables -A EXT_INPUT_CHAIN -j DYNDNS_CHAIN

  # Re-create the rules file
  if dyndns_open_generate_rules "$DYNDNS_OPEN_RULES.tmp"; then
    mv "$DYNDNS_OPEN_RULES.tmp" "$DYNDNS_OPEN_RULES"
  else
    rm -f "$DYNDNS_OPEN_RULES"
  fi

  # Start helper script
  start-stop-daemon -S -x "$PLUGIN_BIN_PATH/dyndns-ipv6-open-helper" -b -- start "$IP6TABLES" \
      "$DYNDNS_IPV6_UPDATE_TIME" "$DYNDNS_OPEN_RULES"

  return 0
}


# Plugin stop function
plugin_stop()
{

  printf "${INDENT}DynDNS IPv6 Open - Stopping... "

  # Stop helper script on next iteration
  "$PLUGIN_BIN_PATH/dyndns-ipv6-open-helper" stop "$IP6TABLES"

  echo "Stopped."

  # Remove the rules file
  rm -f "$DYNDNS_OPEN_RULES"

  ip6tables -D EXT_INPUT_CHAIN -j DYNDNS_CHAIN 2>/dev/null

  ip6tables -F DYNDNS_CHAIN
  ip6tables -X DYNDNS_CHAIN 2>/dev/null

  return 0
}


# Plugin status function
plugin_status()
{

  "$PLUGIN_BIN_PATH/dyndns-ipv6-open-helper" status "$IP6TABLES"

  return 0
}


# Check sanity of eg. environment
plugin_sanity_check()
{
  if [ "$IPV6_SUPPORT" != "1" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: The plugin requires IPv6 to be enabled!\033[0m\n" >&2
    return 1
  fi

  if [ ! -x "$PLUGIN_BIN_PATH/dyndns-ipv6-open-helper" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: The dyndns-ipv6-open-helper script can not be found or is not executable!\033[0m\n" >&2
    return 1
  fi

  if [ -z "$DYNDNS_IPV6_UPDATE_TIME" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: The plugin config file is not (properly) setup!\033[0m\n" >&2
    return 1
  fi

  if [ -z "$DYNDNS_IPV6_OPEN_TCP" -a -z "$DYNDNS_IPV6_OPEN_UDP" -a -z "$DYNDNS_IPV6_OPEN_IP" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: The plugin config file is not (properly) setup!\033[0m\n" >&2
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
        *       ) PLUGIN_RET_VAL=1; printf "\033[40m\033[1;31m${INDENT}ERROR: Invalid plugin option \"$PLUGIN_CMD\"!\033[0m\n" >&2 ;;
      esac
    fi
  fi
fi
