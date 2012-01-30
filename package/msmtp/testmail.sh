#!/bin/sh

# testmail
#
#  Usage: testmail TO_email_address [ FROM_email_address ]
#
#  Utility to test email server settings
#

. /etc/rc.conf

TO="$1"

FROM="$2"

if [ -z "$TO" ]; then
  echo "Usage: testmail TO_email_address [ FROM_email_address ]"
  exit 1
fi

if [ -z "$FROM" -a -n "$SMTP_DOMAIN" ]; then
  FROM="noreply@$SMTP_DOMAIN"
fi

(
  echo "To: ${TO}${FROM:+
From: $FROM}
Subject: Test Email from '$HOSTNAME'

Test Email from '$HOSTNAME'

[Generated at $(date "+%H:%M:%S on %B %d, %Y")]
"
  echo "Hostname:   $HOSTNAME"
  echo "System Time:   $(date)"
  echo "IPv4 Address:   $(ip -o addr show dev "$EXTIF" 2>/dev/null | \
                          awk '$3 == "inet" { split($4, field, "/"); print field[1]; }')"
  if [ -f /etc/astlinux-release ]; then
    if [ -x /usr/sbin/asterisk ]; then
      echo "AstLinux Release:   $(cat /etc/astlinux-release) - $(/usr/sbin/asterisk -V)"
    else
      echo "AstLinux Release:   $(cat /etc/astlinux-release)"
    fi
  fi
  if [ -f /oldroot/cdrom/ver ]; then
    echo "Runnix Release:   $(cat /oldroot/cdrom/ver)"
  fi

) | msmtp -t

