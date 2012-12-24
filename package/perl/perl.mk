#############################################################
#
# perl
#
#############################################################

PERL_VERSION_MAJOR = 16
PERL_VERSION = 5.$(PERL_VERSION_MAJOR).2
PERL_SITE = http://www.cpan.org/src/5.0
PERL_SOURCE = perl-$(PERL_VERSION).tar.bz2
PERL_INSTALL_STAGING = YES
# Depend on linux to define LINUX_VERSION_PROBED
PERL_DEPENDENCIES = linux

PERL_MODULES = constant Carp Errno Fcntl POSIX Digest Socket IO XSLoader Digest/MD5 Digest/SHA Getopt/Std Time/Local File/Glob

PERL_CROSS_VERSION = 0.7.1
PERL_CROSS_BASE_VERSION = 5.$(PERL_VERSION_MAJOR).0
PERL_CROSS_SITE    = http://download.berlios.de/perlcross
PERL_CROSS_SOURCE  = perl-$(PERL_CROSS_BASE_VERSION)-cross-$(PERL_CROSS_VERSION).tar.gz
PERL_CROSS_OLD_POD = perl$(subst .,,$(PERL_CROSS_BASE_VERSION))delta.pod
PERL_CROSS_NEW_POD = perl$(subst .,,$(PERL_VERSION))delta.pod

# We use the perlcross hack to cross-compile perl. It should
# be extracted over the perl sources, so we don't define that
# as a separate package. Instead, it is downloaded and extracted
# together with perl

define PERL_CROSS_DOWNLOAD
	$(call DOWNLOAD,$(PERL_CROSS_SITE),$(PERL_CROSS_SOURCE))
endef
PERL_POST_DOWNLOAD_HOOKS += PERL_CROSS_DOWNLOAD

define PERL_CROSS_EXTRACT
	$(INFLATE$(suffix $(PERL_CROSS_SOURCE))) $(DL_DIR)/$(PERL_CROSS_SOURCE) | \
	$(TAR) $(TAR_STRIP_COMPONENTS)=1 -C $(@D) $(TAR_OPTIONS) -
endef
PERL_POST_EXTRACT_HOOKS += PERL_CROSS_EXTRACT

define PERL_CROSS_SET_POD
	$(SED) s/$(PERL_CROSS_OLD_POD)/$(PERL_CROSS_NEW_POD)/g $(@D)/Makefile
endef
PERL_POST_PATCH_HOOKS += PERL_CROSS_SET_POD

ifeq ($(BR2_PACKAGE_BERKELEYDB),y)
    PERL_DEPENDENCIES += berkeleydb
endif

# We have to override LD, because an external multilib toolchain ld is not
# wrapped to provide the required sysroot options.  We also can't use ccache
# because the configure script doesn't support it.
PERL_CONF_OPT = \
	--target=$(GNU_TARGET_NAME) \
	--target-tools-prefix=$(TARGET_CROSS) \
	--prefix=/usr \
	-Accflags='-DAPPLLIB_EXP=\"/mnt/kd/perl:/usr/local/share/perl\"' \
	-Dsitelib=/mnt/kd/perl \
	-Dld="$(TARGET_CC_NOCCACHE)" \
	-Dccflags="$(TARGET_CFLAGS)" \
	-Dldflags="$(TARGET_LDFLAGS) -lm" \
	-Dmydomain="" \
	-Dmyhostname="$(BR2_TARGET_GENERIC_HOSTNAME)" \
	-Dmyuname="Buildroot $(BR2_VERSION_FULL)" \
	-Dosname=linux \
	-Dosvers=$(LINUX_VERSION_PROBED) \
	-Dperladmin=root

ifeq ($(shell expr $(PERL_VERSION_MAJOR) % 2), 1)
    PERL_CONF_OPT += -Dusedevel
endif

ifneq ($(BR2_LARGEFILE),y)
    PERL_CONF_OPT += -Uuselargefiles
endif

ifneq ($(PERL_MODULES),)
PERL_CONF_OPT += --only-mod=$(subst $(space),$(comma),$(PERL_MODULES))
endif

define PERL_CONFIGURE_CMDS
	(cd $(@D); HOSTCC='$(HOSTCC_NOCACHE)' ./configure $(PERL_CONF_OPT))
	$(SED) 's/UNKNOWN-/Buildroot $(BR2_VERSION_FULL) /' $(@D)/patchlevel.h
endef

# perlcross's miniperl_top forgets base, which is required by mktables.
# Instead of patching, it's easier to just set PERL5LIB
define PERL_BUILD_CMDS
	PERL5LIB=$(@D)/dist/base/lib $(MAKE1) -C $(@D) perl modules
endef

define PERL_INSTALL_STAGING_CMDS
	PERL5LIB=$(@D)/dist/base/lib $(MAKE1) -C $(@D) DESTDIR="$(STAGING_DIR)" install.perl
endef

PERL_INSTALL_TARGET_GOALS = install.perl
ifeq ($(BR2_HAVE_DOCUMENTATION),y)
PERL_INSTALL_TARGET_GOALS += install.man
endif


define PERL_INSTALL_TARGET_CMDS
	PERL5LIB=$(@D)/dist/base/lib $(MAKE1) -C $(@D) DESTDIR="$(TARGET_DIR)" $(PERL_INSTALL_TARGET_GOALS)
	# Remove all .pod files
	find $(TARGET_DIR)/usr/lib/perl/ -name "*.pod" | xargs rm -f
	#
	ln -sf perl$(PERL_VERSION) $(TARGET_DIR)/usr/bin/perl
endef

define PERL_CLEAN_CMDS
	-$(MAKE1) -C $(@D) clean
	rm -rf $(TARGET_DIR)/usr/lib/perl/
	rm -f $(TARGET_DIR)/usr/bin/perl $(TARGET_DIR)/usr/bin/perl$(PERL_VERSION)
endef

$(eval $(call GENTARGETS,package,perl))
