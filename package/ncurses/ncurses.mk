#############################################################
#
# ncurses
#
#############################################################

NCURSES_VERSION = 6.4
NCURSES_SOURCE = ncurses-$(NCURSES_VERSION).tar.gz
NCURSES_SITE = https://invisible-mirror.net/archives/ncurses
NCURSES_INSTALL_STAGING = YES
NCURSES_DEPENDENCIES = host-ncurses
HOST_NCURSES_DEPENDENCIES =
NCURSES_LIB_SUFFIX = 
NCURSES_CONFIG_SCRIPTS = ncurses$(NCURSES_LIB_SUFFIX)5-config

NCURSES_CONF_OPT = \
	--with-abi-version=5 \
	--with-shared \
	--without-normal \
	--without-cxx \
	--without-cxx-binding \
	--without-ada \
	--without-tests \
	--disable-big-core \
	--without-profile \
	--disable-rpath \
	--disable-rpath-hack \
	--enable-echo \
	--enable-const \
	--enable-overwrite \
	--enable-pc-files \
	--disable-stripping \
	--without-progs \
	--without-manpages \
	--without-gpm \
	--disable-widec \
	--disable-ext-colors \
	--disable-root-environ \
	--disable-static

# Install after busybox for the full-blown versions
ifeq ($(BR2_PACKAGE_BUSYBOX),y)
NCURSES_DEPENDENCIES += busybox
endif

ifneq ($(BR2_ENABLE_DEBUG),y)
NCURSES_CONF_OPT += --without-debug
endif

NCURSES_TERMINFO_FILES = \
	a/ansi \
	d/dumb \
	l/linux \
	p/putty \
	p/putty-256color \
	p/putty-vt100 \
	s/screen \
	s/screen-256color \
	v/vt100 \
	v/vt100-putty \
	v/vt102 \
	v/vt200 \
	v/vt220 \
	x/xterm \
	x/xterm-256color \
	x/xterm-16color \
	x/xterm-color \
	x/xterm-xfree86

define NCURSES_STAGING_NCURSES_CONFIG_FIXUP
	$(SED) "s,^prefix=.*,prefix=\'$(STAGING_DIR)/usr\',g" \
		-e "s,^exec_prefix=.*,exec_prefix=\'$(STAGING_DIR)/usr\',g" \
		$(STAGING_DIR)/usr/bin/$(NCURSES_CONFIG_SCRIPTS)
endef
NCURSES_POST_INSTALL_STAGING_HOOKS += NCURSES_STAGING_NCURSES_CONFIG_FIXUP

NCURSES_LIBS-y = ncurses
NCURSES_LIBS-$(BR2_PACKAGE_NCURSES_TARGET_MENU) += menu
NCURSES_LIBS-$(BR2_PACKAGE_NCURSES_TARGET_PANEL) += panel
NCURSES_LIBS-$(BR2_PACKAGE_NCURSES_TARGET_FORM) += form

define NCURSES_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) DESTDIR=$(STAGING_DIR)
endef

define NCURSES_INSTALL_TARGET_LIBS
	for lib in $(NCURSES_LIBS-y:%=lib%); do \
		cp -dpf $(STAGING_DIR)/usr/lib/$${lib}$(NCURSES_LIB_SUFFIX).so* \
			$(TARGET_DIR)/usr/lib/; \
	done
endef

define NCURSES_INSTALL_TARGET_CMDS
	mkdir -p $(TARGET_DIR)/usr/lib
	$(NCURSES_INSTALL_TARGET_LIBS)
	ln -snf /usr/share/terminfo $(TARGET_DIR)/usr/lib/terminfo
	rm -rf $(TARGET_DIR)/usr/share/terminfo $(TARGET_DIR)/usr/share/tabset
	$(foreach t,$(NCURSES_TERMINFO_FILES), \
		$(INSTALL) -D -m 0644 $(STAGING_DIR)/usr/share/terminfo/$(t) \
			$(TARGET_DIR)/usr/share/terminfo/$(t)
	)
endef

HOST_NCURSES_CONF_ENV = \
	ac_cv_path_LDCONFIG=""

HOST_NCURSES_CONF_OPT = \
	--with-shared \
	--without-gpm \
	--without-manpages \
	--without-cxx \
	--without-cxx-binding \
	--without-ada \
	--with-default-terminfo-dir=/usr/share/terminfo \
	--disable-db-install \
	--without-normal

$(eval $(call AUTOTARGETS,package,ncurses))
$(eval $(call AUTOTARGETS,package,ncurses,host))
