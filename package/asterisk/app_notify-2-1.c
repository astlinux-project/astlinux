/*
 * Asterisk -- An open source telephony toolkit.
 *
 */

/*! \file
 *
 * \brief Network Notification Application Module for Asterisk
 *
 * \author Sven Slezak <sunny@mezzo.net>
 *
 * \ingroup applications
 */

/*** MODULEINFO
	<support_level>core</support_level>
 ***/

#include "asterisk.h"

ASTERISK_FILE_VERSION(__FILE__, "$Revision: $")

#include "asterisk/channel.h"
#include "asterisk/module.h"
#include "asterisk/app.h"



#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <netdb.h>
#include <sys/time.h> /* select() */ 

#define DEFAULT_PORT 40000

static char *app = "Notify";

int notify(const char *text, const char *host, int port);

/*** DOCUMENTATION
	<application name="Notify" language="en_US">
		<synopsis>
			Network Notification Application Module for Asterisk
		</synopsis>
		<syntax>
		       <parameter name="text" required="true">
		           <para>The message to send.</para>
		       </parameter>
		       <parameter name="host_port" required="true">
		           <para>The Host to send the message to.</para>
		       </parameter>
		</syntax>
		<description>
			<para>This application sends network notifications from Asterisk to a given host.</para>
		</description>
	</application>
 ***/

static const char notify_app[] = "Notify";


int notify(const char *text, const char *host, int port) 
{
  int sock;
  int broadcast = 1;
  struct sockaddr_in servAddr;
  struct hostent *hp;
  struct ast_hostent ahp;

  if(option_verbose > 2)
    ast_verbose (VERBOSE_PREFIX_3 "Notify: sending '%s' to %s:%d \n", text, host, port);

  if ((sock = socket(AF_INET, SOCK_DGRAM, IPPROTO_UDP)) < 0) {
    ast_log(LOG_ERROR, "cannot open socket\n");
    return -1;
  }

  if(setsockopt(sock, SOL_SOCKET, SO_BROADCAST, &broadcast, sizeof(broadcast)) < 0) {
    ast_log(LOG_ERROR, "setsockopt error.\n");
  }

  memset(&servAddr, 0, sizeof(struct sockaddr_in));
  servAddr.sin_family = AF_INET;
  servAddr.sin_port = htons(port);

  if((servAddr.sin_addr.s_addr = inet_addr(host)) == -1) {
    hp = ast_gethostbyname(host, &ahp);
    if(hp == (struct hostent *)0) {
      ast_log(LOG_ERROR, "unknown host: %s\n", host);
      return -1;
    }
    memcpy(&servAddr.sin_addr, hp->h_addr_list[0], hp->h_length);
  }  

  if (sendto(sock, text, strlen(text)+1, 0, (struct sockaddr *)&servAddr, sizeof(servAddr)) < 0) {
    ast_log(LOG_ERROR, "cannot send text\n");
    close(sock);
    return -1;
  }

  close(sock);
  return 0;
}


static int notify_exec(struct ast_channel *chan, const char *data)
{
  AST_DECLARE_APP_ARGS(args,
		       AST_APP_ARG(text);
		       AST_APP_ARG(host_port);
		       );
  char *parse, *tmp;
  char *host;
  int port = DEFAULT_PORT;

  if (ast_strlen_zero(data)) {
    ast_log(LOG_WARNING, "%s requires an argument (text,host[:port])\n", app);
    return -1;
  }
  
  parse = ast_strdupa(data);
  AST_STANDARD_APP_ARGS(args, parse);

  if (!ast_strlen_zero(args.text)) {
    ast_log(LOG_NOTICE, "message is : %s\n", args.text);
  }

  if (!ast_strlen_zero(args.host_port)) {
    host = args.host_port;
    ast_log(LOG_NOTICE, "host_port is : %s\n", args.host_port);
    if(strchr(args.host_port, ':')) {
      tmp = strsep(&args.host_port, ":");
      port = atoi(strsep(&args.host_port, "\0"));
      host = tmp;
    }

    ast_log(LOG_NOTICE, "send: '%s' to %s:%d\n", args.text, host, port);
    
    notify(args.text, host, port);
  }

  return 0;
}

static int unload_module(void)
{
	return ast_unregister_application(notify_app);
}

static int load_module(void)
{
	if (ast_register_application_xml(notify_app, notify_exec))
		return AST_MODULE_LOAD_FAILURE;
	return AST_MODULE_LOAD_SUCCESS;
}

AST_MODULE_INFO_STANDARD(ASTERISK_GPL_KEY, "Network Notifications for Asterisk");
