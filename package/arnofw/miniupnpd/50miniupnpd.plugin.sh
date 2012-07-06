# ------------------------------------------------------------------------------
#            -= Arno's iptables firewall - MiniUPnPd plugin =-
#
PLUGIN_NAME="MiniUPnPd plugin"
PLUGIN_VERSION="1.0"
PLUGIN_CONF_FILE="miniupnpd.conf"
#
# Last changed          : July 6, 2012
# Requirements          : AIF 2.0.0+ with miniupnpd daemon
# Comments              : Setup of the iptables chains that the miniupnpd daemon manages
#
# Author                : (C) Copyright 2012 by Lonnie Abelbeck & Arno van Amersfoort
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
  ip4tables -t nat -N MINIUPNPD 2>/dev/null
  ip4tables -t nat -F MINIUPNPD

  ip4tables -N MINIUPNPD 2>/dev/null
  ip4tables -F MINIUPNPD

  # Connect both MINIUPNPD chains
  plugin_restart

  return 0
}


# Plugin restart function
plugin_restart()
{
  local eif IFS

  # Skip plugin_stop on a restart
  # Reconnect both MINIUPNPD chains, flushed on a restart

  IFS=' ,'
  for eif in $EXT_IF; do
    ip4tables -t nat -A POST_NAT_PREROUTING_CHAIN -i $eif -j MINIUPNPD

    ip4tables -A POST_FORWARD_CHAIN -i $eif ! -o $eif -j MINIUPNPD
  done

  return 0
}


# Plugin stop function
plugin_stop()
{
  local eif IFS

  IFS=' ,'
  for eif in $EXT_IF; do
    ip4tables -t nat -D POST_NAT_PREROUTING_CHAIN -i $eif -j MINIUPNPD

    ip4tables -D POST_FORWARD_CHAIN -i $eif ! -o $eif -j MINIUPNPD
  done

  ip4tables -t nat -F MINIUPNPD
  ip4tables -t nat -X MINIUPNPD 2>/dev/null

  ip4tables -F MINIUPNPD
  ip4tables -X MINIUPNPD 2>/dev/null

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

# Check if the config file exists
if [ ! -e "$CONF_FILE" ]; then
  printf "NOTE: Config file \"$CONF_FILE\" not found!\n        Plugin \"$PLUGIN_NAME v$PLUGIN_VERSION\" ignored!\n" >&2
  PLUGIN_RET_VAL=0
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
    if plugin_sanity_check; then
      case $PLUGIN_CMD in
        start|'') plugin_start; PLUGIN_RET_VAL=$?;;
        restart ) plugin_restart; PLUGIN_RET_VAL=$?;;
        stop|stop-restart) plugin_stop; PLUGIN_RET_VAL=$?;;
        status  ) plugin_status; PLUGIN_RET_VAL=$?;;
        *       ) PLUGIN_RET_VAL=1; printf "\033[40m\033[1;31m  ERROR: Invalid plugin option \"$PLUGIN_CMD\"!\033[0m\n" >&2;;
      esac
    fi
  else
    PLUGIN_RET_VAL=0
  fi
fi

