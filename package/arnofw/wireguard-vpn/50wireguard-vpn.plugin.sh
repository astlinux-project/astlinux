# ------------------------------------------------------------------------------
#             -= Arno's iptables firewall - WireGuard VPN plugin =-
#
PLUGIN_NAME="WireGuard VPN plugin"
PLUGIN_VERSION="1.02"
PLUGIN_CONF_FILE="wireguard-vpn.conf"
#
# Last changed          : September 18, 2019
# Requirements          : AIF 2.0.0+
# Comments              : This plugin allows access to a WireGuard VPN.
#
# Author                : (C) Copyright 2018-2019 by Lonnie Abelbeck & Arno van Amersfoort
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
  local host port redirect_ports eif IFS

  iptables -N WIREGUARD_INPUT 2>/dev/null
  iptables -F WIREGUARD_INPUT

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

  if [ -n "$WIREGUARD_VPN_REDIRECT_PORTS" ]; then
    redirect_ports=""
    IFS=' ,'
    for port in $WIREGUARD_VPN_REDIRECT_PORTS; do
      if [ "$port" != "$WIREGUARD_VPN_PORT" ]; then
        redirect_ports="$redirect_ports${redirect_ports:+,}$port"
      fi
    done
    if [ -n "$redirect_ports" ]; then
      port="$WIREGUARD_VPN_PORT"
      echo "${INDENT}Redirecting internet IPv4 UDP ports: $redirect_ports to WireGuard VPN listen port: $port"
      IFS=' ,'
      for eif in ${NAT_IF:-$EXT_IF}; do
        ip4tables -t nat -A POST_NAT_PREROUTING_CHAIN -i $eif -p udp -m multiport --dports "$redirect_ports" -j REDIRECT --to-ports $port
      done
    fi
  fi

  echo "${INDENT}Setting up internal(WG->Local) INPUT policy"
  INDENT="${INDENT} "

  if [ -n "$WIREGUARD_VPN_HOST_OPEN_TCP" -o -n "$WIREGUARD_VPN_HOST_OPEN_UDP" ]; then
    unset IFS
    for rule in $WIREGUARD_VPN_HOST_OPEN_TCP; do
      if parse_rule "$rule" WIREGUARD_VPN_HOST_OPEN_TCP "hosts-ports"; then

        echo "${INDENT}Allowing $hosts(WG->Local) for TCP port(s): $ports"

        IFS=' ,'
        for host in $(ip_range "$hosts"); do
          for port in $ports; do
            iptables -A WIREGUARD_INPUT -s $host -p tcp --dport $port -j ACCEPT
          done
        done
      fi
    done

    unset IFS
    for rule in $WIREGUARD_VPN_HOST_OPEN_UDP; do
      if parse_rule "$rule" WIREGUARD_VPN_HOST_OPEN_UDP "hosts-ports"; then

        echo "${INDENT}Allowing $hosts(WG->Local) for UDP port(s): $ports"

        IFS=' ,'
        for host in $(ip_range "$hosts"); do
          for port in $ports; do
            iptables -A WIREGUARD_INPUT -s $host -p udp --dport $port -j ACCEPT
          done
        done
      fi
    done
  elif [ -n "$WIREGUARD_VPN_HOST_DENY_TCP" -o -n "$WIREGUARD_VPN_HOST_DENY_UDP" ]; then
    unset IFS
    for rule in $WIREGUARD_VPN_HOST_DENY_TCP; do
      if parse_rule "$rule" WIREGUARD_VPN_HOST_DENY_TCP "hosts:ANYHOST-ports:ANYPORT"; then

        echo "${INDENT}Denying $hosts(WG->Local) for TCP port(s): $ports"

        IFS=' ,'
        for host in $(ip_range "$hosts"); do
          for port in $ports; do
            iptables -A WIREGUARD_INPUT -s $host -p tcp --dport $port -j DROP
          done
        done
      fi
    done

    unset IFS
    for rule in $WIREGUARD_VPN_HOST_DENY_UDP; do
      if parse_rule "$rule" WIREGUARD_VPN_HOST_DENY_UDP "hosts:ANYHOST-ports:ANYPORT"; then

        echo "${INDENT}Denying $hosts(WG->Local) for UDP port(s): $ports"

        IFS=' ,'
        for host in $(ip_range "$hosts"); do
          for port in $ports; do
            iptables -A WIREGUARD_INPUT -s $host -p udp --dport $port -j DROP
          done
        done
      fi
    done
  fi

  echo "${INDENT}Allowing WG->Local ICMP-requests(ping)"
  iptables -A WIREGUARD_INPUT -p icmp --icmp-type echo-request -m limit --limit 20/second --limit-burst 100 -j ACCEPT
  iptables -A WIREGUARD_INPUT -p icmp --icmp-type echo-request -j DROP

  if [ -n "$WIREGUARD_VPN_HOST_OPEN_TCP" -o -n "$WIREGUARD_VPN_HOST_OPEN_UDP" ]; then
    echo "${INDENT}Denying all remaining WG->Local traffic"
    iptables -A WIREGUARD_INPUT -j DROP
  else
    echo "${INDENT}Allowing all remaining WG->Local traffic"
    ## Fall through to support "Deny LAN->Local" rules
  fi
  INDENT="${INDENT% }"

  iptables -A INT_INPUT_CHAIN -i $WIREGUARD_VPN_IF -j WIREGUARD_INPUT

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

  iptables -F WIREGUARD_INPUT
  iptables -X WIREGUARD_INPUT 2>/dev/null

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
