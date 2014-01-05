#!/bin/sh
#
# AstLinux NOTIFYCMD script to generate
# status and email alert notifications with upsmon.
#

message="$1"
ups="$UPSNAME"
event="$NOTIFYTYPE"

. /etc/rc.conf

# Update status file
echo "$(date "+%Y-%m-%d %H:%M:%S") - $event: $message" >>/var/log/ups-status.log

NOTIFY="$UPS_NOTIFY"
NOTIFY_FROM="$UPS_NOTIFY_FROM"

if [ -z "$NOTIFY_FROM" -a -n "$SMTP_DOMAIN" ]; then
  NOTIFY_FROM="noreply@$SMTP_DOMAIN"
fi

for TO in $NOTIFY; do
  (
  echo "To: ${TO}${NOTIFY_FROM:+
From: $NOTIFY_FROM}
Subject: UPS on '$HOSTNAME': $message

UPS on '$HOSTNAME': $message

Event: $event

[Generated at $(date "+%H:%M:%S on %B %d, %Y")]
"
  if [ -n "$ups" ]; then
    echo "======== $ups ========"
    upsc $ups 2>&1
    echo "========"
  fi
  ) | sendmail -t
done

