#!/usr/bin/bash
openssl req -new -newkey rsa:2048 -days 365 -nodes -x509 \
    -subj "/C=US/ST=US/L=EU/O=Travis/CN=localhost" \
    -keyout ./.travis/ssl.key  -out ./.travis/ssl.crt