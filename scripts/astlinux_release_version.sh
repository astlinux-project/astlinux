## astlinux_release_version.sh

ASTBASE="1.3"

astlinux_release_version()
{
  if svn info >/dev/null 2>&1; then
    ASTREV="$(LANG=C svn info | awk -F': ' '/^Last Changed Rev:/ { print $2 }')"
  else
    ASTREV="$(git rev-parse --verify --short HEAD 2>/dev/null)"
  fi
  if [ -z "$ASTREV" ]; then
    ASTREV="unknown"
  fi

  if [ "$(cat "project/astlinux/target_skeleton/etc/astlinux-release")" = "svn" ]; then
    ASTVER="astlinux-${ASTBASE}-${ASTREV}"
  else
    ASTVER="$(cat "project/astlinux/target_skeleton/etc/astlinux-release")"
  fi
}

