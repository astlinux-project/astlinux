#!/bin/bash
##
## mail
##
## Simple wrapper to emulate 'mail' or 'mailx'
##
## Only sending mail is supported
##
## Copyright (C) 2016 Lonnie Abelbeck
##
## This is free software, licensed under the GNU General Public License
## version 3 as published by the Free Software Foundation; you can
## redistribute it and/or modify it under the terms of the GNU
## General Public License; and comes with ABSOLUTELY NO WARRANTY.

VERSION="12.5 7/5/10"

usage()
{
  echo '
Usage: mail [options...] to_addr

Options:
  -a file      Attach the given file to the message. (Multiple allowed)
  --mime type  Optionally define the MIME Type of each attached file. (Multiple allowed)
  -b address   Send blind carbon copies to a comma-separated list of email addresses.
  -c address   Send carbon copies to a comma-separated list of email addresses.
  -e           Check if mail is present. (Always exit status of "1")
  -H           Print header summaries for all messages and exit. (Always no mail)
  -r address   Define the From address.
  -S var=val   Sets the internal option variable, from= and replyto= are supported.
  -s subject   Define the subject text.
  -t           The sending message is expected to contain "To:", "Cc:" or "Bcc:" fields.
  -u user      Reads the mailbox of the given user name. (Always no mail)
  -V           Print version and exit.
  -v           Verbose mode.
  --help       Show this help text
               Note: Additional mail/mailx options are silently ignored for compatibility.
'
  exit 1
}

check_mailbox()
{
  local user="${1:-$USER}"

  echo "No mail for $user"
  return 1
}

address_header()
{
  local header="$1" addresses="$2" address IFS

  IFS=','
  for address in $addresses; do
    echo "$header $address"
  done
}

set_var_header()
{
  local var="$1" header="" addresses="" address IFS

  case "$var" in
    from=*) header="From:" ; addresses="${var#from=}" ;;
    replyto=*) header="Reply-To:" ; addresses="${var#replyto=}" ;;
  esac

  IFS=','
  for address in $addresses; do
    echo "$header $address"
  done
}

mime_header()
{
  echo "MIME-Version: 1.0"
  echo "Content-Type: multipart/mixed; boundary=\"$BOUNDARY\""
  echo ""
  echo ""
  echo "This is a multi-part message in MIME format."
  echo ""
  echo "--$BOUNDARY"
  echo "Content-Type: text/plain; charset=ISO-8859-1; format=flowed"
  echo "Content-Transfer-Encoding: 7bit"
  echo "Content-Disposition: inline"
}

gen_message_header()
{
  if [ $message_recipients -eq 0 ]; then
    address_header "To:" "$to_addr"
    address_header "Cc:" "$cc_addr"
    address_header "Bcc:" "$bcc_addr"
  fi
  if [ -n "$from_addr" ]; then
    echo "From: $from_addr"
  fi
  for x in ${!SETVAR[*]}; do
    set_var_header "${SETVAR[$x]}"
  done
  echo "Subject: $subject"
  if [ -n "$BOUNDARY" ]; then
    mime_header
  fi

  if [ $message_recipients -eq 0 ]; then
    echo ""
  fi
}

mime_content_type()
{
  local filename="$1" suffix mime

  suffix="${filename##*/}"
  suffix="${suffix##*.}"
  suffix="$(echo "$suffix" | tr '[:upper:]' '[:lower:]')"

  case $suffix in
    txt|text|conf|log|cpp|c|asc) mime="text/plain" ;;
    pdf) mime="application/pdf" ;;
    sig) mime="application/pgp-signature" ;;
    ps) mime="application/postscript" ;;
    gz) mime="application/x-gzip" ;;
    tgz) mime="application/x-tgz" ;;
    tar) mime="application/x-tar" ;;
    zip) mime="application/zip" ;;
    bz2) mime="application/x-bzip" ;;
    tbz) mime="application/x-bzip-compressed-tar" ;;
    crt|der) mime="application/x-x509-ca-cert" ;;
    wav) mime="audio/x-wav" ;;
    gif) mime="image/gif" ;;
    tiff|tif) mime="image/tiff" ;;
    jpeg|jpg) mime="image/jpeg" ;;
    png) mime="image/png" ;;
    css) mime="text/css" ;;
    html|htm) mime="text/html" ;;
    js) mime="text/javascript" ;;
    xml|dtd) mime="text/xml" ;;
    mpeg|mpg) mime="video/mpeg" ;;
    *) mime="application/octet-stream" ;;
  esac

  echo "$mime"
}

