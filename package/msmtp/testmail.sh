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

dev_to_ipv4_address()
{
  ip -o addr show dev "$1" 2>/dev/null | \
        awk '$3 == "inet" { split($4, field, "/"); print field[1]; }'
}

dev_to_ipv4_network()
{
  ip -o addr show dev "$1" 2>/dev/null | \
        awk '$3 == "inet" { print $4; }'
}

if [ -z "$TO" ]; then
  echo "Usage: testmail TO_email_address [ FROM_email_address ]"
  exit 1
fi

if [ ! -f /etc/msmtprc ]; then
  echo "testmail: The SMTP Mail Relay (msmtp) is not running. The SMTP Server must be defined." >&2
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
  echo "External IPv4 Address:   $(dev_to_ipv4_address $EXTIF)"
  if [ -n "$INTIF" ]; then
    echo "1st LAN IPv4 Network:   $(dev_to_ipv4_network $INTIF)"
  fi
  if [ -n "$INT2IF" ]; then
    echo "2nd LAN IPv4 Network:   $(dev_to_ipv4_network $INT2IF)"
  fi
  if [ -n "$INT3IF" ]; then
    echo "3rd LAN IPv4 Network:   $(dev_to_ipv4_network $INT3IF)"
  fi
  if [ -n "$DMZIF" ]; then
    echo "The DMZ IPv4 Network:   $(dev_to_ipv4_network $DMZIF)"
  fi
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

