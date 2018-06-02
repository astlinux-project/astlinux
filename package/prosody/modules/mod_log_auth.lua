local mode = module:get_option_string("log_auth_ips", "failure");
assert(({ all = true, failure = true, success = true })[mode],
	"Unknown log mode: "..tostring(mode).." - valid modes are 'all', 'failure', 'success'");

if mode == "failure" or mode == "all" then
	module:hook("authentication-failure", function (event)
		local session = event.session;
		local username = session.username or session.sasl_handler and session.sasl_handler.username or "?";
		session.log("info", "Failed authentication attempt (%s) for user %s from IP: %s",
			event.condition or "unknown-condition", username, session.ip or "?");
	end);
end

if mode == "success" or mode == "all" then
	module:hook("authentication-success", function (event)
		local session = event.session;
		session.log("info", "Successful authentication as %s from IP: %s", session.username, session.ip or "?");
	end);
end
