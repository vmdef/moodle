#!/bin/bash

echo "Rebuilding fixtures/validator/*.zip files ..."

for d in ${1:-*}; do
    if [[ -d ${d} ]]; then
        echo ${d}.zip
        rm -f ${d}.zip
        zip -r ${d}.zip ${d}
    fi
done

zip -d empty.zip "empty/delete.txt"
zip -d empty.zip "empty/"
