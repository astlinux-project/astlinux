## runnix_release_version.sh

runnix_release_version()
{
  RUNBASE="0.6"
  RUNREV="$(git rev-parse --verify HEAD 2>/dev/null | cut -c 1-6)"
  if [ -z "$RUNREV" ]; then
    RUNREV="unknown"
  fi

  if [ "$(cat "project/runnix/target_skeleton/etc/runnix-release")" = "git" ]; then
    RUNVER="runnix-${RUNBASE}-${RUNREV}"
  else
    RUNVER="$(cat "project/runnix/target_skeleton/etc/runnix-release")"
  fi
}

