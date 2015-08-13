#!/bin/bash

## Generate version "1" PHP timezonedb.h file
## Credit: Derick Rethans
## https://github.com/derickr/timelib/tree/master/zones
##
## Adapted for AstLinux by Lonnie Abelbeck

SCRIPTS="./scripts/php-timezonedb"

if [ -n "$1" ]; then
  TZDATA="$1"
else
  TZDATA="output/target/usr/share/zoneinfo"
fi

if [ ! -x /usr/bin/php ]; then
  echo "Missing native PHP. PHP must be installed at: /usr/bin/php"
  exit 1
fi

if [ ! -f $TZDATA/.tzversion ]; then
  echo "File not found: $TZDATA/.tzversion"
  exit 1
fi
version="$($SCRIPTS/get-version "$(cat $TZDATA/.tzversion)")"

rm -f timezonedb.idx timezonedb.idx.php timezonedb.dta
touch timezonedb.idx timezonedb.dta

echo "Building index:"
echo "<?php return array(" >> timezonedb.idx.php
for i in $(find $TZDATA -type f | sort); do
  l=$(stat -c "%s" timezonedb.dta)
  j=${i#$TZDATA/}
    
  case $j in
    .*|*.tab)
      ;;
    *)
      $SCRIPTS/create-entry $j $TZDATA >> timezonedb.dta
      $SCRIPTS/create-entry2 $j $l  >> timezonedb.idx
      $SCRIPTS/create-entry3 $j $l >> timezonedb.idx.php
      echo "- $j"
      ;;
  esac
done
echo "); ?>" >> timezonedb.idx.php
echo ""

echo "Sorting index:"
$SCRIPTS/sort-index > timezonedb.idx.tmp
mv timezonedb.idx.tmp timezonedb.idx
echo ""

echo "Creating .h file:"
echo -n "const timelib_tzdb_index_entry timezonedb_idx_builtin[" > timezonedb.h
echo -n $(cat timezonedb.idx | wc -l ) >> timezonedb.h
echo "] = {" >> timezonedb.h
cat timezonedb.idx >> timezonedb.h
echo "};" >> timezonedb.h

$SCRIPTS/create_dot_h_file >> timezonedb.h
echo "" >> timezonedb.h
echo -n "const timelib_tzdb timezonedb_builtin = { \"$version\", " >> timezonedb.h
echo -n $(cat timezonedb.idx | wc -l ) >> timezonedb.h 
echo ", timezonedb_idx_builtin, timelib_timezone_db_data_builtin };" >> timezonedb.h
echo ""

echo "Packaging:"
mkdir "timezonedb-$version"
mv timezonedb.h "timezonedb-$version"
tar czvf "timezonedb-$version.tar.gz" "timezonedb-$version"
echo ""

echo "Cleanup:"
rm -rf "timezonedb-$version"
rm -f timezonedb.idx timezonedb.idx.php timezonedb.dta
echo ""

echo "Install using...

# mv timezonedb-$version.tar.gz dl/

# ./scripts/upload-dl-pair dl/timezonedb-$version.tar.gz
"

