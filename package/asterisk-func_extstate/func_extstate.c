/*
 * Asterisk -- An open source telephony toolkit.
 *
 * Copyright (C) 2007, Digium, Inc.
 *
 * Modified from func_devstate.c by Russell Bryant <russell@digium.com> 
 * Adam Gundy <adam@starsilk.net>

 * See http://www.asterisk.org for more information about
 * the Asterisk project. Please do not directly contact
 * any of the maintainers of this project for assistance;
 * the project provides a web site, mailing lists and IRC
 * channels for your use.
 *
 * This program is free software, distributed under the terms of
 * the GNU General Public License Version 2. See the LICENSE file
 * at the top of the source tree.
 */

/*! \file
 *
 * \brief Get the state of a hinted extension for dialplan control
 *
 * \author Adam Gundy <adam@starsilk.net> 
 *
 * \ingroup functions
 */

#include "asterisk.h"

ASTERISK_FILE_VERSION(__FILE__, "$Revision$")

#include <stdlib.h>

#include "asterisk/module.h"
#include "asterisk/channel.h"
#include "asterisk/pbx.h"
#include "asterisk/utils.h"
#include "asterisk/devicestate.h"

static const char *ast_extstate_str(int state)
{
	const char *res = "UNKNOWN";

	switch (state) {
	case AST_EXTENSION_NOT_INUSE:
		res = "NOT_INUSE";
		break;
	case AST_EXTENSION_INUSE:
		res = "INUSE";
		break;
	case AST_EXTENSION_BUSY:
		res = "BUSY";
		break;
	case AST_EXTENSION_UNAVAILABLE:
		res = "UNAVAILABLE";
		break;
	case AST_EXTENSION_RINGING:
		res = "RINGING";
		break;
	case AST_EXTENSION_INUSE | AST_EXTENSION_RINGING:
		res = "RINGINUSE";
		break;
	case AST_EXTENSION_INUSE | AST_EXTENSION_ONHOLD:
		res = "HOLDINUSE";
		break;
	case AST_EXTENSION_ONHOLD:
		res = "ONHOLD";
		break;
	}

	return res;
}

static int extstate_read(struct ast_channel *chan, char *cmd, char *data,
	char *buf, size_t len)
{
	char *exten = ast_strdupa(data);
	char *context = NULL;

	context = strchr(exten, '@');
	if (context)
		*context++ = '\0';
	else
		context = chan->context;

	ast_copy_string(buf, ast_extstate_str(ast_extension_state(chan,context,exten)), len);

	return 0;
}

static struct ast_custom_function extstate_function = {
	.name = "EXTSTATE",
	.synopsis = "Get an extension's state",
	.syntax = "EXTSTATE(extension[@context])",
	.desc =
	"  The EXTSTATE function can be used to retrieve the state from any\n"
	"hinted extension.  For example:\n"
	"   NoOp(1234 has state ${EXTSTATE(1234)})\n"
	"   NoOp(4567@home has state ${EXTSTATE(4567@home)})\n"
	"\n"
	"  The possible values returned by this function are:\n"
	"UNKNOWN | NOT_INUSE | INUSE | BUSY | INVALID | UNAVAILABLE | RINGING\n"
	"RINGINUSE | HOLDINUSE | ONHOLD\n",
	.read = extstate_read,
};

static int unload_module(void)
{
	int res;

	res = ast_custom_function_unregister(&extstate_function);
	return res;
}

static int load_module(void)
{
	int res;

	res = ast_custom_function_register(&extstate_function);

	return res;
}

AST_MODULE_INFO_STANDARD(ASTERISK_GPL_KEY, "Gets an extension state in the dialplan");
