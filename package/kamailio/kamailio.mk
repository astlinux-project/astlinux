#############################################################
#
# kamailio
#
##############################################################

KAMAILIO_VERSION = 4.1.9
KAMAILIO_SOURCE = kamailio-$(KAMAILIO_VERSION)_src.tar.gz
KAMAILIO_SITE = http://kamailio.org/pub/kamailio/$(KAMAILIO_VERSION)/src
KAMAILIO_DEPENDENCIES = openssl

KAMAILIO_GROUP_MODULES = standard
KAMAILIO_INCLUDE_MODULES = acc dispatcher htable outbound permissions registrar tls uac usrloc
KAMAILIO_EXCLUDE_MODULES = rtpproxy-ng xhttp xhttp_rpc

ifeq ($(strip $(BR2_PACKAGE_SQLITE)),y)
KAMAILIO_DEPENDENCIES += sqlite
KAMAILIO_INCLUDE_MODULES += db_sqlite
KAMAILIO_INCLUDE_MODULES += alias_db auth_db domain speeddial
endif

ifeq ($(strip $(BR2_PACKAGE_LIBXML2)),y)
KAMAILIO_DEPENDENCIES += libxml2
KAMAILIO_INCLUDE_MODULES += presence presence_xml xmlrpc
endif

KAMAILIO_ENV_ARGS = \
	LOCALBASE="$(STAGING_DIR)/usr" \
	SYSBASE="$(STAGING_DIR)/usr" \
	CROSS_COMPILE=true \
	TLS_HOOKS=1 \
	CFLAGS='$(TARGET_CFLAGS)' \
	LDFLAGS='$(TARGET_LDFLAGS)'

KAMAILIO_MAKEFLAGS = \
	prefix="" \
	bin_dir=usr/sbin \
	cfg_dir=etc/kamailio/ \
	data_dir=usr/share/kamailio \
	lib_dir=usr/lib/kamailio \
	cfg_target=/etc/kamailio/ \
	group_include="$(KAMAILIO_GROUP_MODULES)" \
	include_modules="$(KAMAILIO_INCLUDE_MODULES)" \
	exclude_modules="$(KAMAILIO_EXCLUDE_MODULES)" \
	modules_dirs="modules" \
	LIBDIR=usr/lib \
	ARCH="$(KERNEL_ARCH)" \
	OS="linux"

define KAMAILIO_BUILD_CMDS
	$(TARGET_CONFIGURE_OPTS) \
	$(KAMAILIO_ENV_ARGS) \
	$(MAKE) -C $(@D) \
		$(KAMAILIO_MAKEFLAGS) \
		FLAVOUR="kamailio" \
		cfg

	$(TARGET_CONFIGURE_OPTS) \
	$(KAMAILIO_ENV_ARGS) \
	$(MAKE) -C $(@D) \
		$(KAMAILIO_MAKEFLAGS) \
		all
endef

define KAMAILIO_INSTALL_TARGET_CMDS
	$(TARGET_CONFIGURE_OPTS) \
	$(KAMAILIO_ENV_ARGS) \
	$(MAKE) -C $(@D) \
		$(KAMAILIO_MAKEFLAGS) \
		DESTDIR="$(TARGET_DIR)" \
		install

	$(INSTALL) -D -m 0644 $(TARGET_DIR)/etc/kamailio/kamctlrc $(TARGET_DIR)/stat/etc/kamailio/kamctlrc
	rm -rf $(TARGET_DIR)/etc/kamailio
	rsync -a --exclude=".svn" package/kamailio/etc/ $(TARGET_DIR)/stat/etc/kamailio/
	$(SED) 's:^[# ]*DBENGINE=.*$$:DBENGINE=SQLITE:' \
	    -e 's:^[# ]*DB_PATH=.*$$:DB_PATH="/mnt/kd/kamailio/data/kamailio.sqlite3":' \
	    -e 's:^[# ]*PID_FILE=.*$$:PID_FILE="/var/run/kamailio/kamailio.pid":' \
	    -e 's:^[# ]*STARTOPTIONS=.*$$:STARTOPTIONS="-u kamailio -g kamailio":' \
		$(TARGET_DIR)/stat/etc/kamailio/kamctlrc
	$(INSTALL) -D -m 0755 package/kamailio/kamailio.init $(TARGET_DIR)/etc/init.d/kamailio
	ln -s /tmp/etc/kamailio $(TARGET_DIR)/etc/kamailio
	ln -sf ../../init.d/kamailio $(TARGET_DIR)/etc/runlevels/default/S58kamailio
	ln -sf ../../init.d/kamailio $(TARGET_DIR)/etc/runlevels/default/K02kamailio
endef

define KAMAILIO_UNINSTALL_TARGET_CMDS
	rm -f $(TARGET_DIR)/usr/sbin/kamailio
	rm -rf $(TARGET_DIR)/usr/lib/kamailio
	rm -rf $(TARGET_DIR)/usr/share/kamailio
	rm -rf $(TARGET_DIR)/stat/etc/kamailio
	rm -f $(TARGET_DIR)/etc/init.d/kamailio
	rm -f $(TARGET_DIR)/etc/kamailio
	rm -f $(TARGET_DIR)/etc/runlevels/default/S58kamailio
	rm -f $(TARGET_DIR)/etc/runlevels/default/K02kamailio
endef

$(eval $(call GENTARGETS,package,kamailio))
