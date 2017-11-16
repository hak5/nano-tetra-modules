#!/usr/bin/python

from subprocess import call

php = "/etc/php.ini"
nginx = "/etc/nginx/nginx.conf"

lines = [f for f in open(php)]
with open(php, "w") as out:
	for line in lines:
		if "upload_max_filesize" in line:
			parts = line.split("=")
			parts[1] = " 20M\n"
			line = "=".join(parts)
		if "post_max_size" in line:
			parts = line.split("=")
			parts[1] = " 26M\n"
			line = "=".join(parts)
		out.write(line)
	call(["/etc/init.d/php5-fpm", "reload"])

httpBlock = False
needsCfg = True
index = innerIndex = 0
lines = [f for f in open(nginx)]
for line in lines:
        if "client_max_body_size" in line:
		needsCfg = False
                break
if needsCfg is True:
	with open(nginx, "w") as out:
		for line in lines:
			if "http {" in line:
				httpBlock = True
			if httpBlock is True:
				if innerIndex == 4:
					lines.insert(index + 1, "\tclient_max_body_size 20M;\n")
				innerIndex = innerIndex + 1
			index = index + 1
			out.write(line)
	call(["/etc/init.d/nginx", "reload"])