mime_data()
{
  local attach_file="$1" mime_type="$2"

  echo ""
  echo ""
  echo "--$BOUNDARY"
  echo "Content-Type: $mime_type; name=\"${attach_file##*/}\""
  echo "Content-Transfer-Encoding: base64"
  echo "Content-Disposition: attachment; filename=\"${attach_file##*/}\""
  echo ""

  openssl base64 -in "$attach_file"
}

mime_footer()
{
  echo ""
  echo "--$BOUNDARY--"
  echo "."
  echo ""
}

ARGS="$(getopt --name mail \
               --long mime:,help \
               --options Aa:Bb:c:DdEeFf:Hh:IilNnq:Rr:S:s:T:tu:vV \
               -- "$@")"
if [ $? -ne 0 ]; then
  usage
fi
eval set -- $ARGS

unset FILE
unset MIME
bcc_addr=""
cc_addr=""
exists_mail=0
headers=0
from_addr=""
unset SETVAR
subject=""
message_recipients=0
mail_user=""
version=0
verbose=0
while [ $# -gt 0 ]; do
  case "$1" in
    -a)  FILE[${#FILE[*]}]="$2"; shift ;;
    --mime)  MIME[${#MIME[*]}]="$2"; shift ;;
    -b)  bcc_addr="$2"; shift ;;
    -c)  cc_addr="$2"; shift ;;
    -e)  exists_mail=1 ;;
    -f)  shift ;;
    -H)  headers=1 ;;
    -h)  shift ;;
    -q)  shift ;;
    -r)  from_addr="$2"; shift ;;
    -S)  SETVAR[${#SETVAR[*]}]="$2"; shift ;;
    -s)  subject="$2"; shift ;;
    -T)  shift ;;
    -t)  message_recipients=1 ;;
    -u)  mail_user="$2"; shift ;;
    -V)  version=1 ;;
    -v)  verbose=1 ;;
    --help)  usage ;;
    --)  shift; break ;;
  esac
  shift
done
to_addr="$1"

if [ $version -eq 1 ]; then
  echo "$VERSION"
  exit 0
fi

if [ $headers -eq 1 ]; then
  check_mailbox "$mail_user"
  exit 0
fi

if [ $exists_mail -eq 1 ]; then
  check_mailbox "$mail_user" >/dev/null
  exit $?
fi

if [ -z "$to_addr" -o "$to_addr" = "--" ] && [ $message_recipients -eq 0 ]; then
  check_mailbox "$mail_user"
  exit $?
fi

if [ ${#FILE[*]} -gt 0 ]; then
  if [ $message_recipients -eq 1 ]; then
    echo "mail: The '-t' option is not compatible with the '-a file' option." >&2
    exit 1
  fi

  for x in ${!FILE[*]}; do
    if [ ! -f "${FILE[$x]}" ]; then
      echo "mail: Attachment file not found: ${FILE[$x]}" >&2
      exit 1
    fi

    if [ -z "${MIME[$x]}" ]; then
      MIME[$x]="$(mime_content_type "${FILE[$x]}")"
    fi
  done

  BOUNDARY="$(date "+%s" | md5sum | cut -b1-32)"
else
  BOUNDARY=""
fi

# Check if this is an interactive session
if tty -s; then
  interactive=1
else
  interactive=0
fi

if [ $interactive -eq 1 -a -z "$subject" ]; then
  read -p "Subject: " subject
fi

(
  gen_message_header
  cat  # copy stdin to stdout until ^D (EOT)
  if [ -n "$BOUNDARY" ]; then
    for x in ${!FILE[*]}; do
      mime_data "${FILE[$x]}" "${MIME[$x]}"
    done
    mime_footer
  fi
) | sendmail -t

