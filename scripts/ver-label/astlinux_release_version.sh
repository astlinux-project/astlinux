## astlinux_release_version.sh

astlinux_release_version()
{
  ASTBASE="1.5"
  ASTREV="$(git rev-list --count --first-parent HEAD 2>/dev/null)"
  if [ -z "$ASTREV" ]; then
    ASTREV="0000"
  else
    ASTREV=$((ASTREV + 44)) # offset to match legacy SVN
  fi
  ASTGIT="$(git rev-parse --verify HEAD 2>/dev/null | cut -c 1-6)"
  if [ -z "$ASTGIT" ]; then
    ASTGIT="unknown"
  fi
  ASTREV="$ASTREV-$ASTGIT"

  if [ "$(cat "project/astlinux/target_skeleton/etc/astlinux-release")" = "git" ]; then
    ASTVER="astlinux-${ASTBASE}-${ASTREV}"
  else
    ASTVER="$(cat "project/astlinux/target_skeleton/etc/astlinux-release")"
  fi
}

