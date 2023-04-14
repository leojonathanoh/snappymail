#!/bin/sh
# This script tests a given docker image using https://github.com/GoogleContainerTools/container-structure-test
set -eu

IMAGE=${1:-snappymail/snappymail:test}
SCRIPT_DIR=$( cd "$(dirname "$0")" && pwd )
docker inspect "$IMAGE" > /dev/null 2>&1 || docker pull "$IMAGE" # Pull the image if it doesn't exist in docker daemon
docker run --rm -i \
    -v /var/run/docker.sock:/var/run/docker.sock:ro \
    -v "$SCRIPT_DIR/config.yaml:/config.yaml:ro" \
    gcr.io/gcp-runtimes/container-structure-test:latest test --image "$IMAGE" --config config.yaml
