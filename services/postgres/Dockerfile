ARG POSTGRES_VERSION=alpine
FROM postgres:${POSTGRES_VERSION}


# Install locales and set default
# RUN apt-get update && apt-get install -y locales && \
#     locale-gen en_US.UTF-8 && \
#     update-locale LANG=en_US.UTF-8

# Set environment variables
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8


CMD ["postgres"]

EXPOSE 5432
