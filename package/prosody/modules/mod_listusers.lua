function module.command()

	local data_path = CFG_DATADIR or "data";
	
	if not pcall(require, "luarocks.loader") then
		pcall(require, "luarocks.require");
	end
	
	local lfs = require "lfs";
	
	function decode(s)
		return s:gsub("%%([a-fA-F0-9][a-fA-F0-9])", function (c)
			return string.char(tonumber("0x"..c));
		end);
	end
	
	for host in lfs.dir(data_path) do
		local accounts = data_path.."/"..host.."/accounts";
		if lfs.attributes(accounts, "mode") == "directory" then
			for user in lfs.dir(accounts) do
				if user:sub(1,1) ~= "." then
					print(decode(user:gsub("%.dat$", "")).."@"..decode(host));
				end
			end
		end
	end
end
