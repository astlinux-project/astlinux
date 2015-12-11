# shell script to download crosstool-ng and any patches

CTNG_VERSION="1.20.0"

CTNG_URL="http://crosstool-ng.org/download/crosstool-ng/crosstool-ng-${CTNG_VERSION}.tar.bz2"

CTNG_PATCHES_DIR="$(dirname $0)/patches"

display_result()
{
  if [ $1 -eq 0 ]; then
    echo "OK"
  else
    echo "FAILED"
  fi
}

if [ -d "crosstool-ng-$CTNG_VERSION" ]; then
  echo "directory \"crosstool-ng-$CTNG_VERSION\" already exists."
  echo "crosstool-ng-$CTNG_VERSION probably is already downloaded, skipping."
else
  echo "Downloading $CTNG_URL ..."
  wget -O - "$CTNG_URL" | tar xj
  display_result $?

  # Overlay our custom patches
  if [ -d "crosstool-ng-$CTNG_VERSION" ]; then
    echo "Adding custom patches ..."
    rsync -a --exclude=".svn" "$CTNG_PATCHES_DIR/" "crosstool-ng-$CTNG_VERSION/patches/"
    display_result $?
  fi
fi

exit 0
