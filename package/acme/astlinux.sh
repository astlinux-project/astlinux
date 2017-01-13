#!/usr/bin/env sh

# acme.sh deploy script for AstLinux
# This file name is "astlinux.sh"
# So, here must be a method astlinux_deploy()
# Which will be called by acme.sh to deploy the cert
# returns 0 means success, otherwise error.

. /etc/rc.conf

########  Public functions #####################

#domain keyfile certfile cafile fullchain
astlinux_deploy() {
  _cdomain="$1"
  _ckey="$2"
  _ccert="$3"
  _cca="$4"
  _cfullchain="$5"

  _debug _cdomain "$_cdomain"
  _debug _ckey "$_ckey"
  _debug _ccert "$_ccert"
  _debug _cca "$_cca"
  _debug _cfullchain "$_cfullchain"

  if [ -n "$HTTPSCERT" -a "$HTTPS_ACME" = "yes" ]; then
    service lighttpd stop
    cat "$_ckey" "$_ccert" > "$HTTPSCERT"
    chmod 600 "$HTTPSCERT"
    if [ -n "$HTTPSCHAIN" ]; then
      if [ -f "$_cfullchain" ]; then
        cat "$_cfullchain" > "$HTTPSCHAIN"
      else
        rm -f "$HTTPSCHAIN"
      fi
    fi
    sleep 1
    service lighttpd init
  fi

  if [ "$SIPTLSCERT_ACME" = "yes" ]; then
# TODO.  Not sure what the right files to use are. Need to test.
# See..
# https://web.johncook.uk/articles/computing/lets-encrypt-launch
# https://www.jedwarddurrett.com/20160604162607.php

    mkdir -p /mnt/kd/ssl/sip-tls/keys
    cat "$_cfullchain" > /mnt/kd/ssl/sip-tls/keys/server.crt
    cat "$_ckey" > /mnt/kd/ssl/sip-tls/keys/server.key
    chmod 600 /mnt/kd/ssl/sip-tls/keys/server.key
# How to tell Asterisk to reload certificate?
# Hopefully just a reload (of just SIP or PJSIP?) and not a restart
  fi

  return 0
}
