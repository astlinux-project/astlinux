# ------------------------------------------------------------------------------
#             -= Arno's iptables firewall - WireGuard VPN plugin =-
#
PLUGIN_NAME="WireGuard VPN plugin"
PLUGIN_VERSION="1.01"
PLUGIN_CONF_FILE="wireguard-vpn.conf"
#
# Last changed          : November 28, 2018
# Requirements          : AIF 2.0.0+
# Comments              : This plugin allows access to a WireGuard VPN.
#
# Author                : (C) Copyright 2018 by Lonnie Abelbeck & Arno van Amersfoort
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

# Plugin start function
plugin_start()
{
  local host port IFS

  if [ -z "$WIREGUARD_VPN_TUNNEL_HOSTS" ]; then
    WIREGUARD_VPN_TUNNEL_HOSTS="0/0"
  fi

  echo "${INDENT}Allowing internet hosts $WIREGUARD_VPN_TUNNEL_HOSTS to access the WireGuard VPN service"
  port="$WIREGUARD_VPN_PORT"
  IFS=' ,'
  for host in $(ip_range "$WIREGUARD_VPN_TUNNEL_HOSTS"); do
    iptables -A EXT_INPUT_CHAIN -p udp --dport $port -s $host -j ACCEPT
  done

  if [ "$WIREGUARD_VPN_PEER_ISOLATION" = "yes" -a -n "$WIREGUARD_VPN_IF" ]; then
    echo "${INDENT}Denying WireGuard VPN Peer->Peer traffic"
    iptables -A FORWARD_CHAIN -i $WIREGUARD_VPN_IF -o $WIREGUARD_VPN_IF -j DROP
  else
    echo "${INDENT}Allowing WireGuard VPN Peer->Peer traffic"
  fi

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

  return 0
}


# Plugin status function
plugin_status()
{
  return 0
}


# Check sanity of eg. environment
plugin_sanity_check()
{
  # Sanity check
  if [ -z "$WIREGUARD_VPN_PORT" ]; then
    printf "\033[40m\033[1;31m${INDENT}ERROR: The plugin config file is not properly set!\033[0m\n" >&2
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
