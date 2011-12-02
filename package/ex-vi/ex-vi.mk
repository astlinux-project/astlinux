#############################################################
#
# ex-vi
#
############################################################

EX_VI_VERSION = 050325
EX_VI_SOURCE = ex-$(EX_VI_VERSION).tar.bz2
EX_VI_SITE = http://$(BR2_SOURCEFORGE_MIRROR).dl.sourceforge.net/ex-vi

EX_VI_DEPENDENCIES = ncurses

ifeq ($(BR2_PACKAGE_BUSYBOX),y)
EX_VI_DEPENDENCIES += busybox
endif

define EX_VI_BUILD_CMDS
        $(MAKE) CC="$(TARGET_CC)" \
		TERMLIB="ncurses" \
		CHARSET="-DISO8859_1" \
		FEATURES="-DCHDIR -DFASTTAG -DUCVISUAL -DBIT8 -DTIOCGWINSZ" \
		-C $(@D)
endef

define EX_VI_CONFIGURE_CMDS
        @echo "No configure"
endef

define EX_VI_INSTALL_TARGET_CMDS
	$(INSTALL) -m 0755 -D $(@D)/ex $(TARGET_DIR)/usr/bin/vi
	# In case Busybox made a /bin/vi link, remove it
	rm -f $(TARGET_DIR)/bin/vi
endef

define EX_VI_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/bin/vi
endef

$(eval $(call GENTARGETS,package,ex-vi))
