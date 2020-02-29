################################################################################
#
# cmake
#
################################################################################

CMAKE_VERSION_MAJOR = 3.15
CMAKE_VERSION = $(CMAKE_VERSION_MAJOR).5
CMAKE_SOURCE = cmake-$(CMAKE_VERSION).tar.gz
CMAKE_SITE = https://cmake.org/files/v$(CMAKE_VERSION_MAJOR)

# CMake is a particular package:
# * CMake can be built using the generic infrastructure or the cmake one.
#   Since Buildroot has no requirement regarding the host system cmake
#   program presence, it uses the generic infrastructure to build the
#   host-cmake package.
# * CMake bundles its dependencies within its sources. This is the
#   reason why the host-cmake package has no dependencies.
#
# Get rid of -I* options from $(HOST_CPPFLAGS) to prevent that a
# header available in $(HOST_DIR)/include is used instead of a
# CMake internal header, e.g. lzma* headers of the xz package
HOST_CMAKE_CFLAGS = $(shell echo $(HOST_CFLAGS) | sed -r "s%$(HOST_CPPFLAGS)%%")
HOST_CMAKE_CXXFLAGS = $(shell echo $(HOST_CXXFLAGS) | sed -r "s%$(HOST_CPPFLAGS)%%")

define HOST_CMAKE_CONFIGURE_CMDS
	(cd $(@D); \
		$(HOST_CONFIGURE_OPTS) \
		CFLAGS="$(HOST_CMAKE_CFLAGS)" \
		./bootstrap --prefix=$(HOST_DIR)/usr \
			--parallel=$(BR2_JLEVEL) -- \
			-DCMAKE_C_FLAGS="$(HOST_CMAKE_CFLAGS)" \
			-DCMAKE_CXX_FLAGS="$(HOST_CMAKE_CXXFLAGS)" \
			-DCMAKE_EXE_LINKER_FLAGS="$(HOST_LDFLAGS)" \
			-DCMAKE_USE_OPENSSL:BOOL=OFF \
			-DBUILD_CursesDialog=OFF \
	)
endef

define HOST_CMAKE_BUILD_CMDS
	$(HOST_MAKE_ENV) $(MAKE) -C $(@D)
endef

define HOST_CMAKE_INSTALL_CMDS
	$(HOST_MAKE_ENV) $(MAKE) -C $(@D) install/fast
endef

$(eval $(call GENTARGETS,package,cmake,host))
