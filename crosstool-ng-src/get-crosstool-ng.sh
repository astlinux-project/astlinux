# shell script to download crosstool-ng

CTNG_VERSION="1.24.0"
CTNG_SHA1="db5cefc51d7870b26287fe76f058a0af2e60e657"
CTNG_URL="http://crosstool-ng.org/download/crosstool-ng"
CTNG_FILE="crosstool-ng-${CTNG_VERSION}.tar.bz2"

WGET_ARGS="--timeout=30 -c -t 2"

error_result()
{
  if [ $1 -eq 0 ]; then
    echo "$2 OK"
  else
    if [ -n "$3" ]; then
      rm -f "$3"
    fi
    echo "$2 FAILED"
    exit 1
  fi
}

if [ -d "crosstool-ng-$CTNG_VERSION" ]; then
  echo "directory \"crosstool-ng-$CTNG_VERSION\" already exists."
  echo "crosstool-ng-$CTNG_VERSION probably is already downloaded, skipping."
else
  echo "Downloading $CTNG_URL/$CTNG_FILE ..."
  wget $WGET_ARGS "$CTNG_URL/$CTNG_FILE"
  error_result $? Download "$CTNG_FILE"

  shasum="$(sha1sum "$CTNG_FILE" | cut -d' ' -f1)"
  if [ "$shasum" != "$CTNG_SHA1" ]; then
    error_result 1 SHASUM "$CTNG_FILE"
  else
    error_result 0 SHASUM
  fi

  tar xjf "$CTNG_FILE"
  error_result $? Extract "$CTNG_FILE"
  rm -f "$CTNG_FILE"
fi

exit 0
