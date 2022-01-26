#!/bin/bash

action="$1"

if [ -z "$action" ]; then
  echo "Usage: upload_script.sh all|dry-run"
  exit 1
fi

if [ ! -d dl/ ]; then
  echo "Directory \"dl/\" not found, exiting."
  exit 1
fi

for i in $(ls -1 dl | grep -v .sha1); do
  if [ -f "dl/${i}" ] && [ ! -f "dl/${i}.sha1" ]; then
    echo "Adding SHA1: dl/${i}.sha1"
    sha1sum "dl/${i}" > "dl/${i}.sha1"
  fi
done

if [ "$action" = "all" ]; then
  s3cmd sync --acl-public --exclude '*/*' -v dl/ s3://files-astlinux-project/
elif [ "$action" = "dry-run" ]; then
  s3cmd sync --dry-run --exclude '*/*' -v dl/ s3://files-astlinux-project/
fi
