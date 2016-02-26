/* Custom config for Asterisk
 *
 * https://wiki.asterisk.org/wiki/display/AST/Building+and+Installing+pjproject
 *
 */

#define NDEBUG 1
#define PJ_HAS_IPV6 1
#define PJ_MAX_HOSTNAME 256
#define PJSIP_MAX_URL_SIZE 512
 
/* The upper limit on MAX_HANDLES is determined by
 * the value of FD_SETSIZE on your system.  For Linux
 * this is usually 1024.  The following code sets it
 * to whatever FD_SETSIZE is or you can set it to a
 * specific number yourself.  pjproject will not
 * compile if you set it to greater than FD_SETSIZE.
 */
#include <sys/select.h>
#define PJ_IOQUEUE_MAX_HANDLES (FD_SETSIZE)
 
/* Set for maximum server performance.
 * In tests, setting these parameters reduced
 * CPU load by approximately 25% for the same number
 * of calls per second.  Your results will vary,
 * of course.
 */
#define PJ_SCANNER_USE_BITWISE  0
#define PJ_OS_HAS_CHECK_STACK   0
#define PJ_LOG_MAX_LEVEL        3
#define PJ_ENABLE_EXTRA_CHECK   0
#define PJSIP_MAX_TSX_COUNT     ((64*1024)-1)
#define PJSIP_MAX_DIALOG_COUNT  ((64*1024)-1)
#define PJSIP_UDP_SO_SNDBUF_SIZE    (512*1024)
#define PJSIP_UDP_SO_RCVBUF_SIZE    (512*1024)
#define PJ_DEBUG            0
#define PJSIP_SAFE_MODULE       0
#define PJ_HAS_STRICMP_ALNUM        0
#define PJ_HASH_USE_OWN_TOLOWER     1
#define PJSIP_UNESCAPE_IN_PLACE     1
#undef PJ_TODO
#define PJ_TODO(x)

