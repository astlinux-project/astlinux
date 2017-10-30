#!/usr/bin/env sh

# acme.sh generic custom deploy script for AstLinux
#
# The executable script "/mnt/kd/acme-deploy-custom.script"
# will be called using the same arguments as the deploy function.
#
# returns 0 means success, otherwise error.

########  Public functions #####################

#domain keyfile certfile cafile fullchain
custom_deploy() {
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

  _custom_scriptfile="/mnt/kd/acme-deploy-custom.script"

  if [ ! -x "$_custom_scriptfile" ]; then
    _err "Action script not found or executable: $_custom_scriptfile"
    return 1
  fi

  $_custom_scriptfile "$_cdomain" "$_ckey" "$_ccert" "$_cca" "$_cfullchain"
  _custom_rtn=$?

  if [ $_custom_rtn -ne 0 ]; then
    _err "Error code $_custom_rtn returned from $_custom_scriptfile"
  fi
  return $_custom_rtn
}
