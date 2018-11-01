#!/bin/bash
#
# mime-pack
#
# Imputs via stdin with principle email headers: (no body message lines)
# Subject:
# To:
# From:
# etc.
#
# Outputs via stdout a MIME encoded email, ready for "sendmail -t"
#
# Usage: mime-pack msg_body file_name1 mime_type1 [ file_name2 mime_type2 ... ]
#

if [ -z "$1" -o -z "$2" -o -z "$3" ]; then
  echo "Usage: mime-pack msg_body file_name1 mime_type1 [ file_name2 mime_type2 ... ]" >&2
  echo "Example: mime-pack \"Message body\\nline2\" \"file.pdf\" \"application/pdf\"" >&2
  exit 1
fi

HEADER="$(cat)"

BOUNDARY="$(date "+%s" | md5sum | cut -b1-32)"

MSGBODY="$(echo "$1" | sed 's/\\n/\n/g')"

IFS=$'\n'
for line in $HEADER; do
  echo "$line"
done
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
echo ""
echo "$MSGBODY"
echo ""
echo ""

while [ -n "$2" -a -n "$3" ]; do
  if [ -f "$2" ]; then
    ATTACHMENT="$2"
    FILENAME="$(basename "$ATTACHMENT")"
    MIMETYPE="$3"

    echo "--$BOUNDARY"
    echo "Content-Type: $MIMETYPE; name=\"$FILENAME\""
    echo "Content-Transfer-Encoding: base64"
    echo "Content-Disposition: attachment; filename=\"$FILENAME\""
    echo ""

    openssl base64 -in "$ATTACHMENT"

    echo ""
  else
    echo "mime-pack: file \"$2\" not found" >&2
  fi
  shift 2
done

echo "--$BOUNDARY--"
echo "."
echo ""

exit 0

