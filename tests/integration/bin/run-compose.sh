#!/bin/bash

set -euo pipefail

MAX_TEST_EXECUTION_TIME='30m'

BASEDIR="$(dirname "$(readlink -f "$0")")/../../../"
export BASEDIR
pushd "$BASEDIR"
DOCKERCOMPOSE="docker-compose -f tests/integration/docker-compose.yml"

function cleanup {
    if [ -n "${TESTS_RESULT:-}" ]; then
        docker cp "$($DOCKERCOMPOSE ps -q tests)":/output/. "$TESTS_RESULT" || echo "Failed to copy tests result"
    fi
    $DOCKERCOMPOSE down
}
trap cleanup EXIT

case "${1:-}" in
    "mysql57")
    export DB_HOST="mysql57"
    ;;
    "mariadb103")
    export DB_HOST="mariadb-10.3"
    ;;
    *)
    echo "A database type must be provided as parameter. Allowed values are:"
    echo "* mysql57"
    echo "* mariadb103"
    exit 1
esac

if [ -n "${SETUP_ONLY:-}" ] && [ "$SETUP_ONLY" != "0" ]; then
    $DOCKERCOMPOSE up -d "$DB_HOST"
    $DOCKERCOMPOSE run -e SETUP_ONLY=1 tests /usr/share/tuleap/tests/integration/bin/run.sh
else
    timeout "$MAX_TEST_EXECUTION_TIME" $DOCKERCOMPOSE up --abort-on-container-exit --exit-code-from=tests "$DB_HOST" tests
fi
