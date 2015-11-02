#!/bin/bash

type=prod
user=tomgeomed
pass=--change--me--
url=http://geomedia.huma-num.fr/manager/text
app=RSSAgregate

crondir=/sites/geomedia/resource/cron/watchdog
initscript=/sites/geomedia/resource/init
phpscript=$crondir/watchdog.php
config=$crondir/watchdog_config_${type}.txt
log=$crondir/watchdog_${type}.log

/shared/php/5.5/current/bin/php $phpscript $config > $log  2>&1
if [ $? -eq 1 ]
then
#curl --user $user:$pass $url/stop?path=/$app >> $log 2>&1
#curl --user $user:$pass $url/start?path=/$app >> $log 2>&1
$initscript/tomcat_prod.sh stop >> $log 2>&1
$initscript/tomcat_prod.sh start >> $log 2>&1
fi
