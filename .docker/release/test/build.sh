#!/bin/sh
set -eu
docker build -t snappymail/snappymail:test -f .docker/release/Dockerfile .
