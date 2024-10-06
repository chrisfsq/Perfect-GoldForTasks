#!/bin/bash

kill $(ps aux | grep 'DeliverByAwardData: success = 1' | awk '{print $2}')  > /dev/null 2>&1 &
pkill -f gold_task.sh

echo "Script parado com sucesso!"