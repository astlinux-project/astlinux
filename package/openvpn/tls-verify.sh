#!/bin/sh
# args: cert_depth, x509_string
#
# Return 1 to decline the client certificate
# Return 0 to accept the client certificate
#

. /etc/rc.conf

if [ "$1" -eq 0 ]; then
  # Extract the CommonName from the X509 subject string
  cn="`expr match "$2" '.*/CN=\([^/][^/]*\)'`"
  if [ -z "$cn" ]; then
    exit 1
  fi

  IFS=$'\n'                                         
  for line in $OVPN_VALIDCLIENTS; do                         
    validclient="`echo $line | cut -d~ -f1`"
    if [ "$validclient" = "$cn" ]; then
      exit 0
    fi
  done

  exit 1
fi

exit 0

