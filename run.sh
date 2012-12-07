#! /bin/bash

if [ -n "$1" ]
then
    if [ $1 == "start" ]
    then
        service mysql start
        service apache2 start
    elif [ $1 == "stop" ]
    then
        service mysql stop
        service apache2 stop
    fi
fi
