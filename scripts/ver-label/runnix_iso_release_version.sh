## runnix_iso_release_version.sh

runnix_iso_release_version()
{
  RUNBASE="1.0"
  RUNREV="$(git rev-parse --verify HEAD 2>/dev/null | cut -c 1-6)"
  if [ -z "$RUNREV" ]; then
    RUNREV="unknown"
  fi

  if [ "$(cat "project/runnix-iso/target_skeleton/etc/runnix-release")" = "git" ]; then
    RUNVER="runnix-iso-${RUNBASE}-${RUNREV}"
  else
    RUNVER="$(cat "project/runnix-iso/target_skeleton/etc/runnix-release")"
  fi
}

