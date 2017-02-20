#!/bin/sh
##
## grep
##
## Simple wrapper to perform a "safe" grep for rbash
##
## Only handle stdin, disable file processing
##
## Copyright (C) 2017 Lonnie Abelbeck
##
## This is free software, licensed under the GNU General Public License
## version 3 as published by the Free Software Foundation; you can
## redistribute it and/or modify it under the terms of the GNU
## General Public License; and comes with ABSOLUTELY NO WARRANTY.

PATH="/usr/bin:/bin"

usage()
{
  echo '
Usage: grep [-qviwFE] PATTERN/-e PATTERN/

Search for PATTERN in stdin

Options:
  -q      Quiet. Return 0 if PATTERN is found, 1 otherwise
  -v      Select non-matching lines
  -i      Ignore case
  -w      Match whole words only
  -F      PATTERN is a literal (not regexp)
  -E      PATTERN is an extended regexp
  -e PTRN Pattern to match
  --help  Show this help text
'
  exit 1
}

ARGS="$(getopt --name grep \
               --long help \
               --options qviwFEe: \
               -- "$@")"
if [ $? -ne 0 ]; then
  usage
fi
eval set -- $ARGS

OPTS=""
PTRN=""
ptrn_cnt=0
while [ $# -gt 0 ]; do
  case "$1" in
    -q|-v|-i|-w|-F|-E)  OPTS="$OPTS${OPTS:+ }$1" ;;
    -e)  PTRN="$2" ; ptrn_cnt=$((ptrn_cnt+1)) ; shift ;;
    --help)  usage ;;
    --)  shift; break ;;
  esac
  shift
done

if [ $ptrn_cnt -gt 1 ]; then
  echo "grep: Only one PATTERN match is allowed." >&2
  usage
fi

if [ -z "$PTRN" ]; then
  if [ $# -eq 1 -a -n "$1" ]; then
    PTRN="$1"
  elif [ $# -gt 0 ]; then
    echo "grep: restricted: File input is not allowed." >&2
    usage
  else
    echo "grep: No PATTERN specified." >&2
    usage
  fi
elif [ $# -gt 0 ]; then
  echo "grep: restricted: File input is not allowed." >&2
  usage
fi

/bin/grep $OPTS -e "$PTRN"
