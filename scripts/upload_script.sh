#!/bin/bash

for i in $(ls dl | grep -v .sha1); do
  if [ ! -f dl/"$i".sha1 -a -f dl/"$i" ]; then
    sha1sum dl/"$i" > dl/"$i".sha1
  fi
done

if [ -z "$1" ]; then
  scripts/ncftpput -z -r1000 -u astlinuxfiles files.astlinux.org / dl/*
fi
