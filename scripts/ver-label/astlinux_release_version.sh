## astlinux_release_version.sh

astlinux_release_version()
{
  ASTBASE="1.5"
  if svn info >/dev/null 2>&1; then
    ASTREV="$(LANG=C svn info | awk -F': ' '/^Last Changed Rev:/ { print $2 }')"
    if [ -n "$ASTREV" ]; then
      ASTGIT="$(svn propget git-commit --revprop -r "$ASTREV" 2>/dev/null | cut -c 1-6)"
      if [ -n "$ASTGIT" ]; then
        ASTREV="$ASTREV-$ASTGIT"
      fi
    fi
  else
    ASTREV="$(git rev-parse --verify HEAD 2>/dev/null | cut -c 1-6)"
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

