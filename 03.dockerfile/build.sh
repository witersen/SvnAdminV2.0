#!/bin/bash

docker build -t core-harbor.xgjoy.org/library/svnadmin2:$1 .
docker push core-harbor.xgjoy.org/library/svnadmin2:$1