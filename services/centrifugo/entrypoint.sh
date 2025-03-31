#!/bin/sh
# Generate the Centrifugo configuration file
./centrifugo genconfig -c /root/centrifugo/config.json

# Start Centrifugo with the generated config file
exec centrifugo -c /root/centrifugo/config.json "$@"
