FROM  centrifugo/centrifugo:v3


COPY config.json /root/centrifugo/config.json

ENTRYPOINT bash -c "centrifugo -c config.json"

ENTRYPOINT sh -c "centrifugo -c /root/centrifugo/config.json"