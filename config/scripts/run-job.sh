#!/bin/bash

shopt -s extglob

source /vagrant/config/export-development-environment-variables.sh

for i in `env | grep SYMFONY` ; \
do
    NAME=`echo $i | awk -F '=' '{print $1}'`
    VALUE_PART_1=`echo $i | awk -F '=' '{print $2}'`
    VALUE_PART_2=`echo $i | awk -F '=' '{print $3}'`

    # Check if second part is a non-empty string
    if [ -n "${VALUE_PART_2//+([[:spaces:]])/}" ]
    then
        read <<<$VALUE_PART_1'='$VALUE_PART_2
    else
        read <<<$VALUE_PART_1
    fi
    export $NAME'='$REPLY
done;

/var/deploy/devobs/current/app/console wtw:job:run -e prod 2>> /var/log/job.run.error.log >> /var/log/job.run.out.log
