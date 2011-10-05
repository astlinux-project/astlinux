#!/bin/bash

MAILQ="/var/spool/mail"

ARGS="$@"

# non-root users cannot queue, send immediate
if [ $EUID -ne 0 ]; then
  msmtp $ARGS
  exit $?
fi

# Set secure permissions on created files
umask 077

if [ ! -d "$MAILQ" ]; then
  echo "Mail queue directory \"$MAILQ\" not found" >&2
  # EX_OSERR
  exit 71
fi

# Create new unique filenames of the form
# MAILFILE:  ccyy-mm-dd-hh.mm.ss-x.mail
# MSMTPFILE: ccyy-mm-dd-hh.mm.ss-x.msmtp
# where x is a consecutive number appended for sub-second uniqueness
BASE="$(date +%Y-%m-%d-%H.%M.%S)"
count=0

while true; do

  # Robust 'bash' method of creating/testing for a mail-file
  if ( set -o noclobber; echo "EOF" > "$MAILQ/$BASE-$count.mail" ) 2>/dev/null; then
    if [ -f "$MAILQ/$BASE-$count.msmtp" ]; then  # Unlikely, but we have to check
      rm -f "$MAILQ/$BASE-$count.mail"
    else
      # Created unique mail-file
      break
    fi
  fi

  count=$((count+1))
  # Sanity check, disk full, no write permissions, etc.
  if [ $count -gt 999 ]; then
    echo "Cannot create mail file: $MAILQ/$BASE-$count.mail" >&2
    # EX_CANTCREAT
    exit 73
  fi
done

MAILFILE="$MAILQ/$BASE-$count.mail"
MSMTPFILE="$MAILQ/$BASE-$count.msmtp"

trap 'rm -f "$MAILFILE"; exit 74' INT TERM EXIT

# Write the mail
cat > "$MAILFILE"
IOERR=$?

trap - INT TERM EXIT

if [ $IOERR -ne 0 ]; then
  rm -f "$MAILFILE"
  # EX_IOERR
  exit 74
fi

# Write command line
echo "$ARGS" > "$MSMTPFILE"
IOERR=$?

if [ $IOERR -ne 0 ]; then
  rm -f "$MAILFILE" "$MSMTPFILE"
  # EX_IOERR
  exit 74
fi

# Flush the queue
msmtpqueue -f >/dev/null 2>&1 &

# EX_OK
exit 0

