## runnix_release_version.sh

runnix_release_version()
{
  RUNBASE="0.5"
  if svn info >/dev/null 2>&1; then
    RUNREV="$(LANG=C svn info | awk -F': ' '/^Last Changed Rev:/ { print $2 }')"
  else
    RUNREV="$(git rev-parse --verify HEAD 2>/dev/null | cut -c 1-6)"
  fi
  if [ -z "$RUNREV" ]; then
    RUNREV="unknown"
  fi

  if [ "$(cat "project/runnix/target_skeleton/etc/runnix-release")" = "svn" ]; then
    RUNVER="runnix-${RUNBASE}-${RUNREV}"
  else
    RUNVER="$(cat "project/runnix/target_skeleton/etc/runnix-release")"
  fi
}

