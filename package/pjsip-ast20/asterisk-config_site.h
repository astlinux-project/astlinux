/*
 * Asterisk config_site.h
 */

#include <sys/select.h>

/*
 * Defining PJMEDIA_HAS_SRTP to 0 does NOT disable Asterisk's ability to use srtp.
 * It only disables the pjmedia srtp transport which Asterisk doesn't use.
 * The reason for the disable is that while Asterisk works fine with older libsrtp
 * versions, newer versions of pjproject won't compile with them.
 */
#define PJMEDIA_HAS_SRTP 0

#define PJ_HAS_IPV6 1
#define NDEBUG 1
#define PJ_MAX_HOSTNAME (256)
#define PJSIP_MAX_URL_SIZE (512)
#ifdef PJ_HAS_LINUX_EPOLL
#define PJ_IOQUEUE_MAX_HANDLES	(5000)
#else
#define PJ_IOQUEUE_MAX_HANDLES	(FD_SETSIZE)
#endif
#define PJ_IOQUEUE_HAS_SAFE_UNREG 1
#define PJ_IOQUEUE_MAX_EVENTS_IN_SINGLE_POLL (16)

/*
 * Increase the number of socket options available. This adjustment is necessary
 * to accommodate additional TCP keepalive settings required for optimizing SIP
 * transport stability, especially in environments prone to connection timeouts.
 * The default limit is insufficient when configuring all desired keepalive
 * parameters along with standard socket options.
 */
#define PJ_MAX_SOCKOPT_PARAMS 5

#define PJ_SCANNER_USE_BITWISE	0
#define PJ_OS_HAS_CHECK_STACK	0

#ifndef PJ_LOG_MAX_LEVEL
#define PJ_LOG_MAX_LEVEL		6
#endif

#define PJ_ENABLE_EXTRA_CHECK	1
#define PJSIP_MAX_TSX_COUNT		((64*1024)-1)
#define PJSIP_MAX_DIALOG_COUNT	((64*1024)-1)
#define PJSIP_UDP_SO_SNDBUF_SIZE	(512*1024)
#define PJSIP_UDP_SO_RCVBUF_SIZE	(512*1024)
#define PJ_DEBUG			0
#define PJSIP_SAFE_MODULE		0
#define PJ_HAS_STRICMP_ALNUM		0

/*
 * Do not ever enable PJ_HASH_USE_OWN_TOLOWER because the algorithm is
 * inconsistently used when calculating the hash value and doesn't
 * convert the same characters as pj_tolower()/tolower().  Thus you
 * can get different hash values if the string hashed has certain
 * characters in it.  (ASCII '@', '[', '\\', ']', '^', and '_')
 */
#undef PJ_HASH_USE_OWN_TOLOWER

/*
  It is imperative that PJSIP_UNESCAPE_IN_PLACE remain 0 or undefined.
  Enabling it will result in SEGFAULTS when URIs containing escape sequences are encountered.
*/
#undef PJSIP_UNESCAPE_IN_PLACE
#define PJSIP_MAX_PKT_LEN			65535

#undef PJ_TODO
#define PJ_TODO(x)

/* Defaults too low for WebRTC */
#define PJ_ICE_MAX_CAND 64
#define PJ_ICE_MAX_CHECKS (PJ_ICE_MAX_CAND * PJ_ICE_MAX_CAND)

/* Increase limits to allow more formats */
#define	PJMEDIA_MAX_SDP_FMT   72
#define	PJMEDIA_MAX_SDP_BANDW   4
#define	PJMEDIA_MAX_SDP_ATTR   (PJMEDIA_MAX_SDP_FMT*6 + 4)
#define	PJMEDIA_MAX_SDP_MEDIA   16

/*
 * Turn off the periodic sending of CRLNCRLN.  Default is on (90 seconds),
 * which conflicts with the global section's keep_alive_interval option in
 * pjsip.conf.
 */
#define PJSIP_TCP_KEEP_ALIVE_INTERVAL	0
#define PJSIP_TLS_KEEP_ALIVE_INTERVAL	0

#define PJSIP_TSX_UAS_CONTINUE_ON_TP_ERROR 0
#define PJ_SSL_SOCK_OSSL_USE_THREAD_CB 0
#define PJSIP_AUTH_ALLOW_MULTIPLE_AUTH_HEADER 0

/*
 * The default is 32 with 8 being used by pjproject itself.
 * Since this value is used in invites, dialogs, transports
 * and subscriptions as well as the global pjproject endpoint,
 * we don't want to increase it too much.
 */
#define PJSIP_MAX_MODULE 38
