# ------------------------------------------------------------------------------
#            -= Arno's iptables firewall - SIP User-Agent plugin =-
#
PLUGIN_NAME="SIP User-Agent plugin"
PLUGIN_VERSION="1.00"
PLUGIN_CONF_FILE="sip-user-agent.conf"
#
# Last changed          : September 14, 2014
# Requirements          : kernel 2.6 + AIF 2.0.1 or better
# Comments              : This filters SIP packets via inspection of the User-Agent field.
#
# Author                : (C) Copyright 2008-2014 by Arno van Amersfoort & Lonnie Abelbeck
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
  local user_agent user_agents mode match ACTION port ports_udp ports_tcp IFS

  # Create new chains:
  iptables -N SIP_USER_AGENT 2>/dev/null
  iptables -F SIP_USER_AGENT

  iptables -N SIP_USER_AGENT_DROP 2>/dev/null
  iptables -F SIP_USER_AGENT_DROP

  if [ -n "$SIP_USER_AGENT_PASS_TYPES" ]; then
    user_agents="$SIP_USER_AGENT_PASS_TYPES"
    mode="whitelist"
    ACTION="RETURN"
  else
    user_agents="${SIP_USER_AGENT_DROP_TYPES:-friendly-scanner sipcli VaxSIPUserAgent}"
    mode="blacklist"
    ACTION="SIP_USER_AGENT_DROP"
  fi

  ports_udp="$SIP_USER_AGENT_PORTS_UDP"
  ports_tcp="$SIP_USER_AGENT_PORTS_TCP"

  if [ -z "$ports_udp" -a -z "$ports_tcp" ]; then
    ports_udp="5060"
  fi

  echo "${INDENT}SIP User-Agent(s): $user_agents ($mode)"
  if [ -n "$ports_udp" ]; then
    echo "${INDENT}SIP User-Agent for EXT->Local UDP Port(s): $ports_udp"
  fi
  if [ -n "$ports_tcp" ]; then
    echo "${INDENT}SIP User-Agent for EXT->Local TCP Port(s): $ports_tcp"
  fi

  if [ "$SIP_USER_AGENT_LOG" = "1" ]; then
    echo "${INDENT}Logging of SIP User-Agent Dropped packets: Enabled"
    iptables -A SIP_USER_AGENT_DROP -m limit --limit 1/m --limit-burst 1 -j LOG \
             --log-level $LOGLEVEL --log-prefix "AIF:SIP User-Agent Dropped: "
  else
    echo "${INDENT}Logging of SIP User-Agent Dropped packets: Disabled"
  fi
  iptables -A SIP_USER_AGENT_DROP -j DROP

  unset IFS
  for user_agent in $user_agents; do
    match="$(echo "$user_agent" | tr '~' ' ')"
    iptables -A SIP_USER_AGENT -m string --string "User-Agent: $match" --algo bm --icase -j $ACTION
  done

  if [ "$mode" = "whitelist" ]; then
    iptables -A SIP_USER_AGENT -j SIP_USER_AGENT_DROP
  fi

  # Insert rules into the main chain:
  IFS=' ,'
  for port in $ports_udp; do
    iptables -A EXT_INPUT_CHAIN -p udp --dport $port -j SIP_USER_AGENT
  done
  for port in $ports_tcp; do
    iptables -A EXT_INPUT_CHAIN -p tcp --dport $port -j SIP_USER_AGENT
  done

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

  iptables -F SIP_USER_AGENT_DROP
  iptables -X SIP_USER_AGENT_DROP 2>/dev/null

  iptables -F SIP_USER_AGENT
  iptables -X SIP_USER_AGENT 2>/dev/null

  return 0
}


# Plugin status function
plugin_status()
{

  #iptables -xnvL SIP_USER_AGENT
  return 0
}


# Check sanity of eg. environment
plugin_sanity_check()
{
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
