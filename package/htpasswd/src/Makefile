# Makefile for htpasswd

# CONFIGURE: Some systems don't need -lcrypt, and indeed they get an
# error if you try to link with it.  If you get an error about libcrypt
# not found, try commenting out this definition.
CRYPT_LIB =    -lcrypt

CC =		gcc
CDEFS =		${SSL_DEFS} ${SSL_INC}
CFLAGS =	-O ${CDEFS}
#CFLAGS =	-g ${CDEFS}
LDFLAGS =	-s
#LDFLAGS =	-g

all:		htpasswd

htpasswd:	htpasswd.o
	${CC} ${CFLAGS} ${LDFLAGS} htpasswd.o ${CRYPT_LIB} -o htpasswd

htpasswd.o:	htpasswd.c
	${CC} ${CFLAGS} -c htpasswd.c

clean:
	rm -f htpasswd *.o

