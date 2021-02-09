#############################################################
#
# perl
#
#############################################################

PERL_VERSION_MAJOR = 24
PERL_VERSION = 5.$(PERL_VERSION_MAJOR).4
PERL_SITE = https://www.cpan.org/src/5.0
PERL_SOURCE = perl-$(PERL_VERSION).tar.xz
PERL_INSTALL_STAGING = YES
# Depend on linux to define LINUX_VERSION_PROBED
PERL_DEPENDENCIES = linux

PERL_ARCHNAME = $(ARCH)-linux

PERL_MODULES = constant version base fields
PERL_MODULES += Carp Errno Fcntl PathTools POSIX Digest Socket IO XSLoader Exporter B File-Find JSON-PP
PERL_MODULES += Digest/MD5 Digest/SHA Getopt/Long Time/Local File/Glob Sys/Hostname

PERL_CROSS_VERSION = 1.3.5
PERL_CROSS_SITE = https://github.com/arsv/perl-cross/releases/download/$(PERL_CROSS_VERSION)
PERL_CROSS_SOURCE = perl-cross-$(PERL_CROSS_VERSION).tar.gz

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
	-Dsitelib=/mnt/kd/perl \
	-Dld="$(TARGET_CC)" \
	-Dccflags="$(TARGET_CFLAGS) -DAPPLLIB_EXP=\\\"/mnt/kd/perl:/usr/local/share/perl\\\" " \
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
	(cd $(@D); $(TARGET_MAKE_ENV) HOSTCC='$(HOSTCC_NOCACHE)' \
		./configure $(PERL_CONF_OPT))
	$(SED) 's/UNKNOWN-/Buildroot $(BR2_VERSION_FULL) /' $(@D)/patchlevel.h
endef

define PERL_BUILD_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) all
endef

define PERL_INSTALL_STAGING_CMDS
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) DESTDIR="$(STAGING_DIR)" install.perl install.sym
endef

define PERL_INSTALL_TARGET_CMDS
	# Undefine utils.lst file so cpan, corelist, ... perlthanks are not installed, keep shasum
	echo "utils/shasum" > $(@D)/utils.lst
	$(TARGET_MAKE_ENV) $(MAKE1) -C $(@D) DESTDIR="$(TARGET_DIR)" install.perl install.sym
	# Remove CORE dir
	rm -rf $(TARGET_DIR)/usr/lib/perl5/$(PERL_VERSION)/$(PERL_ARCHNAME)/CORE
	# Remove all .pod files
	find $(TARGET_DIR)/usr/lib/perl5/ -name '*.pod' -print0 | xargs -0 rm -f
	# Remove many unicore files
	rm -rf $(TARGET_DIR)/usr/lib/perl5/$(PERL_VERSION)/unicore/lib/
	rm -rf $(TARGET_DIR)/usr/lib/perl5/$(PERL_VERSION)/unicore/To/
	rm -f $(TARGET_DIR)/usr/lib/perl5/$(PERL_VERSION)/unicore/Name.pl
	# Remove misc files
	find $(TARGET_DIR)/usr/lib/perl5/ -name '.packlist' -print0 | xargs -0 rm -f
endef

define PERL_CLEAN_CMDS
	-$(MAKE1) -C $(@D) clean
	rm -rf $(TARGET_DIR)/usr/lib/perl5/
	rm -f $(TARGET_DIR)/usr/bin/perl
endef

$(eval $(call GENTARGETS,package,perl))
