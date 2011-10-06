#
# AstLinux include script to use standard
# email alert settings with apcupsd.
#

. /etc/rc.conf

NOTIFY="$UPS_NOTIFY"
NOTIFY_FROM="$UPS_NOTIFY_FROM"

if [ -z "$NOTIFY_FROM" -a -n "$SMTP_DOMAIN" ]; then
  NOTIFY_FROM="noreply@$SMTP_DOMAIN"
fi

for TO in $NOTIFY; do
  (
  echo "To: ${TO}${NOTIFY_FROM:+
From: $NOTIFY_FROM}
Subject: UPS on '$HOSTNAME': $APCUPSD_MSG

UPS on '$HOSTNAME': $APCUPSD_MSG

[Generated at $(date "+%H:%M:%S on %B %d, %Y")]
"
  /usr/sbin/apcaccess status
  ) | sendmail -t
done

