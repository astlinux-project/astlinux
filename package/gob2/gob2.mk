#############################################################
#
# gob2
#
#############################################################
GOB2_VERSION = 2.0.15
GOB2_SOURCE = gob2-$(GOB2_VERSION).tar.gz
GOB2_SITE = http://ftp.5z.com/pub/gob/

HOST_GOB2_DEPENDENCIES = host-bison host-flex host-libglib2

$(eval $(call AUTOTARGETS,package,gob2,host))

# gob2 for the host
GOB2_HOST_BINARY:=$(HOST_DIR)/usr/bin/gob2
