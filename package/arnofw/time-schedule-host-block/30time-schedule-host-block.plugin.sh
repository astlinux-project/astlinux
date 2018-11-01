# ------------------------------------------------------------------------------
#        -= Arno's iptables firewall - Time Schedule Host Block plugin =-
#
PLUGIN_NAME="Time Schedule Host Block plugin"
PLUGIN_VERSION="1.00"
PLUGIN_CONF_FILE="time-schedule-host-block.conf"
#
# Last changed          : May 06, 2013
# Requirements          : AIF 2.0.0+
# Comments              : This plugin blocks forwarded packets based on time and day-of-week.
#
# Author                : (C) Copyright 2012-2013 by Lonnie Abelbeck & Arno van Amersfoort
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
  local rule type data addr timestart timestop weekdays i DAYS SHOWRULE LOG LOG_PREFIX IFS

  LOG="-m limit --limit 3/m --limit-burst 1 -j LOG --log-level $LOGLEVEL --log-prefix"
  LOG_PREFIX="AIF:Time Schedule Host Block:"

  IFS=$EOL
  for rule in $TIME_SCHEDULE_HOST_BLOCK_MAC; do
    unset IFS
    type="$(echo "$rule" | cut -s -d'|' -f1)"
    data="$(echo "$rule" | cut -s -d'|' -f2)"
    addr="$(echo "$data" | cut -s -d'~' -f1)"
    timestart="$(echo "$data" | cut -s -d'~' -f2)"
    timestop="$(echo "$data" | cut -s -d'~' -f3)"
    weekdays="$(echo "$data" | cut -s -d'~' -f4)"
    if [ -z "$addr" -o -z "$timestart" -o -z "$timestop" ]; then
      type=""
    else
      # Check for MAC address, if not, try from STATICHOSTS
      case "$addr" in
        *:*)
          ;;
        *)
          if [ -n "$STATICHOSTS" ]; then
            IFS=$EOL
            for i in $STATICHOSTS; do
              if [ "$(echo "$i" | cut -s -d'~' -f1)" = "$addr" ]; then
                addr="$(echo "$i" | cut -s -d'~' -f3)"
                break
              fi
            done
            unset IFS
          fi
          # Still not MAC address, ignore rule
          case "$addr" in
            *:*) ;;
            *) type="" ;;
          esac
          ;;
      esac
      SHOWRULE="${INDENT}Blocking $type Source MAC Addr: $addr using Schedule: $timestart to $timestop on Days:"
      if [ -n "$weekdays" ]; then
        DAYS="--weekdays $weekdays"
        SHOWRULE="$SHOWRULE $weekdays"
      else
        DAYS=""
        SHOWRULE="$SHOWRULE All"
      fi
      # Adjust time to UTC for iptables
      timestart="$(date -u -d "@$(date -d $timestart '+%s')" '+%H:%M:%S')"
      timestop="$(date -u -d "@$(date -d $timestop '+%s')" '+%H:%M:%S')"
    fi
    case $type in
      LAN-EXT|lan-ext)
        echo "$SHOWRULE"
        if [ "$TIME_SCHEDULE_HOST_BLOCK_LOG" = "1" ]; then
          iptables -A LAN_INET_FORWARD_CHAIN -m mac --mac-source $addr \
                   -m time --timestart $timestart --timestop $timestop $DAYS $LOG "$LOG_PREFIX"
        fi
        iptables -A LAN_INET_FORWARD_CHAIN -m mac --mac-source $addr \
                 -m time --timestart $timestart --timestop $timestop $DAYS -j REJECT
        ;;
      DMZ-EXT|dmz-ext)
        echo "$SHOWRULE"
        if [ "$TIME_SCHEDULE_HOST_BLOCK_LOG" = "1" ]; then
          iptables -A DMZ_INET_FORWARD_CHAIN -m mac --mac-source $addr \
                   -m time --timestart $timestart --timestop $timestop $DAYS $LOG "$LOG_PREFIX"
        fi
        iptables -A DMZ_INET_FORWARD_CHAIN -m mac --mac-source $addr \
                 -m time --timestart $timestart --timestop $timestop $DAYS -j REJECT
        ;;
      ANY|any)
        echo "$SHOWRULE"
        if [ "$TIME_SCHEDULE_HOST_BLOCK_LOG" = "1" ]; then
          iptables -A FORWARD_CHAIN -m mac --mac-source $addr \
                   -m time --timestart $timestart --timestop $timestop $DAYS $LOG "$LOG_PREFIX"
        fi
        iptables -A FORWARD_CHAIN -m mac --mac-source $addr \
                 -m time --timestart $timestart --timestop $timestop $DAYS -j REJECT
        ;;
      '#'*)  # Disable rule
        ;;
      *)
        echo "** WARNING: In Variable TIME_SCHEDULE_HOST_BLOCK_MAC, Rule: \"$rule\" is ignored." >&2
        ;;
    esac
  done

  IFS=$EOL
  for rule in $TIME_SCHEDULE_HOST_BLOCK; do
    unset IFS
    type="$(echo "$rule" | cut -s -d'|' -f1)"
    data="$(echo "$rule" | cut -s -d'|' -f2)"
    addr="$(echo "$data" | cut -s -d'~' -f1)"
    timestart="$(echo "$data" | cut -s -d'~' -f2)"
    timestop="$(echo "$data" | cut -s -d'~' -f3)"
    weekdays="$(echo "$data" | cut -s -d'~' -f4)"
    if [ -z "$addr" -o -z "$timestart" -o -z "$timestop" ]; then
      type=""
    else
      SHOWRULE="${INDENT}Blocking $type Source IP Addr: $addr using Schedule: $timestart to $timestop on Days:"
      if [ -n "$weekdays" ]; then
        DAYS="--weekdays $weekdays"
        SHOWRULE="$SHOWRULE $weekdays"
      else
        DAYS=""
        SHOWRULE="$SHOWRULE All"
      fi
      # Adjust time to UTC for iptables
      timestart="$(date -u -d "@$(date -d $timestart '+%s')" '+%H:%M:%S')"
      timestop="$(date -u -d "@$(date -d $timestop '+%s')" '+%H:%M:%S')"
    fi
    case $type in
      LAN-EXT|lan-ext)
        echo "$SHOWRULE"
        if [ "$TIME_SCHEDULE_HOST_BLOCK_LOG" = "1" ]; then
          iptables -A LAN_INET_FORWARD_CHAIN -s $addr \
                   -m time --timestart $timestart --timestop $timestop $DAYS $LOG "$LOG_PREFIX"
        fi
        iptables -A LAN_INET_FORWARD_CHAIN -s $addr \
                 -m time --timestart $timestart --timestop $timestop $DAYS -j REJECT
        ;;
      DMZ-EXT|dmz-ext)
        echo "$SHOWRULE"
        if [ "$TIME_SCHEDULE_HOST_BLOCK_LOG" = "1" ]; then
          iptables -A DMZ_INET_FORWARD_CHAIN -s $addr \
                   -m time --timestart $timestart --timestop $timestop $DAYS $LOG "$LOG_PREFIX"
        fi
        iptables -A DMZ_INET_FORWARD_CHAIN -s $addr \
                 -m time --timestart $timestart --timestop $timestop $DAYS -j REJECT
        ;;
      ANY|any)
        echo "$SHOWRULE"
        if [ "$TIME_SCHEDULE_HOST_BLOCK_LOG" = "1" ]; then
          iptables -A FORWARD_CHAIN -s $addr \
                   -m time --timestart $timestart --timestop $timestop $DAYS $LOG "$LOG_PREFIX"
        fi
        iptables -A FORWARD_CHAIN -s $addr \
                 -m time --timestart $timestart --timestop $timestop $DAYS -j REJECT
        ;;
      '#'*)  # Disable rule
        ;;
      *)
        echo "** WARNING: In Variable TIME_SCHEDULE_HOST_BLOCK, Rule: \"$rule\" is ignored." >&2
        ;;
    esac
  done

  if [ "$TIME_SCHEDULE_HOST_BLOCK_LOG" = "1" ]; then
    echo "${INDENT}Logging of Time Schedule Host Block packets: Enabled"
  else
    echo "${INDENT}Logging of Time Schedule Host Block packets: Disabled"
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
