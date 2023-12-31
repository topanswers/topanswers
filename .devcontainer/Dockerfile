FROM node:18-bookworm
RUN apt-get update && apt-get install --no-install-recommends -y awscli postgresql-client docker.io pipx php xxhash
RUN pipx install aws-sam-cli && pipx ensurepath
